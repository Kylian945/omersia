<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Omersia\Api\Http\Controllers\AccountProfileController;
use Omersia\Api\Http\Controllers\AddressController;
use Omersia\Api\Http\Controllers\AuthController;
use Omersia\Api\Http\Controllers\CartController;
use Omersia\Api\Http\Controllers\CategoryController;
use Omersia\Api\Http\Controllers\EcommercePageApiController;
use Omersia\Api\Http\Controllers\MenuController;
use Omersia\Api\Http\Controllers\OrderController;
use Omersia\Api\Http\Controllers\PageController;
use Omersia\Api\Http\Controllers\PaymentController;
use Omersia\Api\Http\Controllers\ProductController;
use Omersia\Api\Http\Controllers\SearchController;
use Omersia\Api\Http\Controllers\ShippingMethodController;
use Omersia\Api\Http\Controllers\ShopController;
use Omersia\Api\Http\Controllers\TaxCalculatorController;
use Omersia\Api\Http\Controllers\ThemeApiController;

Route::middleware('api.key')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | SHOP
    |--------------------------------------------------------------------------
    */
    Route::get('/shop/info', [ShopController::class, 'info'])
        ->name('storefront.shop.info');

    /*
    |--------------------------------------------------------------------------
    | THEME
    |--------------------------------------------------------------------------
    */
    Route::get('/theme/settings', [ThemeApiController::class, 'settings'])
        ->name('storefront.theme.settings');

    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */
    Route::middleware(['throttle:auth'])->prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum');
        Route::get('/me', [AuthController::class, 'me'])
            ->middleware('auth:sanctum');

        // Password reset (stricter rate limiting)
        Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:password-reset');
        Route::post('/password/reset', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:password-reset');
    });

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */
    Route::prefix('account')->middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AccountProfileController::class, 'show']);
        Route::put('/profile', [AccountProfileController::class, 'update']);
    });

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */
    Route::middleware(['throttle:search'])->get('/search', [SearchController::class, 'search'])
        ->name('storefront.search');

    /*
    |--------------------------------------------------------------------------
    | PRODUCTS
    |--------------------------------------------------------------------------
    */
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */
    Route::get('/pages', [PageController::class, 'index'])
        ->name('storefront.pages.index');
    Route::get('/pages/{slug}', [PageController::class, 'show'])
        ->name('storefront.pages.show');

    /*
    |--------------------------------------------------------------------------
    | ECOMMERCE PAGES
    |--------------------------------------------------------------------------
    */
    Route::get('/ecommerce-pages/{slug}', [EcommercePageApiController::class, 'getBySlug'])
        ->name('storefront.ecommerce-pages.show');
    Route::get('/ecommerce-pages/{type}/{slug?}', [EcommercePageApiController::class, 'show'])
        ->name('storefront.ecommerce-pages.show-by-type');

    /*
    |--------------------------------------------------------------------------
    | MENUS
    |--------------------------------------------------------------------------
    */
    Route::get('/menus/{slug}', [MenuController::class, 'show'])
        ->name('storefront.menus.show');

    /*
    |--------------------------------------------------------------------------
    | CATEGORIES
    |--------------------------------------------------------------------------
    */
    Route::get('/categories', [CategoryController::class, 'index'])
        ->name('storefront.categories.index');
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])
        ->name('storefront.categories.show');

    /*
    |--------------------------------------------------------------------------
    | ZONE AUTHENTIFIÉE (client connecté)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // ADDRESSES
        Route::get('/addresses', [AddressController::class, 'index'])
            ->name('storefront.addresses.index');
        Route::post('/addresses', [AddressController::class, 'store'])
            ->name('storefront.addresses.store');
        Route::get('/addresses/{id}', [AddressController::class, 'show'])
            ->name('storefront.addresses.show');
        Route::put('/addresses/{id}', [AddressController::class, 'update'])
            ->name('storefront.addresses.update');
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy'])
            ->name('storefront.addresses.destroy');
        Route::post('/addresses/{id}/default-shipping', [AddressController::class, 'setDefaultShipping'])
            ->name('storefront.addresses.setDefaultShipping');
        Route::post('/addresses/{id}/default-billing', [AddressController::class, 'setDefaultBilling'])
            ->name('storefront.addresses.setDefaultBilling');

        // ORDERS (listing + show par numéro)
        Route::get('/orders', [OrderController::class, 'index'])
            ->name('storefront.orders.index');
        Route::get('/orders/{number}', [OrderController::class, 'show'])
            ->name('storefront.orders.show');
        Route::get('/orders/{number}/invoice', [OrderController::class, 'downloadInvoice'])
            ->name('storefront.orders.invoice');

        // DCA-003: Rate limiting strict sur checkout/payment pour éviter abus
        Route::middleware('throttle:checkout')->group(function () {
            // Création de commande
            Route::post('/orders', [OrderController::class, 'store'])
                ->name('storefront.orders.store');

            // Mise à jour de commande (checkout)
            Route::put('/orders/{order}', [OrderController::class, 'update'])
                ->name('storefront.orders.update');

            // Confirmation de commande brouillon (après paiement réussi)
            Route::post('/orders/{id}/confirm', [OrderController::class, 'confirmOrder'])
                ->name('storefront.orders.confirm');

            // Payment intent (Stripe)
            Route::post('/payments/intent', [PaymentController::class, 'createIntent'])
                ->name('storefront.payments.intent');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | CHECKOUT ORDER ENDPOINT - CURRENTLY INACTIVE
    |--------------------------------------------------------------------------
    | CheckoutOrderController exists but is NOT registered (incomplete implementation).
    |
    | IF ACTIVATING THIS ENDPOINT IN THE FUTURE:
    | - MUST use auth:sanctum middleware (NOT just api.key)
    | - Route example: Route::middleware(['api.key', 'auth:sanctum'])->post('/checkout/order', [CheckoutOrderController::class, 'store']);
    | - See: packages/Api/tests/Feature/CheckoutAuthorizationTest.php for security requirements
    | - The api.key middleware does NOT authenticate users ($request->user() returns null)
    | - Guest checkout MUST NOT use this endpoint (use different flow)
    |
    | Security: This endpoint was vulnerable to IDOR (DCA-001) before fix
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | CART (non authentifié ok, mais protégé par api.key)
    |--------------------------------------------------------------------------
    */
    Route::post('/cart/sync', [CartController::class, 'sync']);
    Route::post('/cart/apply-discount', [CartController::class, 'applyDiscount']);
    Route::post('/cart/apply-automatic-discounts', [CartController::class, 'applyAutomaticDiscounts']);

    /*
    |--------------------------------------------------------------------------
    | TAX CALCULATION
    |--------------------------------------------------------------------------
    */
    Route::post('/calculate-tax', [TaxCalculatorController::class, 'calculate']);
    Route::post('/calculate-included-tax', [TaxCalculatorController::class, 'calculateIncludedTax']);

    /*
    |--------------------------------------------------------------------------
    | SHIPPING METHODS
    |--------------------------------------------------------------------------
    */
    Route::get('/shipping-methods', [ShippingMethodController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | PAYMENT METHODS
    |--------------------------------------------------------------------------
    */
    Route::get('/payment-methods', [PaymentController::class, 'getAvailableMethods']);
});
