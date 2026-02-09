<?php

declare(strict_types=1);

namespace Omersia\Sales;

use Illuminate\Support\ServiceProvider;
use Omersia\Sales\Services\DiscountCreationService;
use Omersia\Sales\Services\DiscountRelationService;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Enregistrer les services (singleton car stateless)
        $this->app->singleton(DiscountRelationService::class);
        $this->app->singleton(DiscountCreationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
