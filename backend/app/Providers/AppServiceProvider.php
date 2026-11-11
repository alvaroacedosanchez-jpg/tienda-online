<?php

namespace App\Providers;

use App\Services\Integration\IntegrationOutbox;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Una sola bandeja de salida por petición: guarda qué entregas se confirmaron en ella
        $this->app->singleton(IntegrationOutbox::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Primer envío a n8n cuando la respuesta ya ha salido hacia el navegador (con PHP-FPM el cliente no espera).
        // Lo que falle aquí lo recoge el comando integracion:reintentar.
        $this->app->terminating(fn () => $this->app->make(IntegrationOutbox::class)->flush());
    }
}
