<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        // Traemos todos los productos e instanciamos sus variantes en solo 2 consultas SQL (evita el problema N+1)
        $products = Product::with('variants')->get();

        // Umbral a partir del cual consideramos que un producto o variante tiene stock bajo
        $lowStockThreshold = 5;

        return view('admin.stock.index', [
            'products' => $products,
            'lowStockThreshold' => $lowStockThreshold,
        ]);
    }
    
    public function receiveForm()
    {
        $products = Product::with('variants')->orderBy('name')->get();

        return view('admin.stock.receive', compact('products'));
    }

    public function processReceive(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $code = trim($request->input('code'));
        $quantity = (int) $request->input('quantity', 1);

        // 1. Comprobación exacta por SKU de producto
        $product = Product::where('sku', $code)->first();

        if ($product) {
            // Si el producto tiene variantes, no permitimos la entrada sin especificar variante
            if ($product->variants()->exists()) {
                return back()->withErrors([
                    'code' => "El producto '{$product->name}' tiene variantes. Debes añadir el tamaño al código (ejemplo: {$product->sku}-150 o {$product->sku} 150)."
                ]);
            }

            // Producto simple sin variantes: incrementamos stock directo
            $product->increment('stock', $quantity);

            return back()->with('status', "✅ ¡Stock actualizado! {$product->name} +{$quantity} uds. (Stock actual: {$product->stock})");
        }

        // 2. Si no es un SKU directo, buscar si es una combinación SKU + Tamaño (ej. SAL-002-150, SAL-002 150)
        $productsWithVariants = Product::has('variants')->get();

        foreach ($productsWithVariants as $p) {
            if (str_starts_with($code, $p->sku)) {
                // Extraer el tamaño escaneado tras el SKU (quitando guiones o espacios iniciales)
                $sizePart = trim(substr($code, strlen($p->sku)), '- ');

                if (! empty($sizePart)) {
                    $variant = $p->variants()
                        ->where('size', 'LIKE', "%{$sizePart}%")
                        ->first();

                    if ($variant) {
                        $variant->increment('stock', $quantity);

                        return back()->with('status', "✅ ¡Stock actualizado! {$p->name} ({$variant->size}) +{$quantity} uds. (Stock actual: {$variant->stock})");
                    }
                }
            }
        }

        return back()->withErrors(['code' => "No se encontró ningún producto o variante válido para el código: {$code}"]);
    }

}