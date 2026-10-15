<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentSimulator
{
    public function __construct(
        private OrderService $orders,
        private EventLogger $events,
    ) {}

    public function pay(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
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

            $this->events->log(
                EventLogger::PAYMENT_SIMULATED,
                [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                    'amount' => (float) $payment->amount,
                ],
                [
                    'reference' => $order->reference,
                    'payment_reference' => $payment->reference,
                ]
            );

            $this->orders->markPaid($order);

            return $payment;
        });
    }
}
