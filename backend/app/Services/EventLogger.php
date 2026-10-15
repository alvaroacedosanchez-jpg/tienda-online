<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use InvalidArgumentException;

/**
 * Registra eventos de negocio en la tabla `events`.
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

    /**
     * Columnas permitidas en la tabla `events`.
     */
    protected const ALLOWED_COLUMNS = [
        'order_id',
        'product_id',
        'product_variant_id',
        'payment_id',
        'invoice_id',
        'support_ticket_id',
        'quantity',
        'previous_quantity',
        'unit_price',
        'amount',
        'status',
        'previous_status',
        'origin',
        'payment_method',
        'gateway',
        'response_code',
        'discount_code',
    ];

    /**
     * Registra un evento con columnas tipadas y detalles opcionales en payload.
     */
    public static function log(string $type, array $columns = [], array$details = []): Event
    {
        // Validar lista blanca de columnas (2º argumento)
        foreach (array_keys($columns) as$column) {
            if (! in_array($column, self::ALLOWED_COLUMNS, true)) {
                throw new InvalidArgumentException("Columna no permitida en events: {$column}");
            }
        }

        $sessionId = null;
        if (app()->bound('request') && request()->hasSession()) {
            $sessionId = request()->session()->getId();
        }

        $data = array_merge($columns, [
            'type'        => $type,
            'session_id'  => $sessionId,
            'user_id'     => auth()->id(),
            'payload'     => ! empty($details) ?$details : null,
            'occurred_at' => now(),
        ]);

        return Event::create($data);
    }

    /**
     * Asigna a un usuario los eventos que hizo como invitado en esa misma visita.
     */
    public static function adoptGuestEvents(string $guestSessionId, User$user): int
    {
        return Event::where('session_id', $guestSessionId)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);
    }
}