<?php

namespace ilhamuket\Tripay;

use Illuminate\Support\ServiceProvider;

class TripayServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tripay.php', 'tripay');

        $this->app->singleton(Tripay::class, function ($app) {
            return new Tripay(
                apiKey: config('tripay.api_key'),
                privateKey: config('tripay.private_key'),
                merchantCode: config('tripay.merchant_code'),
                mode: config('tripay.mode', 'sandbox')
            );
        });

        $this->app->alias(Tripay::class, 'tripay');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/tripay.php' => config_path('tripay.php'),
            ], 'tripay-config');
        }
    }
}
