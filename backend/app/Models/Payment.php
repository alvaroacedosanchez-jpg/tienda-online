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

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PENDING => 'pendiente de confirmación',
            self::APPROVED => 'aprobado',
            self::DECLINED => 'rechazado',
            default => $this->status,
        };
    }
}
