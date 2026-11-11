<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticación de n8n en la API de la tienda: token fijo en `Authorization: Bearer`
 * (contrato, apartado 8). Sin token configurado en la tienda, no se acepta nada.
 */
class EnsureIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.integration.token');
        $given = (string) $request->bearerToken();

        // hash_equals: comparación en tiempo constante (no da pistas del token por el tiempo de respuesta)
        if ($expected === '' || $given === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
