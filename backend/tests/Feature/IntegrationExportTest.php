<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Tarea 2 · Sincronización inicial tienda → back-office: GET /api/integration/catalogo y /pedidos.
 * Solo lectura, con el mismo token que /api/integration/sync.
 */
class IntegrationExportTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-de-test-0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.integration.token' => self::TOKEN]);
        Http::preventStrayRequests();
        $this->seed(DatabaseSeeder::class);
    }

    private function getApi(string $path, ?string $token = self::TOKEN): TestResponse
    {
        return $this->getJson($path, $token === null ? [] : ['Authorization' => 'Bearer '.$token]);
    }

    public function test_both_endpoints_require_the_integration_token(): void
    {
        foreach (['/api/integration/catalogo', '/api/integration/pedidos'] as $path) {
            $this->getApi($path, null)->assertUnauthorized();
            $this->getApi($path, 'token-incorrecto')->assertUnauthorized();
        }
    }

    public function test_the_catalog_comes_in_the_back_office_import_format(): void
    {
        $response = $this->getApi('/api/integration/catalogo')->assertOk();
        $products = collect($response->json('products'));

        // 2 productos con 3 tamaños + 7 sin tamaños = 13 referencias, las mismas que el almacén del back-office
        $this->assertSame(13, $response->json('count'));
        $this->assertCount(13, $products->pluck('sku')->unique());

        $variant = Product::where('sku', 'SAL-002')->firstOrFail()->variants()->where('size', '100 ml')->firstOrFail();
        $this->assertEquals([
            'sku' => 'SAL-002-100ML',
            'product_name' => 'Salsa Chipotle Ahumado',
            'category' => Product::where('sku', 'SAL-002')->firstOrFail()->category->slug,
            'variant_size' => '100 ml',
            'unit_price' => round((float) $variant->price, 2),
            'stock_available' => (int) $variant->stock,
        ], $products->firstWhere('sku', 'SAL-002-100ML'));

        $plain = $products->firstWhere('sku', 'SAL-004');
        $this->assertNull($plain['variant_size']);
        $this->assertSame((int) Product::where('sku', 'SAL-004')->value('stock'), $plain['stock_available']);

        // Lo que valida /inventory/import: sku sin espacios, categoría, precio > 0 y stock entero >= 0
        $products->each(function (array $p) {
            $this->assertDoesNotMatchRegularExpression('/\s/', $p['sku']);
            $this->assertNotEmpty($p['category']);
            $this->assertGreaterThan(0, $p['unit_price']);
            $this->assertIsInt($p['stock_available']);
            $this->assertGreaterThanOrEqual(0, $p['stock_available']);
        });
    }

    public function test_the_orders_come_with_the_same_snapshot_as_the_events_oldest_first(): void
    {
        $this->actingAs(User::where('email', 'laura@example.com')->firstOrFail());
        foreach (['SAL-003', 'SAL-004'] as $sku) {
            $this->post(route('cart.add', Product::where('sku', $sku)->firstOrFail()));
            $this->post('/checkout', [
                'name' => 'Laura Prueba', 'address' => 'Calle Ficticia 1', 'city' => 'Madrid',
                'postal_code' => '28001', 'accept_prototype' => '1',
            ]);
            $this->travel(1)->days();
        }
        [$first, $second] = Order::orderByDesc('created_at')->take(2)->get()->reverse()->values();
        $second->update(['status' => Order::CANCELLED]);

        $response = $this->getApi('/api/integration/pedidos')->assertOk();
        $orders = collect($response->json('orders'))->keyBy('reference');

        // Todos los pedidos de la tienda (también los del seeder), del más antiguo al más reciente
        $this->assertSame(Order::count(), $response->json('count'));
        $this->assertSame(
            Order::orderBy('created_at')->orderBy('id')->pluck('reference')->all(),
            array_column($response->json('orders'), 'reference'),
        );

        $a = $orders[$first->reference];
        $this->assertSame($first->created_at->toIso8601String(), $a['created_at'], 'La fecha real del pedido');
        $this->assertSame('cancelado', $orders[$second->reference]['data']['order']['status'], 'Cada pedido con su estado actual');
        $this->assertSame('laura@example.com', $a['data']['customer']['email']);
        $this->assertSame('SAL-003', $a['data']['lines'][0]['product_code']);
        $this->assertSame('28001', $a['data']['shipping_address']['postal_code']);
        $this->assertEquals((float) $first->total, $a['data']['order']['total']);
        $this->assertNull($a['payment_method'], 'Sin pago aprobado no hay método');

        // Con un pago aprobado se indica cómo se pagó
        $this->actingAs(User::where('email', 'laura@example.com')->firstOrFail());
        $this->post(route('orders.pay.store', $first), ['method' => 'transfer'])->assertRedirect();
        $paid = collect($this->getApi('/api/integration/pedidos')->json('orders'))->firstWhere('reference', $first->reference);
        $this->assertSame('transferencia_simulada', $paid['payment_method']);
    }
}
