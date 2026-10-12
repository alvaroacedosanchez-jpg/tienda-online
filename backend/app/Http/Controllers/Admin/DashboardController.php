<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Ingresos: pedidos cobrados (pagados, en preparación o enviados)
        $totalRevenue = Order::whereIn('status', Order::PAID_STATUSES)->sum('total');

        // 2. Conteo general de pedidos y pedidos que requieren atención (sin terminar)
        $totalOrders = Order::count();
        $pendingOrders = Order::whereIn('status', Order::IN_PROGRESS)->count();

        // 3. Conteo de clientes registrados
        $totalCustomers = Customer::count();

        // 4. Conteo de alertas de stock bajo (umbral <= 5)
        $lowStockThreshold = 5;
        $lowStockProducts = Product::doesntHave('variants')
            ->where('stock', '<=', $lowStockThreshold)
            ->count();

        $lowStockVariants = ProductVariant::where('stock', '<=', $lowStockThreshold)->count();
        $totalLowStockAlerts = $lowStockProducts + $lowStockVariants;

        // 5. Últimos 5 pedidos para el resumen de actividad reciente
        $recentOrders = Order::with('customer')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'totalOrders',
            'pendingOrders',
            'totalCustomers',
            'totalLowStockAlerts',
            'recentOrders'
        ));
    }
}
