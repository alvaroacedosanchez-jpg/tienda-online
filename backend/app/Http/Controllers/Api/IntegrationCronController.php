<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Integration\IntegrationOutbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * GET /api/integration/cron · Lo llama el cron del panel de DonDominio (el hosting no deja lanzar
 * `php artisan schedule:run`). Hace lo mismo que `php artisan integracion:reintentar`.
 */
class IntegrationCronController extends Controller
{
    public function __invoke(IntegrationOutbox $outbox): JsonResponse
    {
        // Como withoutOverlapping(): si la llamada anterior sigue en marcha, esta no hace nada
        $lock = Cache::lock('integracion:reintentar', 300);
        if (! $lock->get()) {
            return response()->json(['result' => 'busy', 'message' => 'Hay otra ejecución en marcha.']);
        }

        try {
            $summary = $outbox->retryDue();
        } finally {
            $lock->release();
        }

        return response()->json(['result' => 'ok'] + $summary);
    }
}
