<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
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

    /**
     * Registra el intento de pago, su evento y, si se aprueba, los cambios de estado,
     * todo en una transacción. El pedido se bloquea para que dos peticiones
     * simultáneas no puedan pagarlo dos veces.
     *
     * @throws CheckoutException si el pedido ya no está pendiente de pago
     */
    public function pay(Order $order, string $method, ?string $cardNumber = null): Payment
    {
        $digits = $cardNumber ? preg_replace('/\D/', '', $cardNumber) : null;
        $approved = ! ($method === 'card' && $digits === self::DECLINED_CARD);

        return DB::transaction(function () use ($order, $method, $digits, $approved) {
            // Releer el pedido bloqueado: si otra petición lo acaba de pagar, aquí ya se ve
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->status !== Order::CREATED) {
                throw new CheckoutException('Este pedido ya no está pendiente de pago.');
            }

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
        });
    }
}
