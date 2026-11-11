<?php

namespace App\Services\Integration;

use App\Models\IntegrationInboxEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Aplica en la tienda los cambios que el operario hace en el back-office (n8n → POST /api/integration/sync).
 * Contrato: SIE-Tarea2/integracion/contrato.md, apartado 8.
 *
 * Idempotencia: el event_id se apunta en integration_inbox en la MISMA transacción que el cambio.
 * Si llegan dos avisos iguales a la vez, el índice único deja pasar solo uno; el otro es un duplicado.
 * Un duplicado repite la respuesta original (200 si se aplicó; 404/422 si se rechazó), para que n8n
 * no le diga al back-office que algo rechazado se ha sincronizado.
 */
class IntegrationSync
{
    public const ORIGIN = 'integracion';

    public function __construct(
        private OrderService $orders,
        private EventPayloadBuilder $builder,
    ) {}

    /**
     * @return array{0: int, 1: array} [código HTTP, cuerpo JSON]
     */
    public function handle(array $payload): array
    {
        $eventId = is_string($payload['event_id'] ?? null) ? trim($payload['event_id']) : '';

        if ($eventId !== '' && strlen($eventId) <= 100 && ($previous = IntegrationInboxEntry::where('event_id', $eventId)->first())) {
            return $this->duplicate($previous);
        }

        $validator = Validator::make($payload, [
            'event_id' => ['required', 'string', 'max:100'],
            'change_type' => ['required', 'string', 'in:ORDER_STATUS,STOCK'],
            'order_ref' => ['required_if:change_type,ORDER_STATUS', 'nullable', 'string', 'max:40'],
            'new_status' => ['required_if:change_type,ORDER_STATUS', 'nullable', 'string', 'in:'.implode(',', array_keys(Order::STATUS_LABELS))],
            'product_code' => ['required_if:change_type,STOCK', 'nullable', 'string', 'max:60'],
            'new_stock' => ['required_if:change_type,STOCK', 'nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            $body = ['result' => 'invalid', 'event_id' => $eventId ?: null, 'message' => 'Datos no válidos.', 'errors' => $validator->errors()->toArray()];
            Log::warning("Integración (sync): aviso rechazado por datos no válidos ({$eventId}).", $body['errors']);

            // Sin un event_id usable no se puede apuntar en la bandeja de entrada
            if ($validator->errors()->has('event_id')) {
                return [422, $body];
            }

            return $this->recordOnly($eventId, $payload, 422, $body);
        }

        try {
            return DB::transaction(function () use ($eventId, $payload) {
                // 1) Primero se apunta el event_id (decisión 1, opción A): el índice único bloquea al duplicado
                $entry = IntegrationInboxEntry::create([
                    'event_id' => $eventId,
                    'change_type' => $payload['change_type'],
                    'entity_ref' => $payload['change_type'] === 'STOCK' ? strtoupper(trim($payload['product_code'])) : trim($payload['order_ref']),
                    'result' => 'processing',
                    'http_status' => 0,
                    'payload' => $payload,
                    'response' => [],
                ]);

                // 2) Después se aplica el cambio
                [$status, $body] = $payload['change_type'] === 'ORDER_STATUS'
                    ? $this->applyOrderStatus($eventId, trim($payload['order_ref']), $payload['new_status'])
                    : $this->applyStock($eventId, strtoupper(trim($payload['product_code'])), (int) $payload['new_stock']);

                // 3) Y se guarda el resultado (también los rechazos: decisión 2, opción B)
                $entry->update(['result' => $body['result'], 'http_status' => $status, 'response' => $body]);

                return [$status, $body];
            });
        } catch (UniqueConstraintViolationException) {
            // Otra petición con el mismo event_id se ha adelantado: se responde como ella
            return $this->duplicate(IntegrationInboxEntry::where('event_id', $eventId)->firstOrFail());
        }
    }

    private function applyOrderStatus(string $eventId, string $orderRef, string $newStatus): array
    {
        $order = Order::where('reference', $orderRef)->lockForUpdate()->first();
        if (! $order) {
            return $this->notFound($eventId, "No existe el pedido {$orderRef}.");
        }

        if ($order->status === $newStatus) {
            return [200, ['result' => 'unchanged', 'event_id' => $eventId, 'order_ref' => $orderRef, 'status' => $newStatus]];
        }

        if (! in_array($newStatus, $order->allowedTransitions(), true)) {
            Log::warning("Integración (sync): transición no permitida {$orderRef} {$order->status} → {$newStatus} ({$eventId}).");

            return [422, [
                'result' => 'invalid_transition',
                'event_id' => $eventId,
                'order_ref' => $orderRef,
                'current_status' => $order->status,
                'requested_status' => $newStatus,
                'allowed_next' => $order->allowedTransitions(),
            ]];
        }

        // Repone stock al cancelar y envía el correo al pasar a «enviado». Con origen «integracion»
        // la bandeja de salida no lo reenvía a n8n (no está en la lista blanca): no hay bucle.
        $previous = $order->status;
        $this->orders->changeStatus($order, $newStatus, self::ORIGIN);

        return [200, ['result' => 'updated', 'event_id' => $eventId, 'order_ref' => $orderRef, 'previous_status' => $previous, 'status' => $newStatus]];
    }

    private function applyStock(string $eventId, string $productCode, int $newStock): array
    {
        $target = $this->resolveProductCode($productCode);
        if (! $target) {
            return $this->notFound($eventId, "No existe el producto {$productCode}.");
        }

        $previous = (int) $target->stock;
        if ($previous === $newStock) {
            return [200, ['result' => 'unchanged', 'event_id' => $eventId, 'product_code' => $productCode, 'stock' => $newStock]];
        }

        // Valor absoluto, no una diferencia: repetir la llamada no cambia nada
        $target->update(['stock' => $newStock]);
        Log::info("Integración (sync): stock de {$productCode} {$previous} → {$newStock} ({$eventId}).");

        return [200, ['result' => 'updated', 'event_id' => $eventId, 'product_code' => $productCode, 'previous_stock' => $previous, 'stock' => $newStock]];
    }

    /**
     * product_code (contrato, apartado 5) → tamaño o producto. "SAL-004" → producto sin tamaños;
     * "SAL-002-100ML" → el tamaño «100 ml» de SAL-002. Un SKU con tamaños sin indicar el tamaño no vale.
     */
    private function resolveProductCode(string $code): Product|ProductVariant|null
    {
        $product = Product::where('sku', $code)->lockForUpdate()->first();
        if ($product) {
            return $product->variants()->exists() ? null : $product;
        }

        $product = Product::where('sku', Str::beforeLast($code, '-'))->first();
        if (! $product) {
            return null;
        }

        return $product->variants()->lockForUpdate()->get()
            ->first(fn (ProductVariant $v) => $this->builder->productCode($product->sku, $v->size) === $code);
    }

    private function notFound(string $eventId, string $message): array
    {
        Log::warning("Integración (sync): {$message} ({$eventId}).");

        return [404, ['result' => 'not_found', 'event_id' => $eventId, 'message' => $message]];
    }

    /** Aviso rechazado antes de aplicar nada: se apunta igualmente con su respuesta. */
    private function recordOnly(string $eventId, array $payload, int $status, array $body): array
    {
        try {
            IntegrationInboxEntry::create([
                'event_id' => $eventId,
                'change_type' => is_string($payload['change_type'] ?? null) ? Str::limit($payload['change_type'], 20, '') : null,
                'entity_ref' => null,
                'result' => $body['result'],
                'http_status' => $status,
                'payload' => $payload,
                'response' => $body,
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->duplicate(IntegrationInboxEntry::where('event_id', $eventId)->firstOrFail());
        }

        return [$status, $body];
    }

    /** Repite la respuesta original: 200 si se aplicó, el mismo 404/422 si se rechazó. */
    private function duplicate(IntegrationInboxEntry $entry): array
    {
        return [$entry->http_status, [
            'result' => 'duplicate',
            'event_id' => $entry->event_id,
            'original_result' => $entry->result,
            'original_response' => $entry->response,
        ]];
    }
}
