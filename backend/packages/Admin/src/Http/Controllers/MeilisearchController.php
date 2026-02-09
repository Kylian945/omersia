<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Omersia\Catalog\Models\Product;

class MeilisearchController extends Controller
{
    /**
     * Page de gestion Meilisearch
     */
    public function index()
    {
        $this->authorize('settings.view');

        // Récupérer les stats
        $totalProducts = Product::where('is_active', true)->count();
        $meilisearchConfigured = ! empty(config('scout.meilisearch.host'));

        return view('admin::settings.meilisearch.index', [
            'totalProducts' => $totalProducts,
            'meilisearchConfigured' => $meilisearchConfigured,
            'meilisearchHost' => config('scout.meilisearch.host'),
        ]);
    }

    /**
     * Indexer tous les produits
     */
    public function indexProducts(Request $request)
    {
        $this->authorize('settings.update');

        try {
            Artisan::call('products:index');

            return redirect()
                ->route('admin.settings.meilisearch.index')
                ->with('success', 'Indexation des produits lancée avec succès !');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.meilisearch.index')
                ->with('error', 'Erreur lors de l\'indexation : '.$e->getMessage());
        }
    }

    /**
     * Vider l'index Meilisearch
     */
    public function flushIndex(Request $request)
    {
        $this->authorize('settings.update');

        try {
            Artisan::call('scout:flush', [
                'model' => 'Omersia\\Catalog\\Models\\Product',
            ]);

            return redirect()
                ->route('admin.settings.meilisearch.index')
                ->with('success', 'Index Meilisearch vidé avec succès !');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.meilisearch.index')
                ->with('error', 'Erreur lors du vidage de l\'index : '.$e->getMessage());
        }
    }

    /**
     * Importer tous les produits dans Meilisearch
     */
    public function importAll(Request $request)
    {
        $this->authorize('settings.update');

        try {
            // Utiliser la commande personnalisée products:index
            // au lieu de scout:import pour éviter les problèmes
            Artisan::call('products:index');

            return redirect()
                ->route('admin.settings.meilisearch.index')
                ->with('success', 'Import des produits dans Meilisearch lancé avec succès !');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.meilisearch.index')
                ->with('error', 'Erreur lors de l\'import : '.$e->getMessage());
        }
    }

    /**
     * Configurer les paramètres de l'index Meilisearch
     */
    public function configureIndex(Request $request)
    {
        $this->authorize('settings.update');

        try {
            Artisan::call('products:meili-config');

            return redirect()
                ->route('admin.settings.meilisearch.index')
                ->with('success', 'Configuration de l\'index Meilisearch mise à jour avec succès !');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.meilisearch.index')
                ->with('error', 'Erreur lors de la configuration : '.$e->getMessage());
        }
    }
}
