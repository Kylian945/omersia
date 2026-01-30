<?php

declare(strict_types=1);

namespace Omersia\Core;

use Illuminate\Support\ServiceProvider;
use Omersia\Core\Console\Commands\AdminCreateCommand;
use Omersia\Core\Console\Commands\ApiKeyGenerateCommand;
use Omersia\Core\Console\Commands\SyncModuleCommand;
use Omersia\Core\Contracts\ShopDomainRepositoryInterface;
use Omersia\Core\Contracts\ShopRepositoryInterface;
use Omersia\Core\Models\Shop;
use Omersia\Core\Models\ShopDomain;
use Omersia\Core\Repositories\ShopDomainRepository;
use Omersia\Core\Repositories\ShopRepository;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShopRepositoryInterface::class, function ($app) {
            return new ShopRepository(new Shop);
        });

        $this->app->bind(ShopDomainRepositoryInterface::class, function ($app) {
            return new ShopDomainRepository(new ShopDomain);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncModuleCommand::class,
                ApiKeyGenerateCommand::class,
                AdminCreateCommand::class,
            ]);
        }
    }
}
