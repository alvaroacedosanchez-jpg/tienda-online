<?php

namespace App\Services\Integration;

use App\Models\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Envía a n8n los eventos de la tienda, firmados con HMAC
 * (contrato: SIE-Tarea2/integracion/contrato.md, apartados 3 y 4).
 *
 * Nunca lanza excepciones: si n8n falla o no responde, solo se registra en el log,
 * para que una compra no falle nunca por culpa de la integración.
 */
class WebhookSender
{
    public const SOURCE = 'piquantum-web';

    public const SCHEMA_VERSION = '1.0';

    public function __construct(private EventPayloadBuilder $builder) {}

    /**
     * Construye el `data`, lo envuelve en el sobre y lo envía. Se llama después del commit.
     */
    public function dispatch(Event $event): void
    {
        try {
            if (! $this->isConfigured()) {
                return;
            }

            $data = $this->builder->build($event);
            if ($data === null) {
                return; // el contrato dice que este evento no se envía
            }

            $eventId = (string) Str::uuid();
            $this->send($event->type, $eventId, $this->envelope($eventId, $event, $data));
        } catch (Throwable $e) {
            // El pedido ya está guardado: un fallo aquí no puede convertirse en un error para el cliente
            Log::error("Integración n8n: no se pudo preparar el evento {$event->type} #{$event->id}: {$e->getMessage()}");
        }
    }

    /**
     * El cuerpo JSON exacto que se envía (y que se firma).
     */
    public function envelope(string $eventId, Event $event, array $data): string
    {
        return json_encode([
            'event_id' => $eventId,
            'type' => $event->type,
            'occurred_at' => $event->occurred_at->toIso8601String(),
            'source' => self::SOURCE,
            'schema_version' => self::SCHEMA_VERSION,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Firma y envía un cuerpo ya codificado. Devuelve true si n8n responde 2xx.
     * Cada llamada lleva un timestamp y una firma nuevos (los reintentos reutilizan el cuerpo).
     */
    public function send(string $type, string $eventId, string $body): bool
    {
        $timestamp = (string) time();

        try {
            $response = Http::timeout((int) config('services.n8n.timeout'))
                ->withHeaders([
                    'X-Piquantum-Event' => $type,
                    'X-Piquantum-Event-Id' => $eventId,
                    'X-Piquantum-Timestamp' => $timestamp,
                    'X-Piquantum-Signature' => $this->signature($timestamp, $body),
                ])
                // withBody y no post($array): se envía exactamente el texto que se ha firmado
                ->withBody($body, 'application/json')
                ->post(config('services.n8n.url'));

            if (! $response->successful()) {
                Log::warning("Integración n8n: {$type} {$eventId} respondió {$response->status()}: ".Str::limit($response->body(), 500));
            }

            return $response->successful();
        } catch (Throwable $e) {
            // Tiempo agotado, DNS, conexión rechazada…
            Log::warning("Integración n8n: {$type} {$eventId} sin respuesta: {$e->getMessage()}");

            return false;
        }
    }

    public function signature(string $timestamp, string $body): string
    {
        return 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, (string) config('services.n8n.secret'));
    }

    /** Sin URL o sin secreto no se envía nada (tests, entornos sin integración). */
    public function isConfigured(): bool
    {
        return filled(config('services.n8n.url')) && filled(config('services.n8n.secret'));
    }
}
