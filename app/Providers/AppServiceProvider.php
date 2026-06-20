<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force le schéma HTTPS pour toutes les URLs générées (assets, routes, etc.)
        // en production, car le serveur PHP interne tourne en HTTP derrière le
        // reverse proxy nginx qui gère le TLS / la terminaison HTTPS.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}