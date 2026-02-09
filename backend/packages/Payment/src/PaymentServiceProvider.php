<?php

declare(strict_types=1);

namespace Omersia\Payment;

use Illuminate\Support\ServiceProvider;
use Omersia\Payment\Console\Commands\EncryptPaymentSecretsCommand;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                EncryptPaymentSecretsCommand::class,
            ]);
        }
    }
}
