<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un evento de la bandeja de salida hacia n8n (patrón outbox).
 */
class WebhookDelivery extends Model
{
    public const PENDING = 'pendiente';

    public const SENT = 'enviado';

    public const FAILED = 'fallido';

    public const DISCARDED = 'descartado'; // al construirlo, el contrato dice que no se envía

    protected $fillable = [
        'event_id',
        'type',
        'source_event_id',
        'body',
        'status',
        'attempts',
        'next_attempt_at',
        'last_status_code',
        'last_error',
        'sent_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'last_status_code' => 'integer',
        'next_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function sourceEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'source_event_id');
    }
}
