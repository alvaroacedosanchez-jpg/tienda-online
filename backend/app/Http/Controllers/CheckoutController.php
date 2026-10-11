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

    /** Paso 4 · Cobra el pedido: con tarjeta redirige al TPV de Redsys; con transferencia simulada, pago, factura y estados en una transacción. */
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
            // Tarjeta: el cliente paga en el TPV de Redsys (entorno de pruebas); la tarjeta nunca pasa por la tienda
            if ($data['method'] === Payment::METHOD_REDSYS) {
                return view('checkout.redsys-redirect', $redsys->start($order) + ['order' => $order]);
            }

            $simulator->pay($order);
        } catch (CheckoutException $e) {
            // Otra petición ha pagado el pedido mientras tanto
            return redirect()->route('orders.show', $order)->withErrors(['cart' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', 'Transferencia simulada registrada. ¡Pedido registrado!');
    }
}
