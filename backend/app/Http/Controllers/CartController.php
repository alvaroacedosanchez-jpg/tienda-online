<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\EventLogger;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /** Paso 1 · Muestra el carrito con sus líneas y el resumen de importes. */
    public function show(CartService $cart)
    {
        return view('cart.index', [
            'items'   => $cart->items(),
            'summary' => $cart->summary(),
            'code'    => $cart->code(),
        ]);
    }

    /** Paso 1 · Añade un producto (o una variante de tamaño) al carrito y registra cart.item_added. */
    public function add(Request $request, Product $product, CartService $cart, EventLogger $events)
    {
        $data = $request->validate([
            'quantity'   => ['nullable', 'integer', 'min:1', 'max:10'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        abort_unless($product->active && $product->stock > 0, 404);

        $quantity = (int) ($data['quantity'] ?? 1);
        $variant = !empty($data['variant_id']) ? ProductVariant::find($data['variant_id']) : null;

        // Pasar producto y variante al servicio: devuelve las unidades que entraron de verdad (limitadas por el stock)
        $added = $cart->add($product, $quantity, $variant);

        $itemLabel = $variant ? "«{$product->name} ({$variant->size})»" : "«{$product->name}»";

        if ($added === 0) {
            return redirect()->route('cart.show')
                ->withErrors(['cart' => "No quedan más unidades disponibles de {$itemLabel}."]);
        }

        $unitPrice = $variant ? (float) $variant->price : (float) $product->price;

        $events->log(EventLogger::CART_ITEM_ADDED, [
            'product_id'         => $product->id,
            'variant_id'         => $variant?->id,
            'sku'                => $product->sku,
            'size'               => $variant?->size,
            'quantity'           => $added,     // unidades que entraron en el carrito
            'requested_quantity' => $quantity,  // unidades que pidió el cliente
            'unit_price'         => $unitPrice,
            'cart_units'         => $cart->count(),
        ]);

        return redirect()->route('cart.show')->with('status', "{$itemLabel} añadido al carrito.");
    }

    /** Paso 1 · Cambia la cantidad de una línea del carrito (0 la elimina). */
    public function update(Request $request, string $itemKey, CartService $cart, EventLogger $events)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        // $itemKey es la clave única en sesión (ej: "1" o "1_5")
        $before = $cart->line($itemKey);
        $cart->set($itemKey, (int) $data['quantity']);
        $after = $cart->line($itemKey);

        if ($before && ! $after) {
            // Cantidad 0: la línea se ha quitado
            $this->logRemoved($events, $cart, $before);
        } elseif ($before && $after['quantity'] !== $before['quantity']) {
            $events->log(EventLogger::CART_ITEM_UPDATED, [
                'product_id' => $before['product_id'],
                'variant_id' => $before['variant_id'],
                'from'       => $before['quantity'],
                'to'         => $after['quantity'],
                'cart_units' => $cart->count(),
            ]);
        }

        return redirect()->route('cart.show');
    }

    /** Paso 1 · Quita una línea del carrito. */
    public function remove(string $itemKey, CartService $cart, EventLogger $events)
    {
        // $itemKey elimina la variante específica o el producto simple
        $before = $cart->line($itemKey);
        $cart->remove($itemKey);

        if ($before) {
            $this->logRemoved($events, $cart, $before);
        }

        return redirect()->route('cart.show')->with('status', 'Producto eliminado del carrito.');
    }

    /** Paso 1 · Aplica o quita un código de descuento. */
    public function applyCode(Request $request, CartService $cart)
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:30']]);

        if (! $cart->applyCode($data['code'] ?? null)) {
            return redirect()->route('cart.show')->withErrors(['code' => 'El código de descuento no es válido.']);
        }

        return redirect()->route('cart.show')->with('status', 'Código actualizado.');
    }

    /** Registra cart.item_removed con la línea tal como estaba antes de quitarla. */
    private function logRemoved(EventLogger $events, CartService $cart, array $line): void
    {
        $events->log(EventLogger::CART_ITEM_REMOVED, [
            'product_id' => $line['product_id'],
            'variant_id' => $line['variant_id'],
            'quantity'   => $line['quantity'],
            'cart_units' => $cart->count(),
        ]);
    }
}