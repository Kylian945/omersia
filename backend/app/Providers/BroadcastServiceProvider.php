<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Backoffice (session web auth)
        Broadcast::routes([
            'middleware' => ['web', 'auth'],
        ]);

        // Storefront (Sanctum bearer auth) via /api/broadcasting/auth
        Broadcast::routes([
            'prefix' => 'api',
            'middleware' => ['api', 'auth:sanctum'],
        ]);

        require base_path('routes/channels.php');
    }
}
