<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticación del cron del panel de DonDominio: usuario y contraseña HTTP (Basic Auth).
 * Usuario «cron» y contraseña INTEGRATION_CRON_TOKEN. Van en una cabecera, no en la URL,
 * así que no quedan en los logs de acceso. Sin contraseña configurada, no se acepta nada.
 */
class EnsureCronCredentials
{
    public const USER = 'cron';

    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.integration.cron_token');
        $user = (string) $request->getUser();
        $password = (string) $request->getPassword();

        if ($expected === '' || $password === '' || $user !== self::USER || ! hash_equals($expected, $password)) {
            return response()->json(['message' => 'Unauthenticated.'], 401, ['WWW-Authenticate' => 'Basic realm="cron"']);
        }

        return $next($request);
    }
}
