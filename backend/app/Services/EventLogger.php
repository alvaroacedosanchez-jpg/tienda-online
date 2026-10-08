<?php

namespace App\Services;

use App\Models\Event;

/**
 * Registra eventos de negocio en la tabla `events`.
 * Otro sistema (Tarea 2) puede consumirlos vía /admin/eventos.json o /admin/eventos.csv.
 */
class EventLogger
{
    public const PRODUCT_VIEWED = 'product.viewed';
    public const CART_ITEM_ADDED = 'cart.item_added';
    public const CHECKOUT_STARTED = 'checkout.started';
    public const ORDER_CREATED = 'order.created';
    public const PAYMENT_SIMULATED = 'payment.simulated';
    public const ORDER_STATUS_CHANGED = 'order.status_changed';
    public const SUPPORT_REQUESTED = 'support.requested';

    public function log(string $type, array $payload = [], ?int $orderId = null): Event
    {
        $sessionId = null;
        if (app()->bound('request') && request()->hasSession()) {
            $sessionId = request()->session()->getId();
        }

        return Event::create([
            'type' => $type,
            'session_id' => $sessionId,
            'order_id' => $orderId,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
