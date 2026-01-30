<?php

use Illuminate\Support\Facades\Route;
use Omersia\Gdpr\Http\Controllers\Api\CookieConsentController;
use Omersia\Gdpr\Http\Controllers\Api\DataRequestController;

/*
|--------------------------------------------------------------------------
| GDPR API Routes
|--------------------------------------------------------------------------
*/

// Cookie Consent (public)
Route::get('/cookie-consent', [CookieConsentController::class, 'show'])->name('gdpr.cookie-consent.show');
Route::post('/cookie-consent', [CookieConsentController::class, 'store'])->name('gdpr.cookie-consent.store');
Route::get('/cookie-consent/check/{type}', [CookieConsentController::class, 'check'])->name('gdpr.cookie-consent.check');

// Data Requests (authentifié)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/data-requests', [DataRequestController::class, 'index'])->name('gdpr.data-requests.index');
    Route::post('/data-requests', [DataRequestController::class, 'store'])->name('gdpr.data-requests.store');
    Route::get('/data-requests/{id}/download', [DataRequestController::class, 'download'])->name('gdpr.data-requests.download');
});
