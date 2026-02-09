<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ShippingMethod;
use Omersia\Catalog\Models\TaxRate;
use Omersia\Core\Models\ApiKey;
use Omersia\Payment\Models\PaymentProvider;

class SettingsController extends Controller
{
    /**
     * Page d'accueil des paramètres (grille de cartes : API keys, thèmes, etc.)
     */
    public function index()
    {
        $this->authorize('settings.view');

        // Informations de configuration
        $configData = [
            'api_keys_count' => ApiKey::count(),
            'api_keys_active' => ApiKey::count(),
            'shipping_methods_count' => ShippingMethod::count(),
            'payment_providers_enabled' => PaymentProvider::where('enabled', true)->count(),
            'tax_rates_count' => TaxRate::where('is_active', true)->count(),
            'products_count' => Product::count(),
            'products_indexed' => Product::count(), // Tous les produits sont indexés avec Scout
        ];

        return view('admin::settings.index', compact('configData'));
    }
}
