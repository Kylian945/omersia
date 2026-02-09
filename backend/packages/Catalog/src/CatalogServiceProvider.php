<?php

declare(strict_types=1);

namespace Omersia\Catalog;

use Illuminate\Support\ServiceProvider;
use Omersia\Catalog\Contracts\CategoryRepositoryInterface;
use Omersia\Catalog\Contracts\ProductRepositoryInterface;
use Omersia\Catalog\Contracts\ProductVariantRepositoryInterface;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductVariant;
use Omersia\Catalog\Repositories\CategoryRepository;
use Omersia\Catalog\Repositories\ProductRepository;
use Omersia\Catalog\Repositories\ProductVariantRepository;
use Omersia\Catalog\Services\ProductCreationService;
use Omersia\Catalog\Services\ProductImageService;
use Omersia\Catalog\Services\ProductVariantService;
use Omersia\Catalog\Services\SequenceService;

class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(ProductRepositoryInterface::class, function ($app) {
            return new ProductRepository(new Product);
        });

        $this->app->bind(CategoryRepositoryInterface::class, function ($app) {
            return new CategoryRepository(new Category);
        });

        $this->app->bind(ProductVariantRepositoryInterface::class, function ($app) {
            return new ProductVariantRepository(new ProductVariant);
        });

        // Service bindings (singletons car stateless)
        $this->app->singleton(ProductImageService::class);
        $this->app->singleton(ProductVariantService::class);
        $this->app->singleton(ProductCreationService::class);
        $this->app->singleton(SequenceService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
