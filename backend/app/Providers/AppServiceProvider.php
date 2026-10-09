<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
<<<<<<< HEAD
        // Elige según el diseño de tu CSS:
        Paginator::useBootstrapFive(); 
        // o bien: Paginator::useTailwind();
=======
        // Paginación con marcado simple (sin Tailwind); estilos en public/css/app.css
        Paginator::useBootstrapFive(); 
>>>>>>> 2ffb930121cb36469c88c9bad927230efa3ca77c
    }
}