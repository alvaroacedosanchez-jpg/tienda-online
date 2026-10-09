<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\EventLogger;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(CartService $cart)
    {
        return view('cart.index', [
            'items'   => $cart->items(),
            'summary' => $cart->summary(),
            'code'    => $cart->code(),
        ]);
    }

    public function add(Request $request, Product $product, CartService $cart, EventLogger $events)
    {
        $data = $request->validate([
            'quantity'   => ['nullable', 'integer', 'min:1', 'max:10'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        abort_unless($product->active && $product->stock > 0, 404);

        $quantity = (int) ($data['quantity'] ?? 1);
        $variant = !empty($data['variant_id']) ? ProductVariant::find($data['variant_id']) : null;

        // Pasar producto y variante al servicio
        $cart->add($product, $quantity, $variant);

        $unitPrice = $variant ? (float) $variant->price : (float) $product->price;

        $events->log(EventLogger::CART_ITEM_ADDED, [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'sku'        => $product->sku,
            'size'       => $variant?->size,
            'quantity'   => $quantity,
            'unit_price' => $unitPrice,
            'cart_units' => $cart->count(),
        ]);

        $itemLabel = $variant ? "«{$product->name} ({$variant->size})»" : "«{$product->name}»";

        return redirect()->route('cart.show')->with('status', "{$itemLabel} añadido al carrito.");
    }

    public function update(Request $request, string $itemKey, CartService $cart)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        // $itemKey es la clave única en sesión (ej: "1" o "1_5")
        $cart->set($itemKey, (int) $data['quantity']);

        return redirect()->route('cart.show');
    }

    public function remove(string $itemKey, CartService $cart)
    {
        // $itemKey elimina la variante específica o el producto simple
        $cart->remove($itemKey);

        return redirect()->route('cart.show')->with('status', 'Producto eliminado del carrito.');
    }

    public function applyCode(Request $request, CartService $cart)
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:30']]);

        if (! $cart->applyCode($data['code'] ?? null)) {
            return redirect()->route('cart.show')->withErrors(['code' => 'El código de descuento no es válido.']);
        }

        return redirect()->route('cart.show')->with('status', 'Código actualizado.');
    }
}