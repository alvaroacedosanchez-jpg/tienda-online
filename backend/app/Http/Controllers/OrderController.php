<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Request $request, Order $order)
    {
        // Solo el dueño ve su pedido (el admin lo consulta desde el back-office).
        // 404 y no 403: no revelamos que existe un pedido con esa referencia.
        abort_unless($order->isOwnedBy($request->user()), 404);

        return view('orders.show', ['order' => $order->load(['items', 'customer', 'payments'])]);
    }
}
