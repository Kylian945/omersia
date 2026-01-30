<?php

declare(strict_types=1);

namespace Omersia\Apparence;

use Illuminate\Support\ServiceProvider;
use Omersia\Apparence\Console\Commands\InitializeDefaultPages;
use Omersia\Apparence\Console\Commands\SyncThemeSchema;
use Omersia\Apparence\Contracts\EcommercePageRepositoryInterface;
use Omersia\Apparence\Contracts\MenuRepositoryInterface;
use Omersia\Apparence\Contracts\ThemeRepositoryInterface;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Apparence\Models\Menu;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Repositories\EcommercePageRepository;
use Omersia\Apparence\Repositories\MenuRepository;
use Omersia\Apparence\Repositories\ThemeRepository;

class ApparenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register commands
        $this->commands([
            SyncThemeSchema::class,
            InitializeDefaultPages::class,
        ]);

        $this->app->bind(ThemeRepositoryInterface::class, function ($app) {
            return new ThemeRepository(new Theme);
        });

        $this->app->bind(MenuRepositoryInterface::class, function ($app) {
            return new MenuRepository(new Menu);
        });

        $this->app->bind(EcommercePageRepositoryInterface::class, function ($app) {
            return new EcommercePageRepository(new EcommercePage);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Load theme views
        $this->loadViewsFrom(__DIR__.'/resources/views', 'apparence');
    }
}
