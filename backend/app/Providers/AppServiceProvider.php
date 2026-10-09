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
        // Elige según el diseño de tu CSS:
        Paginator::useBootstrapFive(); 
        // o bien: Paginator::useTailwind();
    }
}