<?php

declare(strict_types=1);

namespace Omersia\CMS;

use Illuminate\Support\ServiceProvider;
use Omersia\CMS\Repositories\Contracts\PageRepositoryInterface;
use Omersia\CMS\Repositories\PageRepository;

class CMSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PageRepositoryInterface::class, PageRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
