<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        // Fix for Doctrine DBAL enum type issue (only when running migrations)
        try {
            if ($this->app->runningInConsole()) {
                $connection = Schema::getConnection();
                if (method_exists($connection, 'getDoctrineSchemaManager')) {
                    $schemaManager = call_user_func([$connection, 'getDoctrineSchemaManager']);
                    if (is_object($schemaManager) && method_exists($schemaManager, 'getDatabasePlatform')) {
                        $platform = $schemaManager->getDatabasePlatform();
                        if (method_exists($platform, 'registerDoctrineTypeMapping')) {
                            $platform->registerDoctrineTypeMapping('enum', 'string');
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore DB connection errors during boot
        }

        // Force l'utilisation de APP_URL pour la génération d'URLs
        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);
        }

        // Forcer HTTPS en production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            // Vérifier que debug est désactivé
            if (config('app.debug')) {
                Log::critical('APP_DEBUG is enabled in production!');
            }
        }
    }
}
