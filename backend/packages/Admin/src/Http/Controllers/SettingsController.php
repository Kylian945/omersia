<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Omersia\Ai\Models\AiProvider;
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

        $aiProvidersCount = 0;
        $aiProvidersEnabled = 0;
        $aiDefaultProvider = 'Non défini';

        if (Schema::hasTable('ai_providers')) {
            $aiProvidersCount = AiProvider::count();
            $aiProvidersEnabled = AiProvider::where('is_enabled', true)->count();
            $aiDefaultProvider = (string) (AiProvider::where('is_default', true)->value('name') ?? 'Non défini');
        }

        // Informations de configuration
        $configData = [
            'api_keys_count' => ApiKey::count(),
            'api_keys_active' => ApiKey::count(),
            'shipping_methods_count' => ShippingMethod::count(),
            'payment_providers_enabled' => PaymentProvider::where('enabled', true)->count(),
            'tax_rates_count' => TaxRate::where('is_active', true)->count(),
            'products_count' => Product::count(),
            'products_indexed' => Product::count(), // Tous les produits sont indexés avec Scout
            'ai_providers_count' => $aiProvidersCount,
            'ai_providers_enabled' => $aiProvidersEnabled,
            'ai_default_provider' => $aiDefaultProvider,
        ];

        return view('admin::settings.index', compact('configData'));
    }
}
