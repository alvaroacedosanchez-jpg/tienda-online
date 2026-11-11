<?php

namespace App\Services\Integration;

use App\Models\Event;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Bandeja de salida hacia n8n (patrón outbox).
 *
 * 1. record(): dentro de la transacción del cambio de negocio se guarda la fila (pendiente, sin cuerpo).
 *    Si la transacción se deshace, la fila desaparece con ella: nunca hay un aviso sin pedido ni un pedido sin aviso.
 * 2. Después del commit, la fila se apunta para enviarla cuando la respuesta ya ha salido hacia el navegador
 *    (flush() en app()->terminating()): el cliente no espera a n8n.
 * 3. attempt(): en el primer intento se construye el cuerpo con el estado final y se congela; los reintentos
 *    reenvían ese mismo cuerpo (mismo event_id) con una firma nueva.
 * 4. retryDue(): el comando integracion:reintentar, cada minuto, reintenta las pendientes que ya tocan.
 */
class IntegrationOutbox
{
    /** Esperas entre intentos (contrato, apartado 7): envío inicial + 5 reintentos. */
    public const BACKOFF_MINUTES = [1, 2, 5, 10, 30];

    /** Si el proceso que hizo el cambio muere antes de enviar, el cron la recoge pasado este margen. */
    public const FIRST_ATTEMPT_GRACE_MINUTES = 1;

    /** @var list<int> filas confirmadas, pendientes de su primer envío */
    private array $queued = [];

    public function __construct(
        private EventPayloadBuilder $builder,
        private WebhookSender $sender,
    ) {}

    /**
     * Se llama desde EventLogger::log(), dentro de la transacción del cambio de negocio.
     */
    public function record(Event $event): void
    {
        if (! $this->sender->isConfigured() || ! $this->builder->shouldSend($event)) {
            return;
        }

        $delivery = WebhookDelivery::create([
            'event_id' => (string) Str::uuid(),
            'type' => $event->type,
            'source_event_id' => $event->id,
            'status' => WebhookDelivery::PENDING,
            'attempts' => 0,
            'next_attempt_at' => now()->addMinutes(self::FIRST_ATTEMPT_GRACE_MINUTES),
        ]);

        // Solo si la transacción se confirma (sin transacción abierta, en el acto)
        DB::afterCommit(function () use ($delivery) {
            $this->queued[] = $delivery->id;
        });
    }

    /**
     * Primer envío de lo confirmado en esta petición. Se ejecuta en app()->terminating(),
     * cuando la respuesta ya se ha enviado al navegador (PHP-FPM).
     */
    public function flush(): void
    {
        $ids = $this->queued;
        $this->queued = [];

        foreach ($ids as $id) {
            $delivery = WebhookDelivery::find($id);
            // attempts === 0: si otra cosa ya la intentó (el cron), no se repite aquí
            if ($delivery && $delivery->status === WebhookDelivery::PENDING && $delivery->attempts === 0) {
                $this->attempt($delivery);
            }
        }
    }

    /**
     * Reintenta las entregas pendientes cuyo momento ya ha llegado.
     *
     * @return array<string, int> recuento por estado final de cada intento
     */
    public function retryDue(int $limit = 50): array
    {
        $summary = [WebhookDelivery::SENT => 0, WebhookDelivery::PENDING => 0, WebhookDelivery::FAILED => 0, WebhookDelivery::DISCARDED => 0];

        if (! $this->sender->isConfigured()) {
            return $summary;
        }

        WebhookDelivery::where('status', WebhookDelivery::PENDING)
            ->where('next_attempt_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (WebhookDelivery $delivery) use (&$summary) {
                $status = $this->attempt($delivery);
                $summary[$status] = ($summary[$status] ?? 0) + 1;
            });

        return $summary;
    }

    /**
     * Vuelve a poner en cola las entregas fallidas (integracion:reintentar --fallidos), por ejemplo cuando ya
     * se ha corregido el flujo de n8n. Conservan su event_id y su cuerpo: si n8n ya las había procesado,
     * responderá «duplicate» y no se duplica nada. Empiezan de nuevo con todos sus intentos.
     */
    public function requeueFailed(): int
    {
        return WebhookDelivery::where('status', WebhookDelivery::FAILED)->update([
            'status' => WebhookDelivery::PENDING,
            'attempts' => 0,
            'next_attempt_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Un intento de envío. Devuelve el estado en que queda la entrega.
     */
    public function attempt(WebhookDelivery $delivery): string
    {
        try {
            // Reserva atómica: si dos procesos van a por la misma fila, solo uno la envía
            $claimed = WebhookDelivery::whereKey($delivery->id)
                ->where('status', WebhookDelivery::PENDING)
                ->where('attempts', $delivery->attempts)
                ->update([
                    'attempts' => $delivery->attempts + 1,
                    'next_attempt_at' => now()->addMinutes(5), // reserva mientras se envía
                    'updated_at' => now(),
                ]);
            if ($claimed !== 1) {
                return $delivery->fresh()->status;
            }
            $delivery->refresh();

            // Primer intento: construir el cuerpo con el estado final y congelarlo
            if ($delivery->body === null) {
                $event = $delivery->sourceEvent;
                $data = $event ? $this->builder->build($event) : null;
                if ($data === null) {
                    $delivery->update(['status' => WebhookDelivery::DISCARDED, 'next_attempt_at' => null]);

                    return WebhookDelivery::DISCARDED;
                }
                $delivery->update(['body' => $this->sender->envelope($delivery->event_id, $event, $data)]);
            }

            $result = $this->sender->send($delivery->type, $delivery->event_id, $delivery->body);

            return $this->applyResult($delivery, $result['status'], $result['error']);
        } catch (Throwable $e) {
            // Nunca debe llegar al cliente: la entrega sigue pendiente y el cron la reintentará (con el mismo tope)
            Log::error("Integración n8n: error preparando la entrega #{$delivery->id} ({$delivery->type}): {$e->getMessage()}");
            $delivery->refresh();
            if ($delivery->status === WebhookDelivery::PENDING && $delivery->attempts > count(self::BACKOFF_MINUTES)) {
                $delivery->update(['status' => WebhookDelivery::FAILED, 'last_error' => Str::limit($e->getMessage(), 1000), 'next_attempt_at' => null]);

                return WebhookDelivery::FAILED;
            }

            return WebhookDelivery::PENDING;
        }
    }

    /**
     * 2xx → enviado · 4xx (salvo 404, 408 y 429) → fallido sin reintentar · 5xx, 404, 408, 429 o sin respuesta → reintentar.
     */
    private function applyResult(WebhookDelivery $delivery, ?int $status, ?string $error): string
    {
        if ($status !== null && $status >= 200 && $status < 300) {
            $delivery->update([
                'status' => WebhookDelivery::SENT,
                'last_status_code' => $status,
                'last_error' => null,
                'sent_at' => now(),
                'next_attempt_at' => null,
            ]);

            return WebhookDelivery::SENT;
        }

        // 404: n8n responde así cuando el flujo está desactivado o en edición ("webhook not registered").
        // Es temporal, así que se reintenta como un 5xx; el resto de 4xx (salvo 408 y 429) no se arregla repitiendo.
        $retryable = $status === null || $status >= 500 || in_array($status, [404, 408, 429], true);
        $waits = self::BACKOFF_MINUTES;
        $exhausted = $delivery->attempts > count($waits); // envío inicial + count($waits) reintentos

        if (! $retryable || $exhausted) {
            $delivery->update([
                'status' => WebhookDelivery::FAILED,
                'last_status_code' => $status,
                'last_error' => $error,
                'next_attempt_at' => null,
            ]);
            Log::warning("Integración n8n: {$delivery->type} {$delivery->event_id} fallido tras {$delivery->attempts} intento(s) (HTTP ".($status ?? 'sin respuesta')."): {$error}");

            return WebhookDelivery::FAILED;
        }

        $delivery->update([
            'last_status_code' => $status,
            'last_error' => $error,
            'next_attempt_at' => now()->addMinutes($waits[$delivery->attempts - 1]),
        ]);

        return WebhookDelivery::PENDING;
    }
}
