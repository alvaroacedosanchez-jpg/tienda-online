<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Carrito guardado en BD para clientes registrados, fusión al iniciar sesión y stock de variante. */
class PersistentCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function laura(): User
    {
        return User::where('email', 'laura@example.com')->firstOrFail();
    }

    private function simpleProduct(string $sku = 'SAL-003'): Product
    {
        return Product::where('sku', $sku)->firstOrFail();
    }

    private function chipotle(): Product
    {
        return Product::where('sku', 'SAL-002')->with('variants')->firstOrFail();
    }

    private function login(): void
    {
        $this->post(route('login.store'), ['email' => 'laura@example.com', 'password' => 'cliente1234']);
    }

    private function cartUnits(): int
    {
        return app(CartService::class)->count();
    }

    // ------------------------------------------------------------ persistencia

    public function test_customer_cart_survives_logout_and_new_login(): void
    {
        $this->login();
        $this->post(route('cart.add', $this->simpleProduct()), ['quantity' => 2]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $this->simpleProduct()->id, 'quantity' => 2]);

        $this->post(route('logout'));
        $this->assertGuest();
        $this->assertSame(0, $this->cartUnits(), 'Como invitado, el carrito de la sesión está vacío');

        $this->login();
        $this->assertSame(2, $this->cartUnits(), 'Al volver a entrar, el carrito sigue ahí');
        $this->get('/carrito')->assertSee($this->simpleProduct()->name);
    }

    public function test_guest_cart_stays_in_session(): void
    {
        $this->post(route('cart.add', $this->simpleProduct()));

        $this->assertNotEmpty(session('cart'));
        $this->assertSame(0, Cart::count());
    }

    public function test_variant_lines_are_saved_and_restored(): void
    {
        $product = $this->chipotle();
        $variant = $product->variants->last();

        $this->actingAs($this->laura());
        $this->post(route('cart.add', $product), ['quantity' => 2, 'variant_id' => $variant->id]);

        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 2]);
        $line = app(CartService::class)->items()->first();
        $this->assertSame($variant->id, $line['variant']->id);

        // La misma clave de siempre permite actualizar y quitar la línea
        $this->patch(route('cart.update', "{$product->id}_{$variant->id}"), ['quantity' => 3]);
        $this->assertSame(3, CartItem::firstOrFail()->quantity);
        $this->delete(route('cart.remove', "{$product->id}_{$variant->id}"));
        $this->assertSame(0, CartItem::count());
    }

    // ------------------------------------------------------------ fusión

    public function test_guest_cart_is_merged_into_saved_cart_on_login(): void
    {
        $verde = $this->simpleProduct('SAL-003');
        $sriracha = $this->simpleProduct('SAL-004');

        // Carrito guardado de una visita anterior: 2 Verde
        $this->login();
        $this->post(route('cart.add', $verde), ['quantity' => 2]);
        $this->post(route('logout'));

        // Como invitado: 3 Verde y 1 Sriracha
        $this->post(route('cart.add', $verde), ['quantity' => 3]);
        $this->post(route('cart.add', $sriracha));

        $this->login();

        $lines = app(CartService::class)->items();
        $this->assertSame(5, $lines["{$verde->id}"]['quantity'], 'Se suman las cantidades');
        $this->assertSame(1, $lines["{$sriracha->id}"]['quantity']);
        $this->assertEmpty(session('cart'), 'El carrito de invitado se vacía tras fusionarlo');
    }

    public function test_merge_respects_stock_and_max_per_line(): void
    {
        $verde = $this->simpleProduct();

        $this->login();
        $this->post(route('cart.add', $verde), ['quantity' => 8]);
        $this->post(route('logout'));

        $this->post(route('cart.add', $verde), ['quantity' => 5]);
        $this->login();

        $this->assertSame(10, app(CartService::class)->items()["{$verde->id}"]['quantity'], 'Máximo 10 por línea');
    }

    public function test_guest_cart_is_kept_on_register(): void
    {
        $this->post(route('cart.add', $this->simpleProduct()), ['quantity' => 2]);

        $this->post(route('register.store'), [
            'name' => 'Nuevo Cliente', 'email' => 'nuevo@example.com',
            'password' => 'cliente1234', 'password_confirmation' => 'cliente1234',
        ]);

        $user = User::where('email', 'nuevo@example.com')->firstOrFail();
        $this->assertSame(2, (int) $user->cart->items()->sum('quantity'));
    }

    public function test_admin_has_no_saved_cart(): void
    {
        $this->post(route('cart.add', $this->simpleProduct()));
        $this->post(route('login.store'), ['email' => 'admin@piquantum.test', 'password' => 'admin1234']);

        $this->assertSame(0, Cart::count());
    }

    // ------------------------------------------------------------ checkout

    public function test_order_empties_saved_cart_in_the_same_transaction(): void
    {
        $this->actingAs($this->laura());
        $this->post(route('cart.add', $this->simpleProduct()), ['quantity' => 2]);
        $this->assertSame(1, CartItem::count());

        $this->post('/checkout', [
            'name' => 'Laura Prueba', 'address' => 'Calle Ficticia 1', 'city' => 'Madrid',
            'postal_code' => '28001', 'accept_prototype' => '1',
        ])->assertRedirect();

        $this->assertSame(0, CartItem::count());
        $this->assertSame($this->laura()->id, Order::latest('id')->firstOrFail()->customer->user_id);
    }

    public function test_discount_code_is_applied_in_checkout(): void
    {
        $this->actingAs($this->laura());
        $this->post(route('cart.add', $this->simpleProduct()));

        $this->post(route('checkout.code'), ['code' => 'NOEXISTE'])->assertSessionHasErrors('code');
        $this->post(route('checkout.code'), ['code' => 'bienvenida10'])->assertRedirect(route('checkout.show'));
        $this->get('/checkout')->assertSee('BIENVENIDA10');

        // El carrito ya no tiene formulario de código
        $this->get('/carrito')->assertDontSee(route('checkout.code'), false)->assertSee('se aplican en el checkout');
    }

    // ------------------------------------------------------------ stock de la variante

    public function test_cannot_add_sold_out_variant_even_if_product_has_stock(): void
    {
        $product = $this->chipotle();
        $variant = $product->variants->first();
        $variant->update(['stock' => 0]);

        $this->post(route('cart.add', $product), ['variant_id' => $variant->id])->assertSessionHasErrors('cart');

        $this->assertSame(0, $this->cartUnits());
    }

    public function test_can_add_variant_with_stock_even_if_product_stock_is_zero(): void
    {
        $product = $this->chipotle();
        $product->update(['stock' => 0]);

        $this->post(route('cart.add', $product), ['variant_id' => $product->variants->first()->id])
            ->assertRedirect(route('cart.show'));

        $this->assertSame(1, $this->cartUnits());
    }

    public function test_variant_is_required_and_must_belong_to_the_product(): void
    {
        $product = $this->chipotle();
        $otherVariant = Product::where('sku', 'SAL-001')->firstOrFail()->variants()->first();

        $this->post(route('cart.add', $product))->assertSessionHasErrors('variant_id');
        $this->post(route('cart.add', $product), ['variant_id' => $otherVariant->id])->assertSessionHasErrors('variant_id');
        $this->post(route('cart.add', $this->simpleProduct()), ['variant_id' => $otherVariant->id])->assertSessionHasErrors('variant_id');

        $this->assertSame(0, $this->cartUnits());
    }

    public function test_cart_warns_when_quantity_exceeds_current_stock(): void
    {
        $product = $this->simpleProduct();
        $this->actingAs($this->laura());
        $this->post(route('cart.add', $product), ['quantity' => 4]);

        $product->update(['stock' => 2]); // otro cliente compra mientras tanto

        $this->get('/carrito')->assertSee('Solo quedan 2 unidades');
        $this->assertSame(4, $this->cartUnits(), 'Solo se avisa: la cantidad no cambia');

        $product->update(['stock' => 0]);
        $this->get('/carrito')->assertSee('Agotado');
    }
}
