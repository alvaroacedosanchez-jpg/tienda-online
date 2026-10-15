<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Redsys\Utils;
use Tests\Concerns\FakesRedsys;
use Tests\TestCase;

/** Pago con tarjeta en el TPV Virtual de Redsys (entorno de pruebas). */
class RedsysPaymentTest extends TestCase
{
    use FakesRedsys;
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

    /** Pedido de Laura pendiente de pago (5,90 € + 4,95 € de envío = 10,85 €). */
    private function createdOrder(): Order
    {
        $this->actingAs($this->laura());
        $this->post(route('cart.add', Product::where('sku', 'SAL-003')->firstOrFail()));
        $this->post('/checkout', [
            'name' => 'Laura Prueba', 'address' => 'Calle Ficticia 1', 'city' => 'Madrid',
            'postal_code' => '28001', 'accept_prototype' => '1',
        ]);

        return Order::latest('id')->firstOrFail();
    }

    public function test_paying_with_card_redirects_to_redsys_test_tpv_with_signed_form(): void
    {
        $order = $this->createdOrder();

        $response = $this->post(route('orders.pay.store', $order), ['method' => 'redsys'])->assertOk();

        $payment = $order->payments()->firstOrFail();
        $this->assertSame(Payment::PENDING, $payment->status);
        $this->assertMatchesRegularExpression('/^\d{12}$/', $payment->gateway_order, 'Número de pedido válido para Redsys');

        $response->assertSee('action="https://sis-t.redsys.es:25443/sis/realizarPago"', false)
            ->assertSee('name="Ds_MerchantParameters"', false)
            ->assertSee('name="Ds_Signature"', false);

        preg_match('/name="Ds_MerchantParameters" value="([^"]+)"/', $response->getContent(), $m);
        $sent = json_decode(Utils::base64_url_decode_safe(html_entity_decode($m[1])), true);
        $this->assertSame('1085', $sent['DS_MERCHANT_AMOUNT']);
        $this->assertSame($payment->gateway_order, $sent['DS_MERCHANT_ORDER']);
        $this->assertSame(route('redsys.notify'), $sent['DS_MERCHANT_MERCHANTURL']);
        $this->assertSame(route('redsys.ok', $payment), $sent['DS_MERCHANT_URLOK']);
        $this->assertSame(Order::CREATED, $order->fresh()->status, 'Hasta que responda Redsys, el pedido no está pagado');
    }

    public function test_approved_notification_pays_the_order_and_issues_invoice(): void
    {
        $order = $this->createdOrder();
        $payment = $this->startRedsysPayment($order);

        $this->notifyRedsys($payment, '0000')->assertOk();

        $payment->refresh();
        $this->assertSame(Payment::APPROVED, $payment->status);
        $this->assertSame('0000', $payment->response_code);
        $this->assertSame('123456', $payment->authorisation_code);
        $this->assertSame('0003', $payment->card_last4);
        $this->assertSame(Order::PENDING_PREPARATION, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->invoice);

        $event = Event::where('type', 'payment.simulated')->latest('id')->firstOrFail();
        $this->assertSame('redsys', $event->payment->gateway());
        $this->assertSame('0000', $event->payment->response_code);
    }

    public function test_declined_notification_cancels_order_without_invoice(): void
    {
        $order = $this->createdOrder();
        $payment = $this->startRedsysPayment($order);

        $this->notifyRedsys($payment, '0190')->assertOk();

        $this->assertSame(Payment::DECLINED, $payment->fresh()->status);
        $this->assertSame(Order::CANCELLED, $order->fresh()->status);
        $this->assertNull($order->fresh()->invoice);
    }

    public function test_cancel_in_redsys_cancels_order_restores_stock_and_cart(): void
    {
        $product = Product::where('sku', 'SAL-003')->firstOrFail();
        $stockBefore = $product->stock;
        $order = $this->createdOrder();
        $this->assertSame($stockBefore - 1, $product->fresh()->stock, 'Al crear el pedido se descuenta el stock');
        $this->assertSame(0, $this->laura()->cart?->items()->count() ?? 0, 'El pedido vacía el carrito');

        $payment = $this->startRedsysPayment($order);
        $this->get(route('redsys.ko', $payment))
            ->assertRedirect(route('cart.show'))
            ->assertSessionHasErrors('cart');

        $this->assertSame(Order::CANCELLED, $order->fresh()->status);
        $this->assertSame(Payment::DECLINED, $payment->fresh()->status);
        $this->assertSame($stockBefore, $product->fresh()->stock, 'El stock vuelve a estar disponible');
        $this->assertNull($order->fresh()->invoice);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->assertSame('cancelado', Event::where('type', 'order.status_changed')->latest('id')->first()->status);
    }

    public function test_declined_notification_restores_stock_and_cart(): void
    {
        $product = Product::where('sku', 'SAL-003')->firstOrFail();
        $stockBefore = $product->stock;
        $order = $this->createdOrder();

        $this->notifyRedsys($this->startRedsysPayment($order), '9915')->assertOk();

        $this->assertSame(Order::CANCELLED, $order->fresh()->status);
        $this->assertSame($stockBefore, $product->fresh()->stock);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id]);
    }

    public function test_url_ko_after_approval_changes_nothing(): void
    {
        $order = $this->createdOrder();
        $payment = $this->startRedsysPayment($order);
        $this->notifyRedsys($payment, '0000');

        $this->get(route('redsys.ko', $payment))->assertRedirect(route('orders.show', $order));

        $this->assertSame(Order::PENDING_PREPARATION, $order->fresh()->status);
        $this->assertSame(Payment::APPROVED, $payment->fresh()->status);
    }

    public function test_approval_arriving_for_a_cancelled_order_is_flagged_as_incident(): void
    {
        $order = $this->createdOrder();
        $first = $this->startRedsysPayment($order);
        $second = $this->startRedsysPayment($order);

        $this->get(route('redsys.ko', $second));
        $this->assertSame(Order::CANCELLED, $order->fresh()->status);

        $this->assertSame(Payment::PENDING, $first->fresh()->status);
        $this->notifyRedsys($first, '0000')->assertOk();

        $this->assertSame(Payment::APPROVED, $first->fresh()->status);
        $this->assertSame(Order::INCIDENT, $order->fresh()->status, 'Cobro sin pedido activo: incidencia para devolverlo');
        $this->assertNull($order->fresh()->invoice);
    }

    public function test_forged_signature_is_rejected_and_nothing_changes(): void
    {
        $order = $this->createdOrder();
        $payment = $this->startRedsysPayment($order);

        $forged = $this->redsysResponse($payment, '0000');
        $forged['Ds_Signature'] = 'firma-inventada';

        $this->post(route('redsys.notify'), $forged)->assertStatus(400);

        $this->assertSame(Payment::PENDING, $payment->fresh()->status);
        $this->assertSame(Order::CREATED, $order->fresh()->status);
    }

    public function test_amount_mismatch_and_unknown_order_are_rejected(): void
    {
        $order = $this->createdOrder();
        $payment = $this->startRedsysPayment($order);

        $this->notifyRedsys($payment, '0000', ['Ds_Amount' => '1'])->assertStatus(400);
        $this->notifyRedsys($payment, '0000', ['Ds_Order' => '999999999999'])->assertStatus(400);

        $this->assertSame(Payment::PENDING, $payment->fresh()->status);
        $this->assertSame(Order::CREATED, $order->fresh()->status);
    }

    public function test_duplicated_notification_is_processed_once(): void
    {
        $order = $this->createdOrder();
        $payment = $this->startRedsysPayment($order);

        $this->notifyRedsys($payment)->assertOk();
        $this->notifyRedsys($payment)->assertOk();

        $this->get(route('redsys.ok', $payment).'?'.http_build_query($this->redsysResponse($payment)));

        $this->assertSame(1, Invoice::where('order_id', $order->id)->count());
        $this->assertSame(1, Event::where('type', 'payment.simulated')->where('order_id', $order->id)->count());
    }

    public function test_return_to_url_ok_with_signed_parameters_settles_the_payment(): void
    {
        $order = $this->createdOrder();
        $payment = $this->startRedsysPayment($order);

        $this->get(route('redsys.ok', $payment).'?'.http_build_query($this->redsysResponse($payment)))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('status');

        $this->assertSame(Order::PENDING_PREPARATION, $order->fresh()->status);
    }

    public function test_return_without_parameters_only_shows_waiting_message(): void
    {
        $order = $this->createdOrder();
        $payment = $this->startRedsysPayment($order);

        $this->get(route('redsys.ok', $payment))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'esperando la confirmación'));

        $this->assertSame(Payment::PENDING, $payment->fresh()->status, 'Una urlOk sin firmar no cambia nada');
    }

    public function test_other_users_and_admin_cannot_pay_or_see_return(): void
    {
        $order = $this->createdOrder();
        $payment = $this->startRedsysPayment($order);

        $this->actingAs(User::where('email', 'carlos@example.com')->firstOrFail());
        $this->post(route('orders.pay.store', $order), ['method' => 'redsys'])->assertNotFound();
        $this->get(route('redsys.ok', $payment))->assertNotFound();

        $this->actingAs(User::where('email', 'admin@piquantum.test')->firstOrFail());
        $this->get(route('redsys.ok', $payment))->assertRedirect(route('admin.dashboard'));
    }

    public function test_transfer_is_still_available_as_simulated_method(): void
    {
        $order = $this->createdOrder();

        $this->post(route('orders.pay.store', $order), ['method' => 'transfer'])->assertRedirect(route('orders.show', $order));

        $this->assertSame(Order::PENDING_PREPARATION, $order->fresh()->status);
        $this->assertSame(Payment::METHOD_TRANSFER, $order->payments()->firstOrFail()->method);
    }
}