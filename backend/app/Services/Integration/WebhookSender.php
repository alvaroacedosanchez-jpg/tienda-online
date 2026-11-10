<?php

namespace App\Services\Integration;

use App\Models\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Firma y envía a n8n un evento ya codificado
 * (contrato: SIE-Tarea2/integracion/contrato.md, apartados 3 y 4).
 *
 * No decide nada: la bandeja de salida (IntegrationOutbox) decide cuándo enviar y qué hacer con la respuesta.
 * Nunca lanza excepciones, para que una compra no falle nunca por culpa de la integración.
 */
class WebhookSender
{
    public const SOURCE = 'piquantum-web';

    public const SCHEMA_VERSION = '1.0';

    /**
     * El cuerpo JSON exacto que se envía (y que se firma). Se guarda tal cual en la bandeja de salida.
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
     * Firma y envía un cuerpo ya codificado. Cada llamada lleva un timestamp y una firma nuevos,
     * aunque el cuerpo (y su event_id) sea el mismo en todos los reintentos.
     *
     * @return array{status: int|null, error: string|null} status null = sin respuesta (tiempo agotado, conexión…)
     */
    public function send(string $type, string $eventId, string $body): array
    {
        $timestamp = (string) now()->getTimestamp(); // reloj de Laravel (los tests pueden moverlo)

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

            return [
                'status' => $response->status(),
                'error' => $response->successful() ? null : Str::limit($response->body(), 1000),
            ];
        } catch (Throwable $e) {
            return ['status' => null, 'error' => Str::limit($e->getMessage(), 1000)];
        }
    }

    public function signature(string $timestamp, string $body): string
    {
        return 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, (string) config('services.n8n.secret'));
    }

    /** Sin URL o sin secreto la integración está desactivada (tests, entornos sin n8n). */
    public function isConfigured(): bool
    {
        return filled(config('services.n8n.url')) && filled(config('services.n8n.secret'));
    }
}
