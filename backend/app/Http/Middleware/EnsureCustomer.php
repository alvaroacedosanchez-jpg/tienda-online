<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zona de compra y área de cliente: la cuenta de administrador no puede usarlas
 * (ni carrito, ni pedidos, ni "Mi cuenta"). Los invitados sí pasan: el carrito es público.
 */
class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_admin) {
            return redirect()->route('admin.orders.index')
                ->with('status', 'La cuenta de administrador no puede hacer pedidos ni usar el área de cliente.');
        }

        return $next($request);
    }
}
