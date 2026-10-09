<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Carrito guardado en la sesión + cálculo de importes (reglas de negocio).
 * Los precios incluyen IVA; el IVA mostrado es el contenido en el total.
 */
class CartService
{
    private const KEY = 'cart';
    private const CODE_KEY = 'cart_discount_code';

    public function add(Product $product, int $quantity = 1): void
    {
        $cart = session(self::KEY, []);
        $current = $cart[$product->id] ?? 0;
        $cart[$product->id] = min($current + $quantity, $product->stock, 10);
        session([self::KEY => $cart]);
    }

    public function set(Product $product, int $quantity): void
    {
        $cart = session(self::KEY, []);
        if ($quantity <= 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id] = min($quantity, $product->stock, 10);
        }
        session([self::KEY => $cart]);
    }

    public function remove(Product $product): void
    {
        $this->set($product, 0);
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
        return array_sum(session(self::KEY, []));
    }

    /** @return Collection<int, array{product: Product, quantity: int, line_total: float}> */
    public function items(): Collection
    {
        $cart = session(self::KEY, []);
        if (! $cart) {
            return collect();
        }

        return Product::whereIn('id', array_keys($cart))
            ->where('active', true)
            ->get()
            ->map(fn (Product $p) => [
                'product' => $p,
                'quantity' => $cart[$p->id],
                'line_total' => round($p->price * $cart[$p->id], 2),
            ]);
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
            'subtotal' => $subtotal,
            'discount' => $discount,
            'discount_code' => $percent ? $code : null,
            'discount_percent' => $percent,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
        ];
    }
}
