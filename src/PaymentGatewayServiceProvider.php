<?php

namespace yogigr\PaymentGateway;

use Illuminate\Support\ServiceProvider;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('paymentgateway', function ($app) {
            return new PaymentGateway();
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'payment');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('payment.php'),
            ], 'config');
        }
    }
}
