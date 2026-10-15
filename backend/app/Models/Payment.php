<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHOD_REDSYS = 'redsys';   // tarjeta en el TPV Virtual de Redsys (entorno de pruebas)

    public const METHOD_TRANSFER = 'transfer'; // transferencia simulada

    public const PENDING = 'pending';   // enviado a Redsys, esperando su respuesta

    public const APPROVED = 'approved';

    public const DECLINED = 'declined';

    /** Textos de cada estado: los usan el back-office y la vista eventos_bi. */
    public const STATUS_LABELS = [
        self::PENDING => 'Pendiente de confirmación',
        self::APPROVED => 'Aprobado',
        self::DECLINED => 'Rechazado',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'gateway_response' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            self::METHOD_REDSYS => 'Tarjeta (TPV Redsys, pruebas)',
            self::METHOD_TRANSFER => 'Transferencia (simulada)',
            default => $this->method,
        };
    }

    /** Pasarela por la que pasó el pago: el TPV de Redsys o la transferencia simulada. */
    public function gateway(): string
    {
        return $this->method === self::METHOD_REDSYS ? 'redsys' : 'simulado';
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
