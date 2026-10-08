<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\EventLogger;
use App\Services\OrderService;
use App\Services\PaymentSimulator;
use Illuminate\Http\Request;
use RuntimeException;

class CheckoutController extends Controller
{
    public function show(CartService $cart, EventLogger $events)
    {
        if ($cart->isEmpty()) {
            return redirect()->route('catalog')->with('status', 'Tu carrito está vacío.');
        }

        $summary = $cart->summary();
        $events->log(EventLogger::CHECKOUT_STARTED, [
            'units' => $cart->count(),
            'total' => $summary['total'],
        ]);

        return view('checkout.show', ['items' => $cart->items(), 'summary' => $summary]);
    }

    public function store(Request $request, CartService $cart, OrderService $orders)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'regex:/^[0-9 +]{9,15}$/'],
            'address' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'digits:5'],
            'accept_prototype' => ['accepted'],
        ], [
            'accept_prototype.accepted' => 'Debes confirmar que entiendes que es un prototipo académico.',
            'postal_code.digits' => 'El código postal debe tener 5 dígitos.',
            'phone.regex' => 'El teléfono no tiene un formato válido.',
        ]);
        unset($data['accept_prototype']);

        try {
            $order = $orders->createFromCart($data);
        } catch (RuntimeException $e) {
            return redirect()->route('cart.show')->withErrors(['cart' => $e->getMessage()]);
        }

        return redirect()->route('orders.pay', $order);
    }

    public function payForm(Order $order)
    {
        if ($order->status !== Order::CREATED) {
            return redirect()->route('orders.show', $order);
        }

        return view('checkout.pay', ['order' => $order->load('items')]);
    }

    public function pay(Request $request, Order $order, PaymentSimulator $simulator)
    {
        abort_unless($order->status === Order::CREATED, 404);

        $data = $request->validate([
            'method' => ['required', 'in:card,transfer'],
            'card_number' => ['required_if:method,card', 'nullable', 'regex:/^[0-9 ]{13,23}$/'],
            'card_expiry' => ['required_if:method,card', 'nullable', 'regex:/^(0[1-9]|1[0-2])\/[0-9]{2}$/'],
            'card_cvv' => ['required_if:method,card', 'nullable', 'digits_between:3,4'],
        ], [
            'card_number.regex' => 'Número de tarjeta no válido.',
            'card_expiry.regex' => 'Usa el formato MM/AA.',
        ]);

        // El número de tarjeta solo se usa en esta llamada; no se guarda (solo los 4 últimos dígitos).
        $payment = $simulator->pay($order, $data['method'], $data['card_number'] ?? null);

        if ($payment->status === 'declined') {
            return redirect()->route('orders.pay', $order)
                ->withErrors(['payment' => 'Pago rechazado (simulado). Prueba con otra tarjeta de prueba o contacta con soporte.']);
        }

        return redirect()->route('orders.show', $order)->with('status', 'Pago simulado aprobado. ¡Pedido registrado!');
    }
}
