<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $guarded = []; // <-- OBLIGATORIO para permitir ->createMany()

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}