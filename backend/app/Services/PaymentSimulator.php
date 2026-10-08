<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Pago SIMULADO. No se conecta con ninguna pasarela y no se guarda el número de tarjeta.
 * Tarjeta de prueba aprobada: 4242 4242 4242 4242. Rechazada: 4000 0000 0000 0002.
 */
class PaymentSimulator
{
    public const DECLINED_CARD = '4000000000000002';

    public function __construct(
        private OrderService $orders,
        private EventLogger $events,
    ) {}

    public function pay(Order $order, string $method, ?string $cardNumber = null): Payment
    {
        $digits = $cardNumber ? preg_replace('/\D/', '', $cardNumber) : null;
        $approved = ! ($method === 'card' && $digits === self::DECLINED_CARD);

        $payment = $order->payments()->create([
            'method' => $method,
            'status' => $approved ? 'approved' : 'declined',
            'amount' => $order->total,
            'card_last4' => $digits ? substr($digits, -4) : null,
            'reference' => 'SIM-'.Str::upper(Str::random(10)),
        ]);

        $this->events->log(EventLogger::PAYMENT_SIMULATED, [
            'reference' => $order->reference,
            'payment_reference' => $payment->reference,
            'method' => $method,
            'status' => $payment->status,
            'amount' => (float) $payment->amount,
        ], $order->id);

        if ($approved) {
            $this->orders->changeStatus($order, Order::PAID, 'payment');
            $this->orders->changeStatus($order, Order::PENDING_PREPARATION, 'payment');
        }

        return $payment;
    }
}
