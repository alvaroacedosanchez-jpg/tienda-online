<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Services\EventLogger;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(CartService $cart)
    {
        return view('cart.index', ['items' => $cart->items(), 'summary' => $cart->summary(), 'code' => $cart->code()]);
    }

    public function add(Request $request, Product $product, CartService $cart, EventLogger $events)
    {
        $data = $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:10']]);
        abort_unless($product->active && $product->stock > 0, 404);

        $quantity = (int) ($data['quantity'] ?? 1);
        $cart->add($product, $quantity);

        $events->log(EventLogger::CART_ITEM_ADDED, [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'quantity' => $quantity,
            'unit_price' => (float) $product->price,
            'cart_units' => $cart->count(),
        ]);

        return redirect()->route('cart.show')->with('status', "«{$product->name}» añadido al carrito.");
    }

    public function update(Request $request, Product $product, CartService $cart)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:10']]);
        $cart->set($product, (int) $data['quantity']);

        return redirect()->route('cart.show');
    }

    public function remove(Product $product, CartService $cart)
    {
        $cart->remove($product);

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
