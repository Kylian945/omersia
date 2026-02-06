<?php

declare(strict_types=1);

namespace Omersia\Api;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Omersia\Api\Services\CartCustomerResolver;
use Omersia\Api\Services\CartService;
use Omersia\Api\Services\Catalog\CategoryService;
use Omersia\Api\Services\Catalog\ProductSearchService;
use Omersia\Api\Services\Catalog\ProductService;
use Omersia\Api\Services\DiscountEvaluationService;
use Omersia\Api\Services\OrderCreationService;
use Omersia\Api\Services\OrderItemService;
use Omersia\Api\Services\OrderPriceValidationService;

class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Enregistrer les services (singleton car stateless)
        $this->app->singleton(DiscountEvaluationService::class);
        $this->app->singleton(OrderItemService::class);
        $this->app->singleton(OrderPriceValidationService::class);
        $this->app->singleton(OrderCreationService::class);
        $this->app->singleton(ProductService::class);
        $this->app->singleton(CategoryService::class);
        $this->app->singleton(ProductSearchService::class);
        $this->app->singleton(CartService::class);
        $this->app->singleton(CartCustomerResolver::class);
    }

    public function boot(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(__DIR__.'/routes/api.php');
    }
}
