<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CartService;
use App\Services\EventLogger;
use App\Services\OrderService;
use App\Services\PaymentSimulator;
use App\Services\RedsysGateway;
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
        $events->log(
            EventLogger::CHECKOUT_STARTED,
            [
                'quantity' => $cart->count(),
                'amount'   => $summary['total'],
            ]
        );

        return view('checkout.show', [
            'items' => $cart->items(),
            'summary' => $summary,
            'user' => $request->user(),
            'customer' => $request->user()->customer,
        ]);
    }

    /** Paso 2 · Aplica o quita un código de descuento (se guarda en la sesión hasta crear el pedido). */
    public function applyCode(Request $request, CartService $cart)
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:30']]);

        if (! $cart->applyCode($data['code'] ?? null)) {
            return redirect()->route('checkout.show')
                ->withErrors(['code' => 'El código de descuento no es válido.'])
                ->withInput($request->only('code'));
        }

        return redirect()->route('checkout.show')->with('status', 'Código de descuento actualizado.');
    }

    /** Paso 3 · Valida los datos de envío y crea el pedido en una transacción (OrderService). */
    public function store(Request $request, OrderService $orders)
    {
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

        try {
            $order = $orders->createFromCart($request->user(), $data);
        } catch (CheckoutException $e) {
            return redirect()->route('cart.show')->withErrors(['cart' => $e->getMessage()]);
        }

        return redirect()->route('orders.pay', $order);
    }

    /** Paso 4 · Muestra el formulario de pago simulado. */
    public function payForm(Request $request, Order $order)
    {
        abort_unless($order->isOwnedBy($request->user()), 404);

        if ($order->status !== Order::CREATED) {
            return redirect()->route('orders.show', $order);
        }

        return view('checkout.pay', ['order' => $order->load('items')]);
    }

    /** Paso 4 · Cobra el pedido. */
    public function pay(Request $request, Order $order, PaymentSimulator $simulator, RedsysGateway $redsys)
    {
        abort_unless($order->isOwnedBy($request->user()), 404);
        abort_unless($order->status === Order::CREATED, 404);

        $data = $request->validate([
            'method' => ['required', 'in:'.Payment::METHOD_REDSYS.','.Payment::METHOD_TRANSFER],
        ], [
            'method.required' => 'Elige un método de pago.',
            'method.in' => 'El método de pago no es válido.',
        ]);

        try {
            if ($data['method'] === Payment::METHOD_REDSYS) {
                return view('checkout.redsys-redirect', $redsys->start($order) + ['order' => $order]);
            }

            $simulator->pay($order);
        } catch (CheckoutException $e) {
            return redirect()->route('orders.show', $order)->withErrors(['cart' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', 'Transferencia simulada registrada. ¡Pedido registrado!');
    }
}