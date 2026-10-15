<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'occurred_at',
        'session_id',
        'user_id',
        'order_id',
        'product_id',
        'product_variant_id',
        'payment_id',
        'invoice_id',
        'support_ticket_id',
        'quantity',
        'previous_quantity',
        'unit_price',
        'amount',
        'status',
        'previous_status',
        'origin',
        'payment_method',
        'gateway',
        'response_code',
        'discount_code',
        'payload',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'unit_price'  => 'decimal:2',
        'amount'      => 'decimal:2',
        'payload'     => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }
}