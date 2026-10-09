<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** La cuenta de administrador no compra ni usa el área de cliente. */
class AdminRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@piquantum.test')->firstOrFail();
    }

    public function test_admin_menu_has_backoffice_but_no_cart_or_account(): void
    {
        $this->actingAs($this->admin())->get('/')
            ->assertOk()
            ->assertSee('Back-office')
            ->assertDontSee('Mi cuenta')
            ->assertDontSee(route('cart.show'), false);
    }

    public function test_admin_cannot_use_cart_checkout_or_account_area(): void
    {
        $this->actingAs($this->admin());
        $product = Product::first();
        $backoffice = route('admin.orders.index');

        $this->post(route('cart.add', $product))->assertRedirect($backoffice);
        $this->get('/carrito')->assertRedirect($backoffice);
        $this->get('/checkout')->assertRedirect($backoffice);
        $this->post('/checkout', [])->assertRedirect($backoffice);
        $this->get('/mi-cuenta')->assertRedirect($backoffice);
        $this->put(route('account.address.update'), [
            'address' => 'Calle Admin 1', 'city' => 'Murcia', 'postal_code' => '30001',
        ])->assertRedirect($backoffice);

        $this->assertEmpty(session('cart', []));
        $this->assertNull(Customer::where('user_id', $this->admin()->id)->first(), 'El admin no tiene ficha de cliente');
        $this->assertSame(0, Order::count());
    }

    public function test_product_page_does_not_offer_add_to_cart_to_admin(): void
    {
        $product = Product::where('sku', 'SAL-002')->firstOrFail();

        $this->actingAs($this->admin())->get(route('product.show', $product))
            ->assertOk()
            ->assertDontSee('Añadir al carrito')
            ->assertSee('no puede hacer pedidos');
    }

    public function test_admin_login_from_shop_goes_to_backoffice_even_from_checkout(): void
    {
        $this->post(route('cart.add', Product::first()));
        $this->get('/checkout')->assertRedirect(route('login')); // guarda el checkout como página pendiente

        $this->post(route('login.store'), ['email' => 'admin@piquantum.test', 'password' => 'admin1234'])
            ->assertRedirect(route('admin.orders.index'));
    }

    public function test_customers_and_guests_are_not_affected(): void
    {
        $product = Product::first();
        $this->post(route('cart.add', $product))->assertRedirect(route('cart.show')); // invitado

        $this->actingAs(User::where('email', 'laura@example.com')->firstOrFail());
        $this->get('/mi-cuenta')->assertOk()->assertSee('Mi cuenta');
        $this->get('/checkout')->assertOk();
    }
}
