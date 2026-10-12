<?php

namespace Tests\Feature;

use App\Mail\OrderPaidMail;
use App\Mail\OrderShippedMail;
use App\Mail\SupportRequestNotificationMail;
use App\Mail\SupportRequestReceivedMail;
use App\Mail\WelcomeMail;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\FakesRedsys;
use Tests\TestCase;

/** Correos de la tienda: qué evento envía qué correo, a quién, y que un fallo no rompe la compra. */
class EmailTest extends TestCase
{
    use FakesRedsys;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['shop.notify_email' => 'tienda@piquantum.test']);
    }

    private function laura(): User
    {
        return User::where('email', 'laura@example.com')->firstOrFail();
    }

    /** Pedido de Laura pendiente de pago. */
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

    public function test_welcome_mail_on_register(): void
    {
        Mail::fake();

        $this->post(route('register.store'), [
            'name' => 'Nuevo Cliente', 'email' => 'nuevo@example.com',
            'password' => 'cliente1234', 'password_confirmation' => 'cliente1234',
        ]);

        Mail::assertSent(WelcomeMail::class, fn (WelcomeMail $mail) => $mail->hasTo('nuevo@example.com'));
    }

    public function test_paid_by_transfer_sends_invoice_attached(): void
    {
        Mail::fake();
        $order = $this->createdOrder();

        $this->post(route('orders.pay.store', $order), ['method' => 'transfer']);

        $invoice = $order->fresh()->invoice;
        Mail::assertSent(OrderPaidMail::class, function (OrderPaidMail $mail) use ($invoice) {
            // El PDF cambia en cada generación (fecha interna), así que se comprueba nombre y tipo del adjunto
            $attachment = $mail->attachments()[0] ?? null;

            return $mail->hasTo('laura@example.com')
                && $attachment?->as === $invoice->number.'.pdf'
                && $attachment?->mime === 'application/pdf';
        });
        $this->assertStringStartsWith('%PDF', $invoice->pdf()->output());
    }

    public function test_paid_by_redsys_sends_mail_and_declined_does_not(): void
    {
        Mail::fake();
        $order = $this->createdOrder();

        $declined = $this->startRedsysPayment($order);
        $this->notifyRedsys($declined, '0190');
        Mail::assertNotSent(OrderPaidMail::class);

        // El rechazo cancela el pedido; el cliente reintenta con un pedido nuevo
        $retry = $this->createdOrder();
        $this->notifyRedsys($this->startRedsysPayment($retry), '0000');
        Mail::assertSent(OrderPaidMail::class, 1);
    }

    public function test_paid_mail_renders_order_and_invoice(): void
    {
        $order = $this->createdOrder();
        $this->post(route('orders.pay.store', $order), ['method' => 'transfer']);
        $order->refresh();

        $html = (new OrderPaidMail($order))->render();

        $this->assertStringContainsString($order->reference, $html);
        $this->assertStringContainsString($order->invoice->number, $html);
        $this->assertStringContainsString('Salsa Verde', $html);
        $this->assertStringContainsString('prototipo académico', $html);
    }

    public function test_shipped_mail_only_when_status_is_shipped(): void
    {
        Mail::fake();
        $order = $this->createdOrder();
        $this->post(route('orders.pay.store', $order), ['method' => 'transfer']); // → pendiente de preparación
        $admin = User::where('email', 'admin@piquantum.test')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => Order::INCIDENT]);
        Mail::assertNotSent(OrderShippedMail::class);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => Order::PENDING_PREPARATION]);
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => Order::SHIPPED]);
        Mail::assertSent(OrderShippedMail::class, fn (OrderShippedMail $mail) => $mail->hasTo('laura@example.com'));
    }

    public function test_support_request_sends_receipt_and_shop_notification(): void
    {
        Mail::fake();

        $this->post('/soporte', [
            'name' => 'Marta Test', 'email' => 'marta@example.com',
            'subject' => 'Duda', 'message' => 'Mensaje de prueba largo.',
        ]);

        Mail::assertSent(SupportRequestReceivedMail::class, fn ($mail) => $mail->hasTo('marta@example.com'));
        Mail::assertSent(SupportRequestNotificationMail::class, fn ($mail) => $mail->hasTo('tienda@piquantum.test')
            && $mail->hasReplyTo('marta@example.com'));
    }

    public function test_smtp_failure_does_not_break_the_payment(): void
    {
        $order = $this->createdOrder();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));
        Log::shouldReceive('error')->once()->withArgs(fn ($message) => str_contains($message, 'SMTP caído'));

        $this->post(route('orders.pay.store', $order), ['method' => 'transfer'])->assertRedirect(route('orders.show', $order));

        $this->assertSame(Order::PENDING_PREPARATION, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->invoice);
    }

    public function test_no_paid_mail_when_the_payment_transaction_is_rolled_back(): void
    {
        Mail::fake();
        $order = $this->createdOrder();

        // Falla la factura, que es el último paso: se revierte el pago y el correo no debe salir
        Invoice::creating(fn () => throw new \RuntimeException('Fallo simulado al emitir la factura'));
        $this->post(route('orders.pay.store', $order), ['method' => 'transfer'])->assertServerError();

        Mail::assertNotSent(OrderPaidMail::class);
        $this->assertSame(Order::CREATED, $order->fresh()->status);
    }
}
