<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WebhookDelivery;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Tarea 2 · GET /api/integration/cron: el cron del panel de DonDominio (solo llama a URLs)
 * lanza los reintentos de la bandeja de salida, con usuario y contraseña HTTP.
 */
class IntegrationCronTest extends TestCase
{
    use RefreshDatabase;

    private const CRON_TOKEN = 'cron-token-de-test-0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.integration.cron_token' => self::CRON_TOKEN,
            'services.n8n.url' => 'https://n8n.test/webhook/piquantum/eventos',
            'services.n8n.secret' => 'secreto-de-test',
        ]);
        Http::preventStrayRequests();
        $this->seed(DatabaseSeeder::class);
    }

    private function cron(?string $user = 'cron', ?string $password = self::CRON_TOKEN): TestResponse
    {
        $headers = $user === null ? [] : ['Authorization' => 'Basic '.base64_encode($user.':'.$password)];

        return $this->getJson('/api/integration/cron', $headers);
    }

    /** Pedido con n8n caído: deja una entrega pendiente. */
    private function pendingDelivery(): WebhookDelivery
    {
        $this->actingAs(User::where('email', 'laura@example.com')->firstOrFail());
        $this->post(route('cart.add', Product::where('sku', 'SAL-003')->firstOrFail()));
        $this->post('/checkout', [
            'name' => 'Laura Prueba', 'address' => 'Calle Ficticia 1', 'city' => 'Madrid',
            'postal_code' => '28001', 'accept_prototype' => '1',
        ]);
        auth()->logout();

        return WebhookDelivery::sole();
    }

    public function test_the_cron_url_requires_the_http_user_and_password(): void
    {
        Http::fake();

        $this->cron(null)->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Basic realm="cron"');
        $this->cron('cron', 'contraseña-incorrecta')->assertUnauthorized();
        $this->cron('otro-usuario')->assertUnauthorized();

        config(['services.integration.cron_token' => '']); // sin contraseña configurada no se acepta nada
        $this->cron('cron', '')->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_the_cron_retries_the_pending_deliveries_that_are_due(): void
    {
        Http::fake(['n8n.test/*' => Http::sequence()->push('caído', 503)->push(['result' => 'created'], 200)]);
        $delivery = $this->pendingDelivery();
        $this->assertSame(WebhookDelivery::PENDING, $delivery->status);

        // Aún no le toca
        $this->cron()->assertOk()->assertJson(['result' => 'ok', 'enviado' => 0]);

        $this->travel(2)->minutes();
        $this->cron()->assertOk()->assertJson(['result' => 'ok', 'enviado' => 1, 'pendiente' => 0]);

        $this->assertSame(WebhookDelivery::SENT, $delivery->fresh()->status);
        $this->assertSame($delivery->event_id, Http::recorded()[1][0]->header('X-Piquantum-Event-Id')[0]);
    }

    public function test_the_cron_does_nothing_while_another_run_is_in_progress(): void
    {
        Http::fake(['n8n.test/*' => Http::sequence()->push('caído', 503)->push(['result' => 'created'], 200)]);
        $delivery = $this->pendingDelivery();
        $this->travel(2)->minutes();

        $lock = Cache::lock('integracion:reintentar', 300);
        $this->assertTrue($lock->get());

        $this->cron()->assertOk()->assertJson(['result' => 'busy']);
        $this->assertSame(WebhookDelivery::PENDING, $delivery->fresh()->status);

        $lock->release();
        $this->cron()->assertOk()->assertJson(['enviado' => 1]);
    }
}
