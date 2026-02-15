<?php

declare(strict_types=1);

namespace Omersia\Ai;

use Illuminate\Support\ServiceProvider;
use Omersia\Ai\Services\BackofficeAssistantService;
use Omersia\Ai\Services\ContentGenerationService;
use Omersia\Ai\Services\ProductImageGenerationService;
use Omersia\Ai\Services\ProductSeoGenerationService;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BackofficeAssistantService::class);
        $this->app->singleton(ContentGenerationService::class);
        $this->app->singleton(ProductSeoGenerationService::class);
        $this->app->singleton(ProductImageGenerationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
