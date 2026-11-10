<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un aviso recibido de n8n en POST /api/integration/sync (idempotencia de entrada).
 */
class IntegrationInboxEntry extends Model
{
    protected $table = 'integration_inbox';

    protected $fillable = [
        'event_id',
        'change_type',
        'entity_ref',
        'result',
        'http_status',
        'payload',
        'response',
    ];

    protected $casts = [
        'http_status' => 'integer',
        'payload' => 'array',
        'response' => 'array',
    ];
}
