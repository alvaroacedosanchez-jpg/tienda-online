<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function customerData(): array
    {
        return [
            'name' => 'Laura Prueba',
            'email' => 'laura@example.com',
            'address' => 'Calle Ficticia 1',
            'city' => 'Madrid',
            'postal_code' => '28001',
            'accept_prototype' => '1',
        ];
    }

    public function test_catalog_has_at_least_eight_products(): void
    {
        $this->assertGreaterThanOrEqual(8, Product::count());
        $this->get('/catalogo')->assertOk()->assertSee('Prototipo académico');
    }

    public function test_full_purchase_flow_generates_order_and_events(): void
    {
        $product = Product::first();

        $this->get(route('product.show', $product))->assertOk();
        $this->post(route('cart.add', $product), ['quantity' => 2])->assertRedirect(route('cart.show'));
        $this->get('/checkout')->assertOk();

        $response = $this->post('/checkout', $this->customerData());
        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.pay', $order));
        $this->assertSame(Order::CREATED, $order->status);

        $this->post(route('orders.pay.store', $order), [
            'method' => 'card',
            'card_number' => '4242 4242 4242 4242',
            'card_expiry' => '12/30',
            'card_cvv' => '123',
        ])->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame(Order::PENDING_PREPARATION, $order->status);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'approved', 'card_last4' => '4242']);
        $this->assertDatabaseMissing('payments', ['card_last4' => '4242 4242 4242 4242']);

        foreach (['product.viewed', 'cart.item_added', 'checkout.started', 'order.created', 'payment.simulated'] as $type) {
            $this->assertTrue(Event::where('type', $type)->exists(), "Falta el evento $type");
        }
    }

    public function test_declined_card_keeps_order_created(): void
    {
        $product = Product::first();
        $this->post(route('cart.add', $product), ['quantity' => 1]);
        $this->post('/checkout', $this->customerData());
        $order = Order::firstOrFail();

        $this->post(route('orders.pay.store', $order), [
            'method' => 'card', 'card_number' => '4000 0000 0000 0002', 'card_expiry' => '12/30', 'card_cvv' => '123',
        ])->assertSessionHasErrors('payment');

        $this->assertSame(Order::CREATED, $order->fresh()->status);
    }

    public function test_totals_apply_discount_shipping_and_vat(): void
    {
        $product = Product::where('sku', 'SAL-003')->first(); // 5,90 €
        $this->post(route('cart.add', $product), ['quantity' => 1]);
        $this->post(route('cart.code'), ['code' => 'BIENVENIDA10']);
        $this->post('/checkout', $this->customerData());

        $order = Order::firstOrFail();
        $this->assertEquals(5.90, (float) $order->subtotal);
        $this->assertEquals(0.59, (float) $order->discount);   // 10 %
        $this->assertEquals(4.95, (float) $order->shipping);   // por debajo de 60 €
        $this->assertEquals(10.26, (float) $order->total);
    }

    public function test_checkout_validates_input(): void
    {
        $product = Product::first();
        $this->post(route('cart.add', $product));
        $this->post('/checkout', ['name' => '', 'email' => 'no-es-email', 'postal_code' => '12'])
            ->assertSessionHasErrors(['name', 'email', 'postal_code', 'address', 'city', 'accept_prototype']);
    }

    public function test_support_request_logs_event(): void
    {
        $this->post('/soporte', [
            'name' => 'Marta Test', 'email' => 'marta@example.com',
            'subject' => 'Duda', 'message' => 'Mensaje de prueba largo.',
        ])->assertRedirect();

        $this->assertDatabaseHas('support_tickets', ['subject' => 'Duda']);
        $this->assertTrue(Event::where('type', 'support.requested')->exists());
    }

    public function test_backoffice_requires_admin(): void
    {
        $this->get('/admin/pedidos')->assertRedirect(route('admin.login'));

        $admin = User::where('email', 'admin@piquantum.test')->first();
        $this->actingAs($admin)->get('/admin/pedidos')->assertOk();
        $this->actingAs($admin)->get('/admin/eventos.json')->assertOk()->assertJsonStructure(['count', 'events']);
    }

    public function test_cancelling_an_order_restores_stock(): void
    {
        $product = Product::first();
        $stock = $product->stock;
        $this->post(route('cart.add', $product), ['quantity' => 2]);
        $this->post('/checkout', $this->customerData());
        $order = Order::firstOrFail();
        $this->assertSame($stock - 2, $product->fresh()->stock);

        $admin = User::where('email', 'admin@piquantum.test')->first();
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => Order::CANCELLED])->assertSessionHasNoErrors();

        $this->assertSame($stock, $product->fresh()->stock);
    }
}
