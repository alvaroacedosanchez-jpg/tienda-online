<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Integration\IntegrationSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/integration/sync · n8n aplica en la tienda los cambios del back-office (estado y stock).
 * El token lo comprueba el middleware EnsureIntegrationToken; la lógica está en IntegrationSync.
 */
class IntegrationSyncController extends Controller
{
    public function __invoke(Request $request, IntegrationSync $sync): JsonResponse
    {
        [$status, $body] = $sync->handle($request->json()->all());

        return response()->json($body, $status, [], JSON_UNESCAPED_UNICODE);
    }
}
