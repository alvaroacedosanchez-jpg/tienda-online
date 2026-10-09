<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    protected $guarded = []; // <-- OBLIGATORIO para permitir ->createMany()

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Ruta de la ilustración generada para esta variante, p. ej. images/products/sal-002-200-ml.svg */
    public function defaultImagePath(): string
    {
        return 'images/products/'.Str::lower($this->product->sku).'-'.Str::slug($this->size).'.svg';
    }

    /** URL de la imagen de esta variante; si no tiene, la del producto. */
    public function imageUrl(): ?string
    {
        return $this->image ? asset($this->image) : $this->product->imageUrl();
    }
}
