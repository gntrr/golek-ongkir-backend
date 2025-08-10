<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // binding untuk RajaOngkirClient
         $this->app->singleton(\App\Services\RajaOngkirClient::class, function ($app) {
            return new \App\Services\RajaOngkirClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
