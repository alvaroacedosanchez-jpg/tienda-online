<?php

namespace Tests\Feature;

use App\Models\IntegrationInboxEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WebhookDelivery;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Tarea 2 · POST /api/integration/sync: n8n aplica en la tienda los cambios del back-office (estado y stock).
 * Contrato: SIE-Tarea2/integracion/contrato.md (apartado 8).
 */
class IntegrationSyncTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-de-test-0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.integration.token' => self::TOKEN,
            // Integración hacia n8n activa, para comprobar que lo que llega de n8n no vuelve a salir (bucle)
            'services.n8n.url' => 'https://n8n.test/webhook/piquantum/eventos',
            'services.n8n.secret' => 'secreto-de-test',
        ]);
        Http::preventStrayRequests();
        Http::fake(['n8n.test/*' => Http::response(['result' => 'created'], 200)]);
        $this->seed(DatabaseSeeder::class);
    }

    private function sync(array $body, ?string $token = self::TOKEN): TestResponse
    {
        return $this->postJson('/api/integration/sync', $body, $token === null ? [] : ['Authorization' => 'Bearer '.$token]);
    }

    /** Pedido de Laura en preparación (pagado), como lo vería el operario del back-office. */
    private function orderInPreparation(): Order
    {
        $this->actingAs(User::where('email', 'laura@example.com')->firstOrFail());
        $this->post(route('cart.add', Product::where('sku', 'SAL-003')->firstOrFail()));
        $this->post('/checkout', [
            'name' => 'Laura Prueba', 'address' => 'Calle Ficticia 1', 'city' => 'Madrid',
            'postal_code' => '28001', 'accept_prototype' => '1',
        ]);
        $order = Order::latest('id')->firstOrFail();
        $order->update(['status' => Order::PENDING_PREPARATION]);
        auth()->logout();

        return $order;
    }

    private function statusChange(Order $order, string $newStatus, string $eventId = 'evt_bo_00000000000000a1'): array
    {
        return ['event_id' => $eventId, 'change_type' => 'ORDER_STATUS', 'order_ref' => $order->reference, 'new_status' => $newStatus];
    }

    private function stockChange(string $productCode, int $newStock, string $eventId = 'evt_bo_00000000000000b1'): array
    {
        return ['event_id' => $eventId, 'change_type' => 'STOCK', 'product_code' => $productCode, 'new_stock' => $newStock];
    }

    private function variant(string $sku, string $size): ProductVariant
    {
        return Product::where('sku', $sku)->firstOrFail()->variants()->where('size', $size)->firstOrFail();
    }

    public function test_requests_without_a_valid_token_are_rejected(): void
    {
        $order = $this->orderInPreparation();
        $body = $this->statusChange($order, 'enviado');

        $this->sync($body, null)->assertUnauthorized();
        $this->sync($body, 'token-incorrecto')->assertUnauthorized();

        config(['services.integration.token' => '']); // la tienda sin token configurado no acepta nada
        $this->sync($body, '')->assertUnauthorized();

        $this->assertSame(Order::PENDING_PREPARATION, $order->fresh()->status);
        $this->assertSame(0, IntegrationInboxEntry::count());
    }

    public function test_a_valid_status_change_is_applied_and_the_customer_is_emailed(): void
    {
        $order = $this->orderInPreparation();
        Mail::fake();

        $this->sync($this->statusChange($order, 'enviado'))
            ->assertOk()
            ->assertJson(['result' => 'updated', 'previous_status' => 'pendiente_preparacion', 'status' => 'enviado']);

        $this->assertSame(Order::SHIPPED, $order->fresh()->status);
        Mail::assertSentCount(1);
        $this->assertDatabaseHas('events', ['type' => 'order.status_changed', 'order_id' => $order->id, 'status' => 'enviado', 'origin' => 'integracion']);
    }

    public function test_the_same_event_is_not_applied_twice(): void
    {
        $order = $this->orderInPreparation();
        Mail::fake();
        $body = $this->statusChange($order, 'enviado');

        $this->sync($body)->assertOk();
        $this->sync($body)->assertOk()->assertJson(['result' => 'duplicate', 'original_result' => 'updated']);

        Mail::assertSentCount(1);
        $this->assertSame(1, IntegrationInboxEntry::count());
    }

    public function test_the_same_change_with_another_event_id_is_unchanged(): void
    {
        $order = $this->orderInPreparation();
        $this->sync($this->statusChange($order, 'enviado'))->assertOk();

        $this->sync($this->statusChange($order, 'enviado', 'evt_bo_00000000000000a2'))
            ->assertOk()
            ->assertJson(['result' => 'unchanged']);
    }

    public function test_changes_coming_from_the_integration_are_not_sent_back_to_n8n(): void
    {
        $order = $this->orderInPreparation();
        $deliveries = WebhookDelivery::count();

        $this->sync($this->statusChange($order, 'enviado'))->assertOk();

        $this->assertSame($deliveries, WebhookDelivery::count(), 'Sin bucle: el cambio no vuelve al back-office');
    }

    public function test_cancelling_from_the_back_office_restores_the_shop_stock(): void
    {
        $order = $this->orderInPreparation();
        $stock = (int) Product::where('sku', 'SAL-003')->value('stock');

        $this->sync($this->statusChange($order, 'cancelado'))->assertOk();

        $this->assertSame($stock + 1, (int) Product::where('sku', 'SAL-003')->value('stock'));
    }

    public function test_a_transition_that_is_not_allowed_is_rejected_with_the_allowed_next_states(): void
    {
        $order = $this->orderInPreparation();
        $order->update(['status' => Order::SHIPPED]);
        $body = $this->statusChange($order, 'pendiente_preparacion');

        $this->sync($body)
            ->assertUnprocessable()
            ->assertJson(['result' => 'invalid_transition', 'current_status' => 'enviado', 'allowed_next' => ['incidencia']]);

        // Repetido, devuelve el mismo error (nunca un 200 que n8n tomaría como sincronizado)
        $this->sync($body)
            ->assertUnprocessable()
            ->assertJson(['result' => 'duplicate', 'original_result' => 'invalid_transition']);

        $this->assertSame(Order::SHIPPED, $order->fresh()->status);
    }

    public function test_an_unknown_order_returns_404(): void
    {
        $this->sync(['event_id' => 'evt_bo_00000000000000a9', 'change_type' => 'ORDER_STATUS', 'order_ref' => 'PQ-20991231-NOEXIS', 'new_status' => 'enviado'])
            ->assertNotFound()
            ->assertJson(['result' => 'not_found']);
    }

    public function test_the_stock_of_a_size_is_set_to_the_absolute_value(): void
    {
        $variant = $this->variant('SAL-002', '100 ml');

        $this->sync($this->stockChange('SAL-002-100ML', 25))
            ->assertOk()
            ->assertJson(['result' => 'updated', 'stock' => 25]);
        $this->assertSame(25, (int) $variant->fresh()->stock);

        // Repetir con otro event_id no suma nada: es un valor absoluto
        $this->sync($this->stockChange('SAL-002-100ML', 25, 'evt_bo_00000000000000b2'))->assertOk()->assertJson(['result' => 'unchanged']);
        $this->assertSame(25, (int) $variant->fresh()->stock);
    }

    public function test_the_stock_of_a_product_without_sizes_is_updated_and_the_code_is_case_insensitive(): void
    {
        $this->sync($this->stockChange('sal-004', 40))->assertOk()->assertJson(['product_code' => 'SAL-004']);

        $this->assertSame(40, (int) Product::where('sku', 'SAL-004')->value('stock'));
    }

    public function test_unknown_product_codes_and_skus_without_their_size_return_404(): void
    {
        $this->sync($this->stockChange('SAL-999', 10))->assertNotFound();
        $this->sync($this->stockChange('SAL-002', 10, 'evt_bo_00000000000000b3'))->assertNotFound();
    }

    public function test_invalid_payloads_are_rejected_and_recorded(): void
    {
        $this->sync(['event_id' => 'evt_bo_00000000000000c1', 'change_type' => 'STOCK'])
            ->assertUnprocessable()
            ->assertJson(['result' => 'invalid'])
            ->assertJsonValidationErrors(['product_code', 'new_stock']);

        $this->sync($this->stockChange('SAL-004', -1, 'evt_bo_00000000000000c2'))->assertUnprocessable()->assertJsonValidationErrors(['new_stock']);
        $this->sync(['event_id' => 'evt_bo_00000000000000c3', 'change_type' => 'BORRAR_TODO'])->assertUnprocessable()->assertJsonValidationErrors(['change_type']);
        $this->sync(['change_type' => 'STOCK'])->assertUnprocessable()->assertJsonValidationErrors(['event_id']);

        // Con event_id se apuntan (decisión 2 B); sin event_id no hay con qué
        $this->assertSame(
            ['evt_bo_00000000000000c1', 'evt_bo_00000000000000c2', 'evt_bo_00000000000000c3'],
            IntegrationInboxEntry::where('result', 'invalid')->orderBy('id')->pluck('event_id')->all(),
        );
    }

    public function test_every_notice_is_recorded_in_the_inbox_with_its_result(): void
    {
        $order = $this->orderInPreparation();

        $this->sync($this->statusChange($order, 'enviado'));
        $this->sync($this->statusChange($order, 'pendiente_preparacion', 'evt_bo_00000000000000a2'));
        $this->sync($this->stockChange('SAL-404', 1));

        $this->assertSame(
            [
                ['evt_bo_00000000000000a1', 'ORDER_STATUS', $order->reference, 'updated', 200],
                ['evt_bo_00000000000000a2', 'ORDER_STATUS', $order->reference, 'invalid_transition', 422],
                ['evt_bo_00000000000000b1', 'STOCK', 'SAL-404', 'not_found', 404],
            ],
            IntegrationInboxEntry::orderBy('id')->get()
                ->map(fn ($e) => [$e->event_id, $e->change_type, $e->entity_ref, $e->result, $e->http_status])->all(),
        );
    }
}
