<?php

use App\Http\Controllers\Api\IntegrationSyncController;
use Illuminate\Support\Facades\Route;

// API para n8n (Tarea 2). Rutas "api": sin sesión ni CSRF. Contrato: SIE-Tarea2/integracion/contrato.md, apartado 8.
// throttle antes que el token: limita también los intentos con tokens incorrectos.
Route::post('/integration/sync', IntegrationSyncController::class)
    ->middleware(['throttle:60,1', 'integration.token'])
    ->name('integration.sync');
