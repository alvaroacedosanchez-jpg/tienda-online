<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\EventLogger;
use App\Services\Integration\EventPayloadBuilder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\Concerns\FakesRedsys;
use Tests\TestCase;

/**
 * Tarea 2 · Eventos de la tienda hacia n8n: firma HMAC, reglas del contrato y bandeja de salida (outbox) con reintentos.
 * Contrato: SIE-Tarea2/integracion/contrato.md (apartados 3 a 7).
 */
class IntegrationOutboxTest extends TestCase
{
    use FakesRedsys;
    use RefreshDatabase;

    private const SECRET = 'secreto-de-test';

    private const URL = 'https://n8n.test/webhook/piquantum/eventos';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.n8n.url' => self::URL, 'services.n8n.secret' => self::SECRET, 'services.n8n.timeout' => 5]);
        Http::preventStrayRequests(); // ningún test puede llamar al n8n real
        $this->seed(DatabaseSeeder::class);
    }

    /** Pedido de Laura: 2 × SAL-002 100 ml (13,00) + 1 × SAL-003 (5,90), BIENVENIDA10 (−1,89) y envío (4,95) = 21,96 €. */
    private function placeOrder(): Order
    {
        $this->actingAs(User::where('email', 'laura@example.com')->firstOrFail());
        $sal2 = Product::where('sku', 'SAL-002')->firstOrFail();
        $this->post(route('cart.add', $sal2), ['quantity' => 2, 'variant_id' => $sal2->variants()->where('size', '100 ml')->value('id')]);
        $this->post(route('cart.add', Product::where('sku', 'SAL-003')->firstOrFail()));
        $this->post(route('checkout.code'), ['code' => 'BIENVENIDA10']);
        $this->post('/checkout', [
            'name' => 'Laura Prueba', 'address' => 'Calle Ficticia 1', 'city' => 'Madrid',
            'postal_code' => '28001', 'accept_prototype' => '1',
        ]);

        return Order::latest('id')->firstOrFail();
    }

    private function hasValidSignature(Request $request): bool
    {
        $timestamp = $request->header('X-Piquantum-Timestamp')[0] ?? '';
        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->body(), self::SECRET);

        return hash_equals($expected, $request->header('X-Piquantum-Signature')[0] ?? '');
    }

    /** @return list<array<string, mixed>> cuerpos enviados a n8n, decodificados, en orden */
    private function sentBodies(): array
    {
        return collect(Http::recorded())->map(fn ($pair) => json_decode($pair[0]->body(), true))->all();
    }

    public function test_a_full_purchase_sends_the_four_business_events_signed(): void
    {
        Http::fake(['n8n.test/*' => Http::response(['result' => 'created'], 200)]);

        $order = $this->placeOrder();
        $this->notifyRedsys($this->startRedsysPayment($order), '0000')->assertOk();
        $this->actingAs(User::where('email', 'admin@piquantum.test')->firstOrFail());
        $this->patch(route('admin.orders.status', $order), ['status' => 'enviado']);
        $this->post(route('support.store'), [
            'name' => 'Laura Prueba', 'email' => 'laura@example.com', 'subject' => 'Bote roto',
            'message' => 'Me ha llegado un bote roto, ¿me enviáis otro?', 'order_reference' => $order->reference,
        ]);

        // markPaid genera 2 order.status_changed con origen «redsys»: no se envían (los cubre payment.simulated)
        $this->assertSame(
            ['order.created', 'payment.simulated', 'order.status_changed', 'support.requested'],
            array_column($this->sentBodies(), 'type'),
        );

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);

            return $request->url() === self::URL
                && $request->hasHeader('Content-Type', 'application/json')
                && $request->header('X-Piquantum-Event')[0] === $body['type']
                && $request->header('X-Piquantum-Event-Id')[0] === $body['event_id']
                && $this->hasValidSignature($request)
                && $body['source'] === 'piquantum-web'
                && $body['schema_version'] === '1.0';
        });
        $this->assertCount(4, collect(Http::recorded())->filter(fn ($pair) => $this->hasValidSignature($pair[0])));
        $this->assertSame(4, WebhookDelivery::where('status', WebhookDelivery::SENT)->where('attempts', 1)->count());
    }

    public function test_order_events_carry_the_full_order_in_its_final_status(): void
    {
        Http::fake(['n8n.test/*' => Http::response(['result' => 'created'], 200)]);

        $order = $this->placeOrder();
        $this->notifyRedsys($this->startRedsysPayment($order), '0000')->assertOk();

        [$created, $paid] = $this->sentBodies();

        $this->assertSame('creado', $created['data']['order']['status']);
        $this->assertSame($order->reference, $created['data']['order']['reference']);
        $this->assertEquals(21.96, $created['data']['order']['total']);
        $this->assertSame('laura@example.com', $created['data']['customer']['email']);
        $this->assertSame(['SAL-002-100ML', 'SAL-003'], array_column($created['data']['lines'], 'product_code'));

        // payment.simulated se registra antes de markPaid, pero el cuerpo se monta después del commit: estado final
        $this->assertSame('pendiente_preparacion', $paid['data']['order']['status']);
        $this->assertSame(['redsys', 'approved'], [$paid['data']['payment']['method'], $paid['data']['payment']['status']]);
        $this->assertEquals($paid['data']['order']['total'], $paid['data']['payment']['amount']);
        $this->assertSame($created['data']['lines'], $paid['data']['lines']);
    }

    public function test_simulated_transfer_payments_are_not_sent(): void
    {
        Http::fake(['n8n.test/*' => Http::response(['result' => 'created'], 200)]);

        $order = $this->placeOrder();
        $this->post(route('orders.pay.store', $order), ['method' => 'transfer'])->assertRedirect();

        $this->assertSame(['order.created'], array_column($this->sentBodies(), 'type'));
        $this->assertSame(0, WebhookDelivery::where('type', 'payment.simulated')->count());
    }

    public function test_the_purchase_completes_when_n8n_fails_and_the_delivery_stays_pending(): void
    {
        Http::fake(['n8n.test/*' => Http::response('Internal Server Error', 500)]);

        $order = $this->placeOrder();

        $this->assertSame(Order::CREATED, $order->status, 'La compra no falla por culpa de n8n');
        $delivery = WebhookDelivery::sole();
        $this->assertSame([WebhookDelivery::PENDING, 1, 500], [$delivery->status, $delivery->attempts, $delivery->last_status_code]);
        $this->assertNotNull($delivery->body, 'El cuerpo queda congelado para los reintentos');
        $this->assertEqualsWithDelta(60, now()->diffInSeconds($delivery->next_attempt_at), 2, 'Siguiente intento en 1 minuto');
    }

    public function test_a_timeout_leaves_the_delivery_pending(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 5000 milliseconds'));

        $this->placeOrder();

        $delivery = WebhookDelivery::sole();
        $this->assertSame(WebhookDelivery::PENDING, $delivery->status);
        $this->assertNull($delivery->last_status_code);
        $this->assertStringContainsString('timed out', $delivery->last_error);
    }

    public function test_the_retry_sends_the_same_event_id_and_body_with_a_new_signature(): void
    {
        Http::fake(['n8n.test/*' => Http::sequence()->push('caído', 500)->push(['result' => 'created'], 200)]);
        $this->placeOrder();
        $delivery = WebhookDelivery::sole();
        $first = Http::recorded()[0][0];

        // Antes de tiempo, el comando no la toca
        $this->artisan('integracion:reintentar')->expectsOutputToContain('Enviados: 0')->assertSuccessful();

        $this->travel(2)->minutes();
        $this->artisan('integracion:reintentar')->expectsOutputToContain('Enviados: 1')->assertSuccessful();

        $retry = Http::recorded()[1][0];
        $this->assertSame($delivery->event_id, $retry->header('X-Piquantum-Event-Id')[0]);
        $this->assertSame($first->body(), $retry->body(), 'Mismo cuerpo, byte a byte');
        $this->assertNotSame($first->header('X-Piquantum-Signature')[0], $retry->header('X-Piquantum-Signature')[0]);
        $this->assertTrue($this->hasValidSignature($retry));
        $this->assertSame([WebhookDelivery::SENT, 2], [$delivery->fresh()->status, $delivery->fresh()->attempts]);
    }

    public function test_client_errors_are_not_retried_except_429(): void
    {
        Http::fake(['n8n.test/*' => Http::sequence()->push(['result' => 'invalid'], 422)->push('', 429)]);

        $this->placeOrder();
        $this->post(route('support.store'), [
            'name' => 'Laura Prueba', 'email' => 'laura@example.com', 'subject' => 'Duda', 'message' => 'Una pregunta sobre mi pedido',
        ]);

        $this->assertSame(
            [['order.created', WebhookDelivery::FAILED, 422], ['support.requested', WebhookDelivery::PENDING, 429]],
            WebhookDelivery::orderBy('id')->get()->map(fn ($d) => [$d->type, $d->status, $d->last_status_code])->all(),
        );
    }

    public function test_the_delivery_fails_after_the_initial_send_and_five_retries(): void
    {
        Http::fake(['n8n.test/*' => Http::response('Service Unavailable', 503)]);
        $this->placeOrder();

        foreach (array_slice([1, 2, 5, 10, 30], 0, 4) as $wait) {
            $this->travel($wait + 1)->minutes();
            $this->artisan('integracion:reintentar')->assertSuccessful();
        }
        $this->assertSame(WebhookDelivery::PENDING, WebhookDelivery::sole()->status, 'Tras 5 envíos aún queda un reintento');

        $this->travel(31)->minutes();
        $this->artisan('integracion:reintentar')->expectsOutputToContain('fallidos: 1')->assertSuccessful();

        $this->assertSame([WebhookDelivery::FAILED, 6], [WebhookDelivery::sole()->status, WebhookDelivery::sole()->attempts]);
        Http::assertSentCount(6);
    }

    public function test_nothing_is_recorded_or_sent_when_the_transaction_rolls_back(): void
    {
        Http::fake();
        $order = $this->placeOrder();
        $deliveries = WebhookDelivery::count();
        $requests = count(Http::recorded());

        try {
            DB::transaction(function () use ($order) {
                EventLogger::log(EventLogger::ORDER_STATUS_CHANGED, [
                    'order_id' => $order->id, 'previous_status' => 'creado', 'status' => 'cancelado', 'origin' => 'backoffice',
                ]);
                throw new RuntimeException('Se deshace el cambio');
            });
        } catch (RuntimeException) {
        }
        $this->app->terminate(); // momento en que se haría el primer envío

        $this->assertSame($deliveries, WebhookDelivery::count());
        $this->assertCount($requests, Http::recorded());
    }

    public function test_nothing_is_recorded_when_the_integration_is_not_configured(): void
    {
        config(['services.n8n.url' => '']);
        Http::fake();

        $this->placeOrder();

        $this->assertSame(0, WebhookDelivery::count());
        Http::assertNothingSent();
    }

    public function test_product_code_matches_the_back_office_sku(): void
    {
        $builder = app(EventPayloadBuilder::class);

        $this->assertSame('SAL-002-100ML', $builder->productCode('SAL-002', '100 ml'));
        $this->assertSame('SAL-001-50ML', $builder->productCode('SAL-001', '50 ml'));
        $this->assertSame('SAL-004', $builder->productCode('SAL-004', null));
        $this->assertSame('SAL-004', $builder->productCode('SAL-004', ''));
    }
}
