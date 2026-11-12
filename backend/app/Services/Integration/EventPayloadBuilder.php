<?php

namespace App\Services\Integration;

use App\Models\Customer;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SupportTicket;
use App\Services\EventLogger;

/**
 * Monta el `data` de cada evento que la tienda envía a n8n
 * (contrato: SIE-Tarea2/integracion/contrato.md, apartado 6).
 *
 * Devuelve null si el evento no se envía según las reglas del contrato.
 * Los datos se leen de la base de datos en el momento de construir el `data`:
 * como se llama después del commit, el pedido ya está en su estado final.
 */
class EventPayloadBuilder
{
    /** Los únicos tipos que se envían a n8n. */
    public const TYPES = [
        EventLogger::ORDER_CREATED,
        EventLogger::PAYMENT_SIMULATED,
        EventLogger::ORDER_STATUS_CHANGED,
        EventLogger::SUPPORT_REQUESTED,
    ];

    /** order.status_changed: lista blanca de orígenes (apartado 6.3). Solo el panel de admin de la tienda. */
    public const STATUS_CHANGE_ORIGINS = ['backoffice'];

    /**
     * ¿Se envía este evento según el contrato? Solo mira datos del propio evento,
     * así que se puede llamar al registrarlo, dentro de la transacción.
     */
    public function shouldSend(Event $event): bool
    {
        return match ($event->type) {
            EventLogger::ORDER_CREATED, EventLogger::SUPPORT_REQUESTED => true,
            // Evita duplicados con payment.simulated y bucles con la integración (apartado 6.3)
            EventLogger::ORDER_STATUS_CHANGED => in_array($event->origin, self::STATUS_CHANGE_ORIGINS, true),
            // Solo Redsys de pruebas: las transferencias simuladas no se envían (apartado 6.2)
            EventLogger::PAYMENT_SIMULATED => $event->payment_id !== null
                && Payment::whereKey($event->payment_id)->value('method') === Payment::METHOD_REDSYS,
            default => false,
        };
    }

    /**
     * El `data` del evento, o null si no se envía. Se llama después del commit, con el estado final.
     */
    public function build(Event $event): ?array
    {
        if (! $this->shouldSend($event)) {
            return null;
        }

        return match ($event->type) {
            EventLogger::ORDER_CREATED => $this->orderCreated($event),
            EventLogger::PAYMENT_SIMULATED => $this->paymentSimulated($event),
            EventLogger::ORDER_STATUS_CHANGED => $this->orderStatusChanged($event),
            EventLogger::SUPPORT_REQUESTED => $this->supportRequested($event),
            default => null,
        };
    }

    private function orderCreated(Event $event): ?array
    {
        $order = $this->loadOrder($event);

        return $order ? $this->orderSnapshot($order) : null;
    }

    private function paymentSimulated(Event $event): ?array
    {
        $payment = Payment::find($event->payment_id);
        $order = $this->loadOrder($event);
        if (! $payment || ! $order) {
            return null;
        }

        return $this->orderSnapshot($order) + [
            'payment' => [
                'reference' => $payment->reference,
                'method' => $payment->method,
                'status' => $payment->status,
                'amount' => $this->money($payment->amount),
                'origin' => $event->origin,
            ],
        ];
    }

    private function orderStatusChanged(Event $event): ?array
    {
        $order = $this->loadOrder($event);
        if (! $order) {
            return null;
        }

        return $this->orderSnapshot($order) + [
            'change' => [
                'previous_status' => $event->previous_status,
                'status' => $event->status,
                'origin' => $event->origin,
            ],
        ];
    }

    private function supportRequested(Event $event): ?array
    {
        $ticket = $event->support_ticket_id ? SupportTicket::find($event->support_ticket_id) : null;
        if (! $ticket) {
            return null;
        }

        $order = $ticket->order_id ? Order::find($ticket->order_id) : null;

        return [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'message' => $ticket->message,
                'status' => $ticket->status,
            ],
            'contact' => [
                'name' => $ticket->name,
                'email' => $ticket->email,
            ],
            // Invitado sin cuenta → null
            'customer_id' => Customer::where('email', $ticket->email)->value('id'),
            'order_reference' => $order?->reference,
        ];
    }

    private function loadOrder(Event $event): ?Order
    {
        return $event->order_id
            ? Order::with(['customer', 'items.product'])->find($event->order_id)
            : null;
    }

    /**
     * Bloques comunes de los eventos de pedido: la "foto" completa del pedido (apartado 6.1).
     * También la usa la sincronización inicial (GET /api/integration/pedidos). El pedido debe venir
     * con `customer` e `items.product` cargados.
     */
    public function orderSnapshot(Order $order): array
    {
        return [
            'order' => [
                'reference' => $order->reference,
                'status' => $order->status,
                'currency' => 'EUR',
                'subtotal' => $this->money($order->subtotal),
                'discount' => $this->money($order->discount),
                'discount_code' => $order->discount_code,
                'shipping' => $this->money($order->shipping),
                'tax' => $this->money($order->tax),
                'total' => $this->money($order->total),
            ],
            'customer' => [
                'id' => $order->customer?->id,
                'name' => $order->customer?->name,
                'email' => $order->customer?->email,
            ],
            'shipping_address' => [
                'name' => $order->shipping_name,
                'address' => $order->shipping_address,
                'city' => $order->shipping_city,
                'postal_code' => $order->shipping_postal_code,
                'country' => $order->shipping_country,
            ],
            'lines' => $order->items->map(fn (OrderItem $item) => [
                'product_code' => $this->productCode($item->product?->sku, $item->variant_size),
                'sku' => $item->product?->sku,
                'size' => $item->variant_size,
                'name' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => $this->money($item->unit_price),
                'line_total' => $this->money($item->line_total),
            ])->values()->all(),
        ];
    }

    /**
     * Código que entiende el back-office (apartado 5): "SAL-002" + "100 ml" → "SAL-002-100ML";
     * sin tamaño, el SKU tal cual.
     */
    public function productCode(?string $sku, ?string $size): ?string
    {
        if ($sku === null) {
            return null;
        }
        if ($size === null || trim($size) === '') {
            return $sku;
        }

        return $sku.'-'.strtoupper(str_replace(' ', '', $size));
    }

    /** Las columnas decimal llegan como string: se envían como número con 2 decimales. */
    private function money(mixed $value): float
    {
        return round((float) $value, 2);
    }
}
