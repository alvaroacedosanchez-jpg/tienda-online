<?php

namespace Tests\Feature;

use App\Exceptions\CheckoutException;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\PaymentSimulator;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesRedsys;
use Tests\TestCase;

class PurchaseFlowTest extends TestCase
{
    use FakesRedsys;
    use RefreshDatabase;

    /** El seeder crea pedidos de demostración: las comprobaciones se hacen respecto a lo que ya había. */
    private int $ordersBefore;

    private int $itemsBefore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->ordersBefore = Order::count();
        $this->itemsBefore = OrderItem::count();
    }

    /** Último pedido creado (el del test, no uno de demostración del seeder). */
    private function lastOrder(): Order
    {
        return Order::latest('id')->firstOrFail();
    }

    /** Inicia sesión como la clienta de prueba Laura (el checkout exige usuario). */
    private function actingAsCustomer(string $email = 'laura@example.com'): User
    {
        $user = User::where('email', $email)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    private function customerData(): array
    {
        return [
            'name' => 'Laura Prueba',
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
        $this->actingAsCustomer();
        $product = Product::doesntHave('variants')->first();

        $this->get(route('product.show', $product))->assertOk();
        $this->post(route('cart.add', $product), ['quantity' => 2])->assertRedirect(route('cart.show'));
        $this->get('/checkout')->assertOk();

        $response = $this->post('/checkout', $this->customerData());
        $order = $this->lastOrder();
        $response->assertRedirect(route('orders.pay', $order));
        $this->assertSame(Order::CREATED, $order->status);

        // Pago con tarjeta en el TPV de Redsys: redirección y respuesta firmada de Redsys
        $payment = $this->startRedsysPayment($order);
        $this->notifyRedsys($payment)->assertOk();

        $order->refresh();
        $this->assertSame(Order::PENDING_PREPARATION, $order->status);
        // De la tarjeta solo se guardan los 4 últimos dígitos (Redsys la envía enmascarada)
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'approved', 'card_last4' => '0003']);

        foreach (['product.viewed', 'cart.item_added', 'checkout.started', 'order.created', 'payment.simulated'] as $type) {
            $this->assertTrue(Event::where('type', $type)->exists(), "Falta el evento $type");
        }
    }

    public function test_declined_card_in_redsys_keeps_order_created(): void
    {
        $this->actingAsCustomer();
        $product = Product::doesntHave('variants')->first();
        $this->post(route('cart.add', $product), ['quantity' => 1]);
        $this->post('/checkout', $this->customerData());
        $order = $this->lastOrder();

        $payment = $this->startRedsysPayment($order);
        $this->get(route('redsys.ko', $payment).'?'.http_build_query($this->redsysResponse($payment, '0190')))
            ->assertRedirect(route('orders.pay', $order))
            ->assertSessionHasErrors('payment');

        $this->assertSame(Order::CREATED, $order->fresh()->status);
    }

    public function test_totals_apply_discount_shipping_and_vat(): void
    {
        $this->actingAsCustomer();
        $product = Product::where('sku', 'SAL-003')->first(); // 5,90 €
        $this->post(route('cart.add', $product), ['quantity' => 1]);
        $this->post(route('checkout.code'), ['code' => 'BIENVENIDA10']); // el código se aplica en el checkout
        $this->post('/checkout', $this->customerData());

        $order = $this->lastOrder();
        $this->assertEquals(5.90, (float) $order->subtotal);
        $this->assertEquals(0.59, (float) $order->discount);   // 10 %
        $this->assertEquals(4.95, (float) $order->shipping);   // por debajo de 60 €
        $this->assertEquals(10.26, (float) $order->total);
    }

    public function test_checkout_validates_input(): void
    {
        $this->actingAsCustomer();
        $product = Product::doesntHave('variants')->first();
        $this->post(route('cart.add', $product));
        $this->post('/checkout', ['name' => '', 'postal_code' => '12'])
            ->assertSessionHasErrors(['name', 'postal_code', 'address', 'city', 'accept_prototype']);
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
        $this->actingAsCustomer();
        $product = Product::doesntHave('variants')->first();
        $stock = $product->stock;
        $this->post(route('cart.add', $product), ['quantity' => 2]);
        $this->post('/checkout', $this->customerData());
        $order = $this->lastOrder();
        $this->assertSame($stock - 2, $product->fresh()->stock);

        $admin = User::where('email', 'admin@piquantum.test')->first();
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => Order::CANCELLED])->assertSessionHasNoErrors();

        $this->assertSame($stock, $product->fresh()->stock);
    }

    public function test_guest_must_log_in_to_checkout_and_returns_there_after_login(): void
    {
        $this->post(route('cart.add', Product::doesntHave('variants')->first()));

        $this->get('/checkout')->assertRedirect(route('login'));

        // Tras iniciar sesión vuelve al checkout, con el carrito intacto
        $this->post(route('login.store'), ['email' => 'laura@example.com', 'password' => 'cliente1234'])
            ->assertRedirect(route('checkout.show'));
        $this->get('/checkout')->assertOk();
    }

    public function test_each_order_keeps_its_own_shipping_address(): void
    {
        $user = $this->actingAsCustomer();
        $customer = $user->customer;
        $product = Product::doesntHave('variants')->first();

        $this->post(route('cart.add', $product));
        $this->post('/checkout', ['address' => 'Calle Uno 1'] + $this->customerData());
        $this->post(route('cart.add', $product));
        $this->post('/checkout', ['address' => 'Avenida Dos 2', 'city' => 'Sevilla'] + $this->customerData());

        [$first, $second] = Order::orderByDesc('id')->take(2)->get()->reverse()->values();
        $this->assertSame('Calle Uno 1', $first->shipping_address);
        $this->assertSame('Avenida Dos 2', $second->shipping_address);
        $this->assertSame('Sevilla', $second->shipping_city);

        // Una sola ficha de cliente, reutilizada y sin modificar (dirección por defecto de la primera vez)
        $this->assertSame($customer->id, $first->customer_id);
        $this->assertSame($customer->id, $second->customer_id);
        $this->assertSame(1, Customer::where('user_id', $user->id)->count());
        $this->assertSame('Calle Ficticia 1', $customer->fresh()->address);
    }

    public function test_first_purchase_creates_the_customer_record(): void
    {
        $user = User::create(['name' => 'Nuevo Cliente', 'email' => 'nuevo@example.com', 'password' => 'cliente1234']);
        $this->actingAs($user);

        $this->post(route('cart.add', Product::doesntHave('variants')->first()));
        $this->post('/checkout', $this->customerData())->assertRedirect();

        $customer = $user->fresh()->customer;
        $this->assertNotNull($customer);
        $this->assertSame('nuevo@example.com', $customer->email); // el correo de la cuenta
        $this->assertSame($customer->id, $this->lastOrder()->customer_id);
    }

    public function test_checkout_without_stock_rolls_back_everything(): void
    {
        // Usuario sin ficha de cliente: la ficha se crearía DENTRO de la transacción
        $user = User::create(['name' => 'Nuevo Cliente', 'email' => 'nuevo@example.com', 'password' => 'cliente1234']);
        $this->actingAs($user);

        $product = Product::doesntHave('variants')->first();
        $this->post(route('cart.add', $product), ['quantity' => 2]);
        $product->update(['stock' => 1]); // otro cliente compra mientras tanto

        $this->post('/checkout', $this->customerData())
            ->assertRedirect(route('cart.show'))
            ->assertSessionHasErrors('cart');

        $this->assertSame($this->ordersBefore, Order::count());
        $this->assertDatabaseCount('order_items', $this->itemsBefore);
        $this->assertFalse(Event::where('type', 'order.created')->exists());
        $this->assertNull($user->fresh()->customer, 'La ficha de cliente debe revertirse');
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertGreaterThan(0, app(CartService::class)->count(), 'El carrito (guardado en BD) se conserva para reintentar');
    }

    public function test_technical_error_in_last_step_rolls_back_order_and_stock(): void
    {
        $this->actingAsCustomer();
        $product = Product::doesntHave('variants')->first();
        $stock = $product->stock;
        $this->post(route('cart.add', $product), ['quantity' => 2]);

        // Simula un fallo al guardar el evento order.created (último paso de la transacción)
        Event::creating(function (Event $event) {
            if ($event->type === 'order.created') {
                throw new \RuntimeException('Fallo simulado de base de datos');
            }
        });

        $this->post('/checkout', $this->customerData())->assertServerError();

        $this->assertSame($this->ordersBefore, Order::count());
        $this->assertDatabaseCount('order_items', $this->itemsBefore);
        $this->assertSame($stock, $product->fresh()->stock);
        $this->assertGreaterThan(0, app(CartService::class)->count(), 'El carrito (guardado en BD) se conserva');
    }

    public function test_variant_purchase_uses_variant_price_and_stock(): void
    {
        $this->actingAsCustomer();
        $product = Product::where('sku', 'SAL-002')->firstOrFail();
        $variant = $product->variants()->where('size', '200 ml')->firstOrFail(); // 9,90 €, stock 10
        $productStock = $product->stock;

        $this->post(route('cart.add', $product), ['quantity' => 2, 'variant_id' => $variant->id]);
        $this->post('/checkout', $this->customerData())->assertRedirect();

        $item = $this->lastOrder()->items()->firstOrFail();
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame('200 ml', $item->variant_size);
        $this->assertEquals(9.90, (float) $item->unit_price);
        $this->assertEquals(19.80, (float) $item->line_total);
        $this->assertSame(8, $variant->fresh()->stock);            // se descuenta de la variante
        $this->assertSame($productStock, $product->fresh()->stock); // y no del producto
    }

    public function test_variant_without_stock_rolls_back_everything(): void
    {
        $this->actingAsCustomer();
        $product = Product::where('sku', 'SAL-002')->firstOrFail();
        $variant = $product->variants()->where('size', '200 ml')->firstOrFail();

        $this->post(route('cart.add', $product), ['quantity' => 3, 'variant_id' => $variant->id]);
        $variant->update(['stock' => 2]);

        $this->post('/checkout', $this->customerData())
            ->assertRedirect(route('cart.show'))
            ->assertSessionHasErrors('cart');

        $this->assertSame($this->ordersBefore, Order::count());
        $this->assertSame(2, $variant->fresh()->stock);
    }

    public function test_other_users_cannot_see_or_pay_an_order(): void
    {
        $this->actingAsCustomer();
        $this->post(route('cart.add', Product::doesntHave('variants')->first()));
        $this->post('/checkout', $this->customerData());
        $order = $this->lastOrder();

        $this->actingAsCustomer('carlos@example.com');
        $this->get(route('orders.show', $order))->assertNotFound();
        $this->get(route('orders.pay', $order))->assertNotFound();
        $this->post(route('orders.pay.store', $order), ['method' => 'transfer'])->assertNotFound();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_an_order_cannot_be_paid_twice(): void
    {
        $this->actingAsCustomer();
        $this->post(route('cart.add', Product::doesntHave('variants')->first()));
        $this->post('/checkout', $this->customerData());
        $order = $this->lastOrder();
        $stale = $order->replicate(); // copia "vieja" que aún cree que el pedido está sin pagar
        $stale->id = $order->id;

        $this->post(route('orders.pay.store', $order), ['method' => 'transfer'])->assertRedirect(route('orders.show', $order));
        $this->post(route('orders.pay.store', $order), ['method' => 'transfer'])->assertNotFound();

        // Aunque una segunda petición llegue con datos antiguos, el bloqueo y la relectura lo impiden
        $this->expectException(CheckoutException::class);
        try {
            app(PaymentSimulator::class)->pay($stale);
        } finally {
            $this->assertSame(1, $order->payments()->where('status', 'approved')->count());
        }
    }
}
