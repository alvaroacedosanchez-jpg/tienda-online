<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Integración con n8n (Tarea 2): reintentos de la bandeja de salida. En DonDominio, el cron del panel
// lanza `php artisan schedule:run` cada minuto (no hay colas: QUEUE_CONNECTION=sync).
Schedule::command('integracion:reintentar')->everyMinute()->withoutOverlapping();
