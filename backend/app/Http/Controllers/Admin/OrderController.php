<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('estado');

        $orders = Order::with('customer')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'status'));
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load(['items', 'customer', 'payments'])]);
    }

    public function updateStatus(Request $request, Order $order, OrderService $orders)
    {
        $data = $request->validate(['status' => ['required', 'string']]);

        if (! in_array($data['status'], $order->allowedTransitions(), true)) {
            return back()->withErrors(['status' => 'Transición de estado no permitida.']);
        }

        $orders->changeStatus($order, $data['status'], 'backoffice');

        return back()->with('status', 'Estado actualizado.');
    }
}
