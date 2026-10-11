<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Carrito + cálculo de importes (reglas de negocio).
 *
 * Dónde se guarda:
 *  - cliente registrado → base de datos (tablas carts / cart_items): se conserva entre sesiones;
 *  - invitado           → sesión (se pierde al caducar). Al iniciar sesión se fusiona con el guardado.
 *
 * Las líneas tienen siempre el mismo formato: "producto_variante" => {product_id, variant_id, quantity}.
 * Solo se guardan cantidades; el precio se lee del catálogo cada vez.
 * Los precios incluyen IVA; el IVA mostrado es el contenido en el total.
 */
class CartService
{
    private const KEY = 'cart';

    private const CODE_KEY = 'cart_discount_code';

    private const MAX_PER_LINE = 10;

    /**
     * Añade unidades al carrito, limitadas al stock disponible y a 10 por línea.
     * Devuelve las unidades que se han añadido de verdad (pueden ser menos de las pedidas).
     */
    public function add(Product $product, int $quantity = 1, ?ProductVariant $variant = null): int
    {
        $lines = $this->readLines();
        $itemKey = $this->generateKey($product->id, $variant?->id);
        $currentQuantity = $lines[$itemKey]['quantity'] ?? 0;

        // Stock disponible: el de la variante si existe; si no, el del producto
        $stock = $variant ? $variant->stock : $product->stock;
        $newQuantity = max(0, min($currentQuantity + $quantity, $stock, self::MAX_PER_LINE));

        if ($newQuantity === 0) {
            return 0;
        }

        $lines[$itemKey] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $newQuantity,
        ];
        $this->writeLines($lines);

        return max(0, $newQuantity - $currentQuantity);
    }

    /**
     * Línea del carrito por su clave, o null si no existe.
     *
     * @return array{product_id: int, variant_id: ?int, quantity: int}|null
     */
    public function line(string $itemKey): ?array
    {
        return $this->readLines()[$itemKey] ?? null;
    }

    /** Cambia la cantidad de una línea (0 la elimina), limitada al stock y a 10. */
    public function set(string $itemKey, int $quantity): void
    {
        $lines = $this->readLines();
        if (! isset($lines[$itemKey])) {
            return;
        }

        if ($quantity <= 0) {
            unset($lines[$itemKey]);
        } else {
            $available = $this->available($lines[$itemKey]['product_id'], $lines[$itemKey]['variant_id']);
            if ($available === null) {
                unset($lines[$itemKey]); // el producto ya no existe
            } else {
                $lines[$itemKey]['quantity'] = min($quantity, $available, self::MAX_PER_LINE);
            }
        }

        $this->writeLines($lines);
    }

    public function remove(string $itemKey): void
    {
        $lines = $this->readLines();
        if (isset($lines[$itemKey])) {
            unset($lines[$itemKey]);
            $this->writeLines($lines);
        }
    }

    /** Vacía el carrito y olvida el código de descuento. */
    public function clear(): void
    {
        $this->writeLines([]);
        session()->forget(self::CODE_KEY);
    }

    public function isEmpty(): bool
    {
        return count($this->readLines()) === 0;
    }

    public function count(): int
    {
        return (int) collect($this->readLines())->sum('quantity');
    }

    /**
     * Líneas con sus modelos, el importe y el stock disponible ahora mismo.
     *
     * @return Collection<string, array{product: Product, variant: ?ProductVariant, quantity: int, line_total: float, available: int}>
     */
    public function items(): Collection
    {
        $items = collect();

        foreach ($this->readLines() as $key => $line) {
            $product = Product::where('id', $line['product_id'])->where('active', true)->first();
            if (! $product) {
                continue;
            }

            $variant = $line['variant_id'] ? ProductVariant::find($line['variant_id']) : null;
            $source = $variant ?? $product;

            $items->put($key, [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $line['quantity'],
                'line_total' => round($source->price * $line['quantity'], 2),
                // Stock actual: el carrito avisa si la cantidad lo supera (el checkout lo rechazaría)
                'available' => $source->stock,
            ]);
        }

        return $items;
    }

    /**
     * Al iniciar sesión o registrarse: suma el carrito de invitado (sesión) al carrito guardado
     * del usuario (cantidades sumadas, con tope de stock y de 10) y vacía el de la sesión.
     */
    public function mergeGuestCart(): void
    {
        $guestLines = session(self::KEY, []);
        if (! $this->databaseUser() || ! $guestLines) {
            return;
        }

        $lines = $this->readLines();
        foreach ($guestLines as $key => $guestLine) {
            $available = $this->available($guestLine['product_id'], $guestLine['variant_id']);
            if (! $available) {
                continue; // producto desaparecido o agotado
            }

            $quantity = min(($lines[$key]['quantity'] ?? 0) + $guestLine['quantity'], $available, self::MAX_PER_LINE);
            $lines[$key] = [
                'product_id' => $guestLine['product_id'],
                'variant_id' => $guestLine['variant_id'],
                'quantity' => $quantity,
            ];
        }

        $this->writeLines($lines);
        session()->forget(self::KEY);
    }

    /** Devuelve true si el código es válido y lo guarda (en la sesión: se aplica en el checkout). */
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

    // ------------------------------------------------------------------ almacenamiento

    /** Cliente registrado cuyo carrito va en base de datos (el admin no tiene carrito). */
    private function databaseUser(): ?User
    {
        $user = auth()->user();

        return $user && ! $user->is_admin ? $user : null;
    }

    /** @return array<string, array{product_id: int, variant_id: ?int, quantity: int}> */
    private function readLines(): array
    {
        $user = $this->databaseUser();
        if (! $user) {
            return session(self::KEY, []);
        }

        $lines = [];
        foreach ($user->cart?->items()->orderBy('id')->get() ?? [] as $item) {
            $lines[$this->generateKey($item->product_id, $item->product_variant_id)] = [
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
            ];
        }

        return $lines;
    }

    /**
     * Guarda las líneas. En base de datos sincroniza cart_items (crea, actualiza y borra)
     * en una transacción, y actualiza la fecha del carrito (útil para detectar abandonos).
     */
    private function writeLines(array $lines): void
    {
        $user = $this->databaseUser();
        if (! $user) {
            session([self::KEY => $lines]);

            return;
        }

        DB::transaction(function () use ($user, $lines) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
            $existing = $cart->items()->get()
                ->keyBy(fn ($item) => $this->generateKey($item->product_id, $item->product_variant_id));

            foreach ($existing as $key => $item) {
                if (! isset($lines[$key])) {
                    $item->delete();
                } elseif ($item->quantity !== $lines[$key]['quantity']) {
                    $item->update(['quantity' => $lines[$key]['quantity']]);
                }
            }

            foreach ($lines as $key => $line) {
                if (! $existing->has($key)) {
                    $cart->items()->create([
                        'product_id' => $line['product_id'],
                        'product_variant_id' => $line['variant_id'],
                        'quantity' => $line['quantity'],
                    ]);
                }
            }

            $cart->touch();
        });

        $user->unsetRelation('cart');
    }

    /** Stock disponible de un producto o de su variante; null si ya no existe. */
    private function available(int $productId, ?int $variantId): ?int
    {
        $source = $variantId ? ProductVariant::find($variantId) : Product::find($productId);

        return $source?->stock;
    }

    /** Genera la clave única de la línea en el carrito. */
    private function generateKey(int $productId, ?int $variantId = null): string
    {
        return $variantId ? "{$productId}_{$variantId}" : (string) $productId;
    }
}
