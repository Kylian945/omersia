<?php

declare(strict_types=1);

namespace Omersia\Gdpr;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Omersia\Gdpr\Services\CookieConsentService;
use Omersia\Gdpr\Services\DataDeletionService;
use Omersia\Gdpr\Services\DataExportService;
use Omersia\Gdpr\Services\DataRequestService;

class GdprServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Enregistrer les services (singleton car stateless)
        $this->app->singleton(CookieConsentService::class);
        $this->app->singleton(DataExportService::class);
        $this->app->singleton(DataDeletionService::class);
        $this->app->singleton(DataRequestService::class);
    }

    public function boot(): void
    {
        // Charger les migrations
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Enregistrer les routes API
        Route::prefix('api/v1/gdpr')
            ->middleware('api.key')
            ->group(__DIR__.'/routes/api.php');
    }
}
