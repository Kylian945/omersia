<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
// Admin Controllers
use Omersia\Admin\Http\Controllers\DashboardController;
use Omersia\Admin\Http\Controllers\GdprController;
use Omersia\Admin\Http\Controllers\MediaLibraryController;
use Omersia\Admin\Http\Controllers\MeilisearchController;
use Omersia\Admin\Http\Controllers\PageBuilderController;
use Omersia\Admin\Http\Controllers\PageController;
use Omersia\Admin\Http\Controllers\SettingsController;
// Customer Package Controllers
use Omersia\Apparence\Http\Controllers\EcommercePageBuilderController;
use Omersia\Apparence\Http\Controllers\EcommercePageController;
use Omersia\Apparence\Http\Controllers\MenuController;
// Catalog Package Controllers
use Omersia\Apparence\Http\Controllers\ThemeController;
use Omersia\Catalog\Http\Controllers\CategoryController;
use Omersia\Catalog\Http\Controllers\OrderController;
use Omersia\Catalog\Http\Controllers\ProductController;
// Sales Package Controllers
use Omersia\Catalog\Http\Controllers\ShippingMethodController;
// Payment Package Controllers
use Omersia\Core\Http\Controllers\ApiKeyController;
// Core Package Controllers
use Omersia\Core\Http\Controllers\ModulePositionController;
use Omersia\Core\Http\Controllers\ModuleUploadController;
use Omersia\Core\Http\Controllers\ShopController;
use Omersia\Customer\Http\Controllers\AddressController;
// Apparence Package Controllers
use Omersia\Customer\Http\Controllers\CustomerController;
use Omersia\Customer\Http\Controllers\CustomerGroupController;
use Omersia\Payment\Http\Controllers\PaymentController;
use Omersia\Sales\Http\Controllers\DiscountController;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])
    ->name('admin.dashboard');
Route::get('/metrics/active-carts', [DashboardController::class, 'activeCartsCount'])
    ->name('admin.metrics.active-carts');
Route::get('/dashboard/export', [DashboardController::class, 'export'])
    ->name('admin.dashboard.export');

// Resources
Route::resource('products', ProductController::class);
Route::resource('categories', CategoryController::class);
Route::resource('pages', PageController::class);
Route::resource('discounts', DiscountController::class);
Route::resource('customer-groups', CustomerGroupController::class)->except(['show']);

// API endpoints for builder
Route::get('/api/categories-list', [CategoryController::class, 'apiList'])
    ->name('admin.api.categories');
Route::get('/api/products-list', [ProductController::class, 'apiList'])
    ->name('admin.api.products');

// Customers
Route::get('/customers', [CustomerController::class, 'index'])
    ->name('admin.customers');
Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('admin.customers.show');

Route::prefix('customers/{customer}/addresses')->name('admin.customers.addresses.')->group(function () {
    Route::get('/', [AddressController::class, 'index'])->name('index');
    Route::get('/create', [AddressController::class, 'create'])->name('create');
    Route::post('/', [AddressController::class, 'store'])->name('store');
    Route::get('/{address}/edit', [AddressController::class, 'edit'])->name('edit');
    Route::put('/{address}', [AddressController::class, 'update'])->name('update');
    Route::delete('/{address}', [AddressController::class, 'destroy'])->name('destroy');

    Route::post('/{address}/default-shipping', [AddressController::class, 'setDefaultShipping'])->name('default-shipping');
    Route::post('/{address}/default-billing', [AddressController::class, 'setDefaultBilling'])->name('default-billing');
});

// Orders
Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders.index');
Route::get('/orders/drafts', [OrderController::class, 'drafts'])->name('admin.orders.drafts');
Route::get('/orders/{id}', [OrderController::class, 'show'])->name('admin.orders.show');
Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.update-status');
Route::get('/orders/{id}/invoice', [OrderController::class, 'downloadInvoice'])->name('admin.orders.invoice');

Route::post('products/{product}/images/{image}/main', [ProductController::class, 'setMainImage'])
    ->name('products.images.main');

// Shops
Route::get('/shops/create', [ShopController::class, 'create'])->name('admin.shops.create');
Route::post('/shops', [ShopController::class, 'store'])->name('admin.shops.store');

// Page Builder
Route::get('/pages/{page}/builder', [PageBuilderController::class, 'edit'])
    ->name('pages.builder');
Route::post('/pages/{page}/builder', [PageBuilderController::class, 'update'])
    ->name('pages.builder.update');

// Apparence
Route::prefix('apparence')
    ->name('admin.apparence.')
    ->group(function () {
        Route::resource('menus', MenuController::class)->except(['show']);
        Route::post('/menus/base', [MenuController::class, 'storeBaseMenu'])
            ->name('menus.store-base');

        // Themes
        Route::get('/theme', [ThemeController::class, 'index'])->name('theme.index');
        Route::post('/theme/logo', [ThemeController::class, 'updateLogo'])->name('theme.logo.update');
        Route::post('/theme/shop-name', [ThemeController::class, 'updateShopName'])->name('theme.shop-name.update');
        Route::post('/theme/upload', [ThemeController::class, 'uploadTheme'])->name('theme.upload');
        Route::get('/theme/{theme}/compare-widgets', [ThemeController::class, 'compareWidgets'])->name('theme.compare-widgets');
        Route::post('/theme/{theme}/activate', [ThemeController::class, 'activate'])->name('theme.activate');
        Route::delete('/theme/{theme}', [ThemeController::class, 'destroy'])->name('theme.destroy');
        Route::get('/theme/{theme}/customize', [ThemeController::class, 'customize'])->name('theme.customize');
        Route::post('/theme/{theme}/customize', [ThemeController::class, 'updateCustomization'])->name('theme.customize.update');
        Route::post('/theme/{theme}/reset', [ThemeController::class, 'resetCustomization'])->name('theme.customize.reset');

        // E-commerce Pages
        Route::resource('ecommerce-pages', EcommercePageController::class)
            ->except(['show'])
            ->parameters(['ecommerce-pages' => 'page']);
        Route::get('/ecommerce-pages/{page}/builder', [EcommercePageBuilderController::class, 'edit'])
            ->name('ecommerce-pages.builder');
        Route::post('/ecommerce-pages/{page}/builder', [EcommercePageBuilderController::class, 'update'])
            ->name('ecommerce-pages.builder.update');

        // Media Library
        Route::get('/media', [MediaLibraryController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaLibraryController::class, 'store'])->name('media.store');
        Route::delete('/media/{item}', [MediaLibraryController::class, 'destroy'])->name('media.destroy');
        Route::post('/media/folders', [MediaLibraryController::class, 'createFolder'])->name('media.folders.store');
        Route::delete('/media/folders/{folder}', [MediaLibraryController::class, 'destroyFolder'])->name('media.folders.destroy');

        // API endpoint for media picker
        Route::get('/api/media', [MediaLibraryController::class, 'apiIndex'])->name('api.media');
    });

// Settings
Route::prefix('settings')->name('admin.settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');

    // API Keys
    Route::resource('api-keys', ApiKeyController::class)->except(['show']);

    Route::patch('api-keys/{apiKey}/toggle', [ApiKeyController::class, 'toggle'])
        ->name('api-keys.toggle');

    Route::patch('api-keys/{apiKey}/regenerate', [ApiKeyController::class, 'regenerate'])
        ->name('api-keys.regenerate');

    // Shipping Methods
    Route::resource('shipping-methods', ShippingMethodController::class)
        ->names('shipping_methods')
        ->except(['show']);

    Route::prefix('shipping-methods/{shippingMethod}')->name('shipping_methods.')->group(function () {
        Route::get('/configure', [\Omersia\Catalog\Http\Controllers\ShippingConfigurationController::class, 'show'])
            ->name('configure');
        Route::post('/configure/options', [\Omersia\Catalog\Http\Controllers\ShippingConfigurationController::class, 'updateOptions'])
            ->name('configure.options');

        Route::post('/zones', [\Omersia\Catalog\Http\Controllers\ShippingConfigurationController::class, 'storeZone'])
            ->name('zones.store');
        Route::put('/zones/{zone}', [\Omersia\Catalog\Http\Controllers\ShippingConfigurationController::class, 'updateZone'])
            ->name('zones.update');
        Route::delete('/zones/{zone}', [\Omersia\Catalog\Http\Controllers\ShippingConfigurationController::class, 'destroyZone'])
            ->name('zones.destroy');

        Route::post('/rates', [\Omersia\Catalog\Http\Controllers\ShippingConfigurationController::class, 'storeRate'])
            ->name('rates.store');
        Route::put('/rates/{rate}', [\Omersia\Catalog\Http\Controllers\ShippingConfigurationController::class, 'updateRate'])
            ->name('rates.update');
        Route::delete('/rates/{rate}', [\Omersia\Catalog\Http\Controllers\ShippingConfigurationController::class, 'destroyRate'])
            ->name('rates.destroy');
    });

    // Payment Settings
    Route::prefix('payments')
        ->name('payments.')
        ->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::get('/stripe', [PaymentController::class, 'stripe'])->name('stripe');
            Route::put('/stripe', [PaymentController::class, 'updateStripe'])->name('stripe.update');
            Route::patch('/{paymentProvider}/toggle', [PaymentController::class, 'toggle'])->name('toggle');
        });

    // Meilisearch Settings
    Route::prefix('meilisearch')
        ->name('meilisearch.')
        ->group(function () {
            Route::get('/', [MeilisearchController::class, 'index'])->name('index');
            Route::post('/configure-index', [MeilisearchController::class, 'configureIndex'])->name('configure-index');
            Route::post('/index-products', [MeilisearchController::class, 'indexProducts'])->name('index-products');
            Route::post('/flush-index', [MeilisearchController::class, 'flushIndex'])->name('flush-index');
            Route::post('/import-all', [MeilisearchController::class, 'importAll'])->name('import-all');
        });

    // Tax Settings
    Route::prefix('taxes')
        ->name('taxes.')
        ->group(function () {
            Route::get('/', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'index'])->name('index');

            // Tax Zones
            Route::prefix('zones')->name('zones.')->group(function () {
                Route::get('/create', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'createZone'])->name('create');
                Route::post('/', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'storeZone'])->name('store');
                Route::get('/{taxZone}/edit', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'editZone'])->name('edit');
                Route::put('/{taxZone}', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'updateZone'])->name('update');
                Route::delete('/{taxZone}', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'destroyZone'])->name('destroy');
            });

            Route::prefix('zones/{taxZone}/rates')->name('rates.')->group(function () {
                Route::get('/create', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'createRate'])->name('create');
                Route::post('/', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'storeRate'])->name('store');
                Route::get('/{taxRate}/edit', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'editRate'])->name('edit');
                Route::put('/{taxRate}', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'updateRate'])->name('update');
                Route::delete('/{taxRate}', [\Omersia\Catalog\Http\Controllers\TaxController::class, 'destroyRate'])->name('destroy');
            });
        });

    // Roles & Permissions Management (Super Admin Only)
    Route::middleware('can:manage-roles')->group(function () {
        Route::resource('roles', \Omersia\Admin\Http\Controllers\RoleController::class)->except(['show']);
        Route::resource('permissions', \Omersia\Admin\Http\Controllers\PermissionController::class)->except(['show']);

        Route::get('users', [\Omersia\Admin\Http\Controllers\UserManagementController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [\Omersia\Admin\Http\Controllers\UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}/roles', [\Omersia\Admin\Http\Controllers\UserManagementController::class, 'updateRoles'])->name('users.roles.update');
        Route::post('users/{user}/roles/assign', [\Omersia\Admin\Http\Controllers\UserManagementController::class, 'assignRole'])->name('users.roles.assign');
        Route::delete('users/{user}/roles/{role}', [\Omersia\Admin\Http\Controllers\UserManagementController::class, 'removeRole'])->name('users.roles.remove');
    });

    // GDPR
    Route::prefix('gdpr')->name('gdpr.')->group(function () {
        Route::get('/', [GdprController::class, 'index'])->name('index');
        Route::get('/{request}', [GdprController::class, 'show'])->name('show');
        Route::post('/{request}/process-access', [GdprController::class, 'processAccess'])->name('process-access');
        Route::post('/{request}/process-export', [GdprController::class, 'processExport'])->name('process-export');
        Route::post('/{request}/process-deletion', [GdprController::class, 'processDeletion'])->name('process-deletion');
        Route::post('/{request}/reject', [GdprController::class, 'reject'])->name('reject');
        Route::post('/{request}/add-note', [GdprController::class, 'addNote'])->name('add-note');
    });

    // Performance & Cache
    Route::prefix('performance')->name('performance.')->group(function () {
        Route::get('/', [\Omersia\Admin\Http\Controllers\PerformanceController::class, 'index'])->name('index');
        Route::post('/clear-all', [\Omersia\Admin\Http\Controllers\PerformanceController::class, 'clearAll'])->name('clear-all');
        Route::post('/clear-cache', [\Omersia\Admin\Http\Controllers\PerformanceController::class, 'clearCache'])->name('clear-cache');
        Route::post('/clear-config', [\Omersia\Admin\Http\Controllers\PerformanceController::class, 'clearConfig'])->name('clear-config');
        Route::post('/clear-route', [\Omersia\Admin\Http\Controllers\PerformanceController::class, 'clearRoute'])->name('clear-route');
        Route::post('/clear-view', [\Omersia\Admin\Http\Controllers\PerformanceController::class, 'clearView'])->name('clear-view');
        Route::post('/clear-optimize', [\Omersia\Admin\Http\Controllers\PerformanceController::class, 'clearOptimize'])->name('clear-optimize');
        Route::post('/clear-event', [\Omersia\Admin\Http\Controllers\PerformanceController::class, 'clearEvent'])->name('clear-event');
    });
});

// Modules
Route::prefix('modules')
    ->name('admin.modules.')
    ->group(function () {
        Route::get('/', [ModuleUploadController::class, 'index'])->name('index');
        Route::get('upload', [ModuleUploadController::class, 'create'])->name('upload');
        Route::post('upload', [ModuleUploadController::class, 'store'])->name('upload.store');
        Route::post('{slug}/migrate', [ModuleUploadController::class, 'migrate'])->name('migrate');
        Route::post('{slug}/sync', [ModuleUploadController::class, 'sync'])->name('sync');
        Route::post('{slug}/enable', [ModuleUploadController::class, 'enable'])->name('enable');
        Route::post('{slug}/disable', [ModuleUploadController::class, 'disable'])->name('disable');
        Route::delete('{slug}', [ModuleUploadController::class, 'destroy'])->name('destroy'); // (optionnel)

        // Positions des modules
        Route::get('positions', [ModulePositionController::class, 'index'])->name('positions');
        Route::post('positions/assign', [ModulePositionController::class, 'assign'])->name('positions.assign');
        Route::post('positions/{hook}/toggle', [ModulePositionController::class, 'toggle'])->name('positions.toggle');
        Route::patch('positions/{hook}/priority', [ModulePositionController::class, 'updatePriority'])->name('positions.update-priority');
        Route::post('positions/bulk-priority', [ModulePositionController::class, 'updateBulkPriorities'])->name('positions.bulk-priority');
        Route::delete('positions/{hook}', [ModulePositionController::class, 'destroy'])->name('positions.destroy');
    });
