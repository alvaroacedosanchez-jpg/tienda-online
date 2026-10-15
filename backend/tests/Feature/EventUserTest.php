<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Trazabilidad: qué usuario provocó cada evento y eventos completos del carrito. */
class EventUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email = 'laura@example.com'): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /**
     * Los tests no guardan la cookie de sesión entre peticiones (cada una tendría un id distinto).
     * Para simular a un mismo invitado navegando, se envía siempre la misma cookie, como haría el navegador.
     */
    private function sameBrowserSession(): void
    {
        $this->withCookie(config('session.cookie'), str_repeat('a', 40));
    }

    private function lastEvent(string $type): Event
    {
        return Event::where('type', $type)->latest('id')->firstOrFail();
    }

    public function test_logged_in_customer_events_carry_user_id(): void
    {
        $laura = $this->user();
        $this->actingAs($laura);

        $this->post(route('cart.add', Product::doesntHave('variants')->first()));
        $this->get('/checkout');
        $this->post('/checkout', [
            'name' => 'Laura Prueba', 'address' => 'Calle Ficticia 1', 'city' => 'Madrid',
            'postal_code' => '28001', 'accept_prototype' => '1',
        ]);

        foreach (['cart.item_added', 'checkout.started', 'order.created'] as $type) {
            $this->assertSame($laura->id, $this->lastEvent($type)->user_id, "$type sin usuario");
        }
        $this->assertSame($laura->id, $this->lastEvent('order.created')->user->id);
    }

    public function test_guest_events_have_no_user(): void
    {
        $product = Product::doesntHave('variants')->first();
        $this->get(route('product.show', $product));
        $this->post(route('cart.add', $product));

        $this->assertNull($this->lastEvent('product.viewed')->user_id);
        $this->assertNull($this->lastEvent('cart.item_added')->user_id);
    }

    public function test_guest_events_are_adopted_on_login(): void
    {
        $this->sameBrowserSession();
        $product = Product::doesntHave('variants')->first();
        $this->get(route('product.show', $product));
        $this->post(route('cart.add', $product));

        $this->post(route('login.store'), ['email' => 'laura@example.com', 'password' => 'cliente1234']);

        $laura = $this->user();
        $this->assertSame($laura->id, $this->lastEvent('product.viewed')->user_id);
        $this->assertSame($laura->id, $this->lastEvent('cart.item_added')->user_id);
    }

    public function test_guest_events_are_adopted_on_register(): void
    {
        $this->sameBrowserSession();
        $this->post(route('cart.add', Product::doesntHave('variants')->first()));

        $this->post(route('register.store'), [
            'name' => 'Nuevo Cliente', 'email' => 'nuevo@example.com',
            'password' => 'cliente1234', 'password_confirmation' => 'cliente1234',
        ]);

        $this->assertSame($this->user('nuevo@example.com')->id, $this->lastEvent('cart.item_added')->user_id);
    }

    public function test_admin_does_not_adopt_guest_events(): void
    {
        $this->sameBrowserSession();
        $this->get(route('product.show', Product::doesntHave('variants')->first()));

        $this->post(route('login.store'), ['email' => 'admin@piquantum.test', 'password' => 'admin1234']);

        $this->assertNull($this->lastEvent('product.viewed')->user_id);
    }

    public function test_events_of_other_sessions_are_not_adopted(): void
    {
        // Carlos navega en su propia sesión...
        $this->actingAs($this->user('carlos@example.com'))->get(route('product.show', Product::doesntHave('variants')->first()));
        $carlosEvent = $this->lastEvent('product.viewed');

        // ...y un invitado distinto inicia sesión como Laura: no se queda con el evento de Carlos
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->post(route('login.store'), ['email' => 'laura@example.com', 'password' => 'cliente1234']);

        $this->assertSame($this->user('carlos@example.com')->id, $carlosEvent->fresh()->user_id);
    }

    public function test_item_added_records_real_quantity_when_stock_is_short(): void
    {
        $product = Product::where('sku', 'SAL-003')->firstOrFail();
        $product->update(['stock' => 2]);

        $this->post(route('cart.add', $product), ['quantity' => 5]);

        $event = $this->lastEvent('cart.item_added');
        $this->assertSame(2, $event->quantity);
        $this->assertSame(5, $event->payload['requested_quantity']);
    }

    public function test_no_event_when_nothing_could_be_added(): void
    {
        $product = Product::where('sku', 'SAL-003')->firstOrFail();
        $product->update(['stock' => 1]);

        $this->post(route('cart.add', $product));
        $this->post(route('cart.add', $product))->assertSessionHasErrors('cart');

        $this->assertSame(1, Event::where('type', 'cart.item_added')->count());
    }

    public function test_updating_and_removing_lines_log_events(): void
    {
        $this->actingAs($this->user());
        $product = Product::where('sku', 'SAL-002')->firstOrFail();
        $variant = $product->variants()->first();
        $key = "{$product->id}_{$variant->id}";

        $this->post(route('cart.add', $product), ['quantity' => 1, 'variant_id' => $variant->id]);

        $this->patch(route('cart.update', $key), ['quantity' => 3]);
        $updated = $this->lastEvent('cart.item_updated');

        $this->assertSame(1, $updated->previous_quantity);
        $this->assertSame(3, $updated->quantity);
        $this->assertSame($variant->id, $updated->product_variant_id);
        $this->assertSame($this->user()->id, $updated->user_id);

        $this->delete(route('cart.remove', $key));
        $this->assertSame(3, $this->lastEvent('cart.item_removed')->quantity);
    }

    public function test_setting_quantity_to_zero_logs_removal(): void
    {
        $product = Product::doesntHave('variants')->first();
        $this->post(route('cart.add', $product), ['quantity' => 2]);

        $this->patch(route('cart.update', (string) $product->id), ['quantity' => 0]);

        $this->assertSame(2, $this->lastEvent('cart.item_removed')->quantity);
        $this->assertFalse(Event::where('type', 'cart.item_updated')->exists());
    }

    public function test_backoffice_status_change_records_the_admin(): void
    {
        $order = Order::where('status', Order::PENDING_PREPARATION)->first()
            ?? tap(Order::firstOrFail())->update(['status' => Order::PENDING_PREPARATION]);
        $admin = $this->user('admin@piquantum.test');

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => Order::SHIPPED]);

        $this->assertSame($admin->id, $this->lastEvent('order.status_changed')->user_id);
    }
}
