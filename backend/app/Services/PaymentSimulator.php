<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pago por TRANSFERENCIA SIMULADA (siempre se aprueba). El pago con tarjeta
 * se hace en el TPV Virtual de Redsys (RedsysGateway), no aquí.
 */
class PaymentSimulator
{
    public function __construct(
        private OrderService $orders,
        private EventLogger $events,
    ) {}

    /**
     * Registra la transferencia, su evento, la factura y los cambios de estado, todo en una
     * transacción. El pedido se bloquea para que dos peticiones simultáneas no puedan pagarlo dos veces.
     *
     * @throws CheckoutException si el pedido ya no está pendiente de pago
     */
    public function pay(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
            // Releer el pedido bloqueado: si otra petición lo acaba de pagar, aquí ya se ve
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->status !== Order::CREATED) {
                throw new CheckoutException('Este pedido ya no está pendiente de pago.');
            }

            $payment = $order->payments()->create([
                'method' => Payment::METHOD_TRANSFER,
                'status' => Payment::APPROVED,
                'amount' => $order->total,
                'reference' => 'SIM-'.Str::upper(Str::random(10)),
            ]);

            $this->events->log(EventLogger::PAYMENT_SIMULATED, [
                'reference' => $order->reference,
                'payment_reference' => $payment->reference,
                'gateway' => 'simulado',
                'method' => $payment->method,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
            ], $order->id);

            $this->orders->markPaid($order);

            return $payment;
        });
    }
}
