<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\RedsysGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Respuestas del TPV Virtual de Redsys: notificación (servidor a servidor) y retorno del cliente. */
class RedsysController extends Controller
{
    /**
     * Notificación de Redsys (merchantUrl). La llama el servidor de Redsys, sin sesión ni CSRF:
     * la seguridad está en la firma, que RedsysGateway::settle verifica.
     */
    public function notify(Request $request, RedsysGateway $redsys)
    {
        try {
            $payment = $redsys->settle($request->all());
        } catch (Throwable $e) {
            Log::warning('Notificación de Redsys rechazada: '.$e->getMessage());

            return response('KO', 400);
        }

        return response('OK '.$payment->gateway_order, 200);
    }

    /** Vuelta del cliente desde Redsys tras un pago correcto (urlOk). */
    public function ok(Request $request, Payment $payment, RedsysGateway $redsys)
    {
        return $this->back($request, $payment, $redsys);
    }

    /** Vuelta del cliente desde Redsys tras un error o cancelación (urlKo). */
    public function ko(Request $request, Payment $payment, RedsysGateway $redsys)
    {
        return $this->back($request, $payment, $redsys, cancelled: true);
    }

    /**
     * Redsys añade a la URL de vuelta la respuesta firmada: se procesa igual que la notificación
     * (idempotente). En local es la única forma de enterarse, porque Redsys no puede enviar
     * notificaciones a localhost.
     * Si el cliente vuelve por urlKo SIN respuesta firmada (canceló en la página de Redsys), el pago
     * se da por cancelado y el pedido se cancela al instante (stock y carrito devueltos).
     * Una urlOk sin firmar no cambia nada: el pago podría estar aprobándose.
     */
    private function back(Request $request, Payment $payment, RedsysGateway $redsys, bool $cancelled = false)
    {
        abort_unless($payment->order->isOwnedBy($request->user()), 404);

        if ($request->filled('Ds_MerchantParameters')) {
            try {
                $payment = $redsys->settle($request->query());
            } catch (Throwable $e) {
                Log::warning('Retorno de Redsys rechazado: '.$e->getMessage());
            }
        } elseif ($cancelled) {
            $redsys->cancelByCustomer($payment);
        }

        $payment->refresh();
        $order = $payment->order;

        return match ($payment->status) {
            Payment::APPROVED => redirect()->route('orders.show', $order)
                ->with('status', 'Pago aprobado en el TPV de Redsys (entorno de pruebas). ¡Pedido registrado!'),
            // Pedido cancelado: los productos vuelven al carrito para que el cliente pueda reintentar
            Payment::DECLINED => redirect()->route('cart.show')
                ->withErrors(['cart' => 'El pago no se ha completado'.($payment->response_code ? " (código {$payment->response_code} de Redsys)" : ' (cancelado en Redsys)')
                    ." y el pedido {$order->reference} se ha cancelado. Hemos devuelto los productos a tu carrito por si quieres volver a intentarlo."]),
            default => redirect()->route('orders.show', $order)
                ->with('status', 'Estamos esperando la confirmación del pago de Redsys. El pedido se actualizará en cuanto llegue.'),
        };
    }
}
