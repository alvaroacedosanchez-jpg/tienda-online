<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'specs' => 'array',
            'active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // --- NUEVAS ADICIONES PARA LAS VARIANTES ---

    /**
     * Un producto puede tener muchas variantes de tamaño (50ml, 100ml, etc.)
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Helper para saber en la vista Blade si el producto tiene opciones
     */
    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    /** Ruta de la ilustración generada para este producto (relativa a public/). */
    public function defaultImagePath(): string
    {
        return 'images/products/'.Str::lower($this->sku).'.svg';
    }

    /** URL pública de la imagen, o null si no tiene (las vistas muestran entonces el emoji). */
    public function imageUrl(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }
}
