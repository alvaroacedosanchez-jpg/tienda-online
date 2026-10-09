<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function login(string $email = 'laura@example.com'): User
    {
        $user = User::where('email', $email)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    /** Compra un producto como el usuario conectado y, opcionalmente, lo paga. */
    private function buy(bool $pay = true, string $card = '4242 4242 4242 4242'): Order
    {
        $this->post(route('cart.add', Product::first()));
        $this->post('/checkout', [
            'name' => 'Laura Prueba', 'address' => 'Calle Ficticia 1', 'city' => 'Madrid',
            'postal_code' => '28001', 'accept_prototype' => '1',
        ]);
        $order = Order::latest('id')->firstOrFail();

        if ($pay) {
            $this->post(route('orders.pay.store', $order), [
                'method' => 'card', 'card_number' => $card, 'card_expiry' => '12/30', 'card_cvv' => '123',
            ]);
        }

        return $order->fresh();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/mi-cuenta')->assertRedirect(route('login'));
        $this->get('/mi-cuenta/pedidos')->assertRedirect(route('login'));
        $this->get('/mi-cuenta/facturas')->assertRedirect(route('login'));
    }

    public function test_orders_are_split_into_in_progress_and_past_and_only_own(): void
    {
        $this->login('carlos@example.com');
        $carlosOrder = $this->buy();

        $this->login();
        $shipped = $this->buy();
        $shipped->update(['status' => Order::SHIPPED]);
        $pending = $this->buy();

        $this->get('/mi-cuenta/pedidos')
            ->assertOk()
            ->assertSeeInOrder(['En curso', $pending->reference, 'Anteriores', $shipped->reference])
            ->assertDontSee($carlosOrder->reference);

        $this->get('/mi-cuenta')->assertOk()->assertSee($pending->reference)->assertDontSee($shipped->reference);
    }

    public function test_approved_payment_issues_correlative_invoices_and_event(): void
    {
        $this->login();
        $first = $this->buy();
        $second = $this->buy();

        $year = now()->format('Y');
        $this->assertSame("FAC-$year-0001", $first->invoice->number);
        $this->assertSame("FAC-$year-0002", $second->invoice->number);
        $this->assertEquals($first->total, $first->invoice->total);
        $this->assertSame(2, Event::where('type', 'invoice.issued')->count());

        $this->get('/mi-cuenta/facturas')->assertOk()->assertSee("FAC-$year-0001")->assertSee("FAC-$year-0002");
    }

    public function test_declined_payment_does_not_issue_invoice(): void
    {
        $this->login();
        $order = $this->buy(card: '4000 0000 0000 0002');

        $this->assertSame(Order::CREATED, $order->status);
        $this->assertNull($order->invoice);
    }

    public function test_invoice_failure_rolls_back_the_payment(): void
    {
        $this->login();
        $order = $this->buy(pay: false);

        // Simula un fallo al guardar la factura (dentro de la transacción del pago)
        Invoice::creating(fn () => throw new \RuntimeException('Fallo simulado al emitir la factura'));

        $this->post(route('orders.pay.store', $order), ['method' => 'transfer'])->assertServerError();

        $order->refresh();
        $this->assertSame(Order::CREATED, $order->status);
        $this->assertSame(0, $order->payments()->count());
        $this->assertFalse(Event::where('type', 'payment.simulated')->exists());
        $this->assertSame(0, Invoice::count());
    }

    public function test_owner_downloads_invoice_pdf_and_others_get_404(): void
    {
        $this->login();
        $invoice = $this->buy()->invoice;

        $response = $this->get(route('account.invoices.pdf', $invoice))->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->login('carlos@example.com');
        $this->get(route('account.invoices.pdf', $invoice))->assertNotFound();
    }

    public function test_password_change_requires_current_password(): void
    {
        $this->login();

        $this->put(route('account.password.update'), [
            'current_password' => 'incorrecta', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345',
        ])->assertSessionHasErrorsIn('password', 'current_password');

        $this->put(route('account.password.update'), [
            'current_password' => 'cliente1234', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345',
        ])->assertRedirect(route('account.show'))->assertSessionHasNoErrors();

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => 'laura@example.com', 'password' => 'nueva12345'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_default_address_change_does_not_alter_past_orders(): void
    {
        $user = $this->login();
        $order = $this->buy();

        $this->put(route('account.address.update'), [
            'address' => 'Avenida Nueva 9', 'city' => 'Sevilla', 'postal_code' => '41001',
        ])->assertRedirect(route('account.show'));

        $this->assertSame('Avenida Nueva 9', $user->customer->fresh()->address);
        $this->assertSame('Calle Ficticia 1', $order->fresh()->shipping_address);
        $this->assertSame('Calle Ficticia 1', $order->invoice->fresh()->billing_address);
    }

    public function test_default_address_creates_customer_if_missing(): void
    {
        $user = User::create(['name' => 'Nuevo Cliente', 'email' => 'nuevo@example.com', 'password' => 'cliente1234']);
        $this->actingAs($user);

        $this->put(route('account.address.update'), [
            'address' => 'Calle Uno 1', 'city' => 'Murcia', 'postal_code' => '30001',
        ])->assertSessionHasNoErrors();

        $customer = $user->fresh()->customer;
        $this->assertSame('Nuevo Cliente', $customer->name);
        $this->assertSame('nuevo@example.com', $customer->email);
        $this->assertSame('Murcia', $customer->city);
    }

    public function test_account_deletion_is_blocked_with_orders_in_progress(): void
    {
        $user = $this->login();
        $this->buy();

        $this->delete(route('account.destroy'), ['current_password' => 'cliente1234'])
            ->assertSessionHasErrorsIn('delete', 'current_password');

        $this->assertNull($user->fresh()->deleted_at);
        $this->assertAuthenticated();
    }

    public function test_account_deletion_soft_deletes_and_logs_out(): void
    {
        $user = $this->login();
        $order = $this->buy();
        $order->update(['status' => Order::SHIPPED]);

        $this->delete(route('account.destroy'), ['current_password' => 'incorrecta'])
            ->assertSessionHasErrorsIn('delete', 'current_password');

        $this->delete(route('account.destroy'), ['current_password' => 'cliente1234'])
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertSoftDeleted($user);
        $this->assertNotNull(Order::find($order->id), 'Los pedidos se conservan');

        // Ya no puede iniciar sesión
        $this->post(route('login.store'), ['email' => 'laura@example.com', 'password' => 'cliente1234'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
