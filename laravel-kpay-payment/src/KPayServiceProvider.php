<?php

namespace KPay\LaravelKPayment;

use Illuminate\Support\ServiceProvider;
use KPay\LaravelKPayment\Services\KPayService;

class KPayServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/kpay.php' => config_path('kpay.php'),
        ], 'kpay-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'kpay-migrations');

        // Publish routes
        $this->publishes([
            __DIR__ . '/../routes/kpay.php' => base_path('routes/kpay.php'),
        ], 'kpay-routes');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/kpay.php');

        // Register migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\TestConnectionCommand::class,
            ]);
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/kpay.php',
            'kpay'
        );

        $this->app->singleton('kpay', function ($app) {
            return new KPayService();
        });
    }
}
