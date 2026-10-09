<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;

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
}