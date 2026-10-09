<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Carrito guardado en la sesión + cálculo de importes (reglas de negocio).
 * Los precios incluyen IVA; el IVA mostrado es el contenido en el total.
 */
class CartService
{
    private const KEY = 'cart';
    private const CODE_KEY = 'cart_discount_code';

    public function add(Product $product, int $quantity = 1, ?ProductVariant $variant = null): void
    {
        $cart = session(self::KEY, []);
        
        $itemKey = $this->generateKey($product->id, $variant?->id);
        
        $currentQuantity = isset($cart[$itemKey]) ? $cart[$itemKey]['quantity'] : 0;
        
        // Calculamos el stock disponible (usamos el de la variante si existe, si no, el del producto)
        $stock = $variant ? $variant->stock : $product->stock;
        
        $cart[$itemKey] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity'   => min($currentQuantity + $quantity, $stock, 10)
        ];
        
        session([self::KEY => $cart]);
    }

    public function set(string $itemKey, int $quantity): void
    {
        $cart = session(self::KEY, []);
        
        if (!isset($cart[$itemKey])) {
            return;
        }

        if ($quantity <= 0) {
            unset($cart[$itemKey]);
        } else {
            // Para validar el stock, necesitamos recuperar los modelos
            $productId = $cart[$itemKey]['product_id'];
            $variantId = $cart[$itemKey]['variant_id'];
            
            $product = Product::find($productId);
            if (!$product) {
                 unset($cart[$itemKey]);
                 session([self::KEY => $cart]);
                 return;
            }
            
            $variant = $variantId ? ProductVariant::find($variantId) : null;
            $stock = $variant ? $variant->stock : $product->stock;

            $cart[$itemKey]['quantity'] = min($quantity, $stock, 10);
        }
        
        session([self::KEY => $cart]);
    }

    public function remove(string $itemKey): void
    {
        $cart = session(self::KEY, []);
        if (isset($cart[$itemKey])) {
            unset($cart[$itemKey]);
            session([self::KEY => $cart]);
        }
    }

    public function clear(): void
    {
        session()->forget([self::KEY, self::CODE_KEY]);
    }

    public function isEmpty(): bool
    {
        return count(session(self::KEY, [])) === 0;
    }

    public function count(): int
    {
        $cart = session(self::KEY, []);
        return collect($cart)->sum('quantity');
    }

    /** 
     * @return Collection<string, array{product: Product, variant: ?ProductVariant, quantity: int, line_total: float}> 
     */
    public function items(): Collection
    {
        $cart = session(self::KEY, []);
        if (! $cart) {
            return collect();
        }

        $items = collect();

        foreach ($cart as $key => $item) {
            $product = Product::where('id', $item['product_id'])->where('active', true)->first();
            
            if (!$product) {
                continue;
            }

            $variant = $item['variant_id'] ? ProductVariant::find($item['variant_id']) : null;
            $price = $variant ? $variant->price : $product->price;

            $items->put($key, [
                'product'    => $product,
                'variant'    => $variant,
                'quantity'   => $item['quantity'],
                'line_total' => round($price * $item['quantity'], 2),
            ]);
        }

        return $items;
    }

    /** Devuelve true si el código es válido y lo guarda. */
    public function applyCode(?string $code): bool
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            session()->forget(self::CODE_KEY);
            return true;
        }
        if (! array_key_exists($code, config('shop.discount_codes'))) {
            return false;
        }
        session([self::CODE_KEY => $code]);
        return true;
    }

    public function code(): ?string
    {
        return session(self::CODE_KEY);
    }

    public function summary(): array
    {
        return $this->summaryFor($this->items());
    }

    /**
     * Calcula los importes de unas líneas concretas. El checkout lo usa con
     * los precios releídos y bloqueados dentro de la transacción.
     *
     * @param  Collection<int, array{product: Product, quantity: int, line_total: float}>  $items
     */
    public function summaryFor(Collection $items): array
    {
        $subtotal = round($items->sum('line_total'), 2);

        $code = $this->code();
        $percent = $code ? (config('shop.discount_codes')[$code] ?? 0) : 0;
        $discount = round($subtotal * $percent / 100, 2);

        $afterDiscount = $subtotal - $discount;
        $shipping = ($subtotal === 0.0 || $afterDiscount >= config('shop.free_shipping_from'))
            ? 0.0
            : (float) config('shop.shipping_cost');

        $total = round($afterDiscount + $shipping, 2);
        $rate = config('shop.vat_rate');
        $tax = round($total - $total / (1 + $rate), 2); // IVA incluido en el total

        return [
            'subtotal'         => $subtotal,
            'discount'         => $discount,
            'discount_code'    => $percent ? $code : null,
            'discount_percent' => $percent,
            'shipping'         => $shipping,
            'tax'              => $tax,
            'total'            => $total,
        ];
    }
    
    /**
     * Genera la clave única para el ítem en el carrito.
     */
    private function generateKey(int $productId, ?int $variantId = null): string
    {
        return $variantId ? "{$productId}_{$variantId}" : (string) $productId;
    }
}