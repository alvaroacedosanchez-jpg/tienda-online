<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;

/**
 * Registra eventos de negocio en la tabla `events`.
 * Otro sistema (Tarea 2) puede consumirlos vía /admin/eventos.json o /admin/eventos.csv.
 */
class EventLogger
{
    public const PRODUCT_VIEWED = 'product.viewed';
    public const CART_ITEM_ADDED = 'cart.item_added';
    public const CART_ITEM_UPDATED = 'cart.item_updated';
    public const CART_ITEM_REMOVED = 'cart.item_removed';
    public const CHECKOUT_STARTED = 'checkout.started';
    public const ORDER_CREATED = 'order.created';
    public const PAYMENT_SIMULATED = 'payment.simulated';
    public const ORDER_STATUS_CHANGED = 'order.status_changed';
    public const SUPPORT_REQUESTED = 'support.requested';
    public const INVOICE_ISSUED = 'invoice.issued';

    public function log(string $type, array $payload = [], ?int $orderId = null): Event
    {
        $sessionId = null;
        if (app()->bound('request') && request()->hasSession()) {
            $sessionId = request()->session()->getId();
        }

        return Event::create([
            'type' => $type,
            'session_id' => $sessionId,
            // Quién provocó el evento: el cliente conectado, el admin en el back-office o null si es un invitado
            'user_id' => auth()->id(),
            'order_id' => $orderId,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Al iniciar sesión o registrarse, asigna al usuario los eventos que hizo como
     * invitado en esa misma visita. Hay que llamarlo con el id de sesión ANTERIOR
     * a session()->regenerate(), porque después cambia.
     */
    public function adoptGuestEvents(string $guestSessionId, User $user): int
    {
        return Event::where('session_id', $guestSessionId)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);
    }
}
