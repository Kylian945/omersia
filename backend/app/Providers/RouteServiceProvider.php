<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/admin';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Rate limit global API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit strict pour l'authentification
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limit pour la recherche
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Rate limit strict pour reset password
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        // Rate limit pour les webhooks (DCA-002)
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Rate limit strict pour checkout/payment (SEC-004)
        RateLimiter::for('checkout', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(20)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Rate limit pour l'upload
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perHour(50)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
