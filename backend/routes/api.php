<?php

use App\Http\Controllers\Api\IntegrationCronController;
use App\Http\Controllers\Api\IntegrationExportController;
use App\Http\Controllers\Api\IntegrationSyncController;
use Illuminate\Support\Facades\Route;

// API para n8n (Tarea 2). Rutas "api": sin sesión ni CSRF. Contrato: SIE-Tarea2/integracion/contrato.md, apartado 8.
// throttle antes que el token: limita también los intentos con tokens incorrectos.
Route::post('/integration/sync', IntegrationSyncController::class)
    ->middleware(['throttle:60,1', 'integration.token'])
    ->name('integration.sync');

// Cron del panel de DonDominio (solo sabe llamar a URLs): reintentos de la bandeja de salida.
// Usuario y contraseña HTTP (Basic Auth); hace lo mismo que `php artisan integracion:reintentar`.
Route::match(['get', 'post'], '/integration/cron', IntegrationCronController::class)
    ->middleware(['throttle:10,1', 'integration.cron'])
    ->name('integration.cron');

// Sincronización inicial tienda -> back-office (flujo manual de n8n): solo lectura, mismo token que sync.
Route::middleware(['throttle:60,1', 'integration.token'])->group(function () {
    Route::get('/integration/catalogo', [IntegrationExportController::class, 'catalog'])->name('integration.catalog');
    Route::get('/integration/pedidos', [IntegrationExportController::class, 'orders'])->name('integration.orders');
});
