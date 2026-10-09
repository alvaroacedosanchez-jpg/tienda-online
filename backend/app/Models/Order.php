<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const CREATED = 'creado';
    public const PAID = 'pagado_simulado';
    public const PENDING_PREPARATION = 'pendiente_preparacion';
    public const SHIPPED = 'enviado';
    public const CANCELLED = 'cancelado';
    public const INCIDENT = 'incidencia';

    public const STATUS_LABELS = [
        self::CREATED => 'Creado',
        self::PAID => 'Pagado (simulado)',
        self::PENDING_PREPARATION => 'Pendiente de preparación',
        self::SHIPPED => 'Enviado',
        self::CANCELLED => 'Cancelado',
        self::INCIDENT => 'Con incidencia',
    ];

    /** Transiciones que puede hacer el back-office desde cada estado. */
    public const TRANSITIONS = [
        self::CREATED => [self::CANCELLED, self::INCIDENT],
        self::PAID => [self::PENDING_PREPARATION, self::CANCELLED, self::INCIDENT],
        self::PENDING_PREPARATION => [self::SHIPPED, self::CANCELLED, self::INCIDENT],
        self::SHIPPED => [self::INCIDENT],
        self::INCIDENT => [self::PENDING_PREPARATION, self::CANCELLED],
        self::CANCELLED => [],
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** El pedido pertenece a este usuario (a través de su ficha de cliente). */
    public function isOwnedBy(User $user): bool
    {
        return $this->customer->user_id === $user->id;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }
}
