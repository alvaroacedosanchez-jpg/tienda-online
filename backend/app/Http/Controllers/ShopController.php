<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\EventLogger;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function home()
    {
        return view('shop.home', [
            'categories' => Category::withCount('products')->orderBy('name')->get(),
            'featured' => Product::with('category')->where('active', true)->orderByDesc('id')->take(4)->get(),
        ]);
    }

    public function catalog(Request $request)
    {
        $categories = Category::orderBy('name')->get();
        $current = $request->query('categoria');

        $products = Product::with('category')
            ->where('active', true)
            ->when($current, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $current)))
            ->orderBy('name')
            ->get();

        return view('shop.catalog', compact('categories', 'products', 'current'));
    }

    public function show(Product $product, EventLogger $events)
    {
        abort_unless($product->active, 404);
        $product->load('category');

        $events->log(EventLogger::PRODUCT_VIEWED, [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
        ]);

        return view('shop.product', [
            'product' => $product,
            'related' => Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)->where('active', true)->take(3)->get(),
        ]);
    }
}
