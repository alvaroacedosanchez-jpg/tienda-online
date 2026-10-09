<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Services\CartService;
use App\Services\EventLogger;
use App\Services\OrderService;
use App\Services\PaymentSimulator;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /** Paso 2 · Muestra el formulario de envío (prerrellenado) y registra checkout.started. */
    public function show(Request $request, CartService $cart, EventLogger $events)
    {
        if ($cart->isEmpty()) {
            return redirect()->route('catalog')->with('status', 'Tu carrito está vacío.');
        }

        $summary = $cart->summary();
        $events->log(EventLogger::CHECKOUT_STARTED, [
            'units' => $cart->count(),
            'total' => $summary['total'],
        ]);

        return view('checkout.show', [
            'items' => $cart->items(),
            'summary' => $summary,
            'user' => $request->user(),
            // Ficha de cliente (null hasta su primera compra): sirve para prerrellenar el formulario
            'customer' => $request->user()->customer,
        ]);
    }

    /** Paso 3 · Valida los datos de envío y crea el pedido en una transacción (OrderService). */
    public function store(Request $request, OrderService $orders)
    {
        // El correo no se pide: se usa el de la cuenta del usuario
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
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

        // Solo se capturan errores de negocio; un error técnico revierte la transacción y da un 500
        try {
            $order = $orders->createFromCart($request->user(), $data);
        } catch (CheckoutException $e) {
            return redirect()->route('cart.show')->withErrors(['cart' => $e->getMessage()]);
        }

        return redirect()->route('orders.pay', $order);
    }

    /** Paso 4 · Muestra el formulario de pago simulado (solo del dueño y si está sin pagar). */
    public function payForm(Request $request, Order $order)
    {
        // 404 y no 403: no revelamos que existe un pedido con esa referencia
        abort_unless($order->isOwnedBy($request->user()), 404);

        if ($order->status !== Order::CREATED) {
            return redirect()->route('orders.show', $order);
        }

        return view('checkout.pay', ['order' => $order->load('items')]);
    }

    /** Paso 4 · Cobra el pedido: pago, factura y cambios de estado en una transacción. */
    public function pay(Request $request, Order $order, PaymentSimulator $simulator)
    {
        abort_unless($order->isOwnedBy($request->user()), 404);
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
        try {
            $payment = $simulator->pay($order, $data['method'], $data['card_number'] ?? null);
        } catch (CheckoutException $e) {
            // Otra petición ha pagado el pedido mientras tanto
            return redirect()->route('orders.show', $order)->withErrors(['cart' => $e->getMessage()]);
        }

        if ($payment->status === 'declined') {
            return redirect()->route('orders.pay', $order)
                ->withErrors(['payment' => 'Pago rechazado (simulado). Prueba con otra tarjeta de prueba o contacta con soporte.']);
        }

        return redirect()->route('orders.show', $order)->with('status', 'Pago simulado aprobado. ¡Pedido registrado!');
    }
}
