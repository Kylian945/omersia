<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    /**
     * Page de gestion de la performance et du cache
     */
    public function index(): View
    {
        $this->authorize('settings.view');

        return view('admin::settings.performance.index');
    }

    /**
     * Vider tous les caches en une seule action
     */
    public function clearAll(): RedirectResponse
    {
        $this->authorize('settings.manage');

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('optimize:clear');
            Artisan::call('event:clear');

            return redirect()
                ->route('admin.settings.performance.index')
                ->with('success', 'Tous les caches ont été vidés avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.performance.index')
                ->with('error', 'Erreur lors du vidage des caches : '.$e->getMessage());
        }
    }

    /**
     * Vider le cache de l'application
     */
    public function clearCache(): RedirectResponse
    {
        $this->authorize('settings.manage');

        try {
            Artisan::call('cache:clear');

            return redirect()
                ->route('admin.settings.performance.index')
                ->with('success', 'Le cache de l\'application a été vidé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.performance.index')
                ->with('error', 'Erreur lors du vidage du cache : '.$e->getMessage());
        }
    }

    /**
     * Vider le cache de configuration
     */
    public function clearConfig(): RedirectResponse
    {
        $this->authorize('settings.manage');

        try {
            Artisan::call('config:clear');

            return redirect()
                ->route('admin.settings.performance.index')
                ->with('success', 'Le cache de configuration a été vidé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.performance.index')
                ->with('error', 'Erreur lors du vidage du cache de configuration : '.$e->getMessage());
        }
    }

    /**
     * Vider le cache des routes
     */
    public function clearRoute(): RedirectResponse
    {
        $this->authorize('settings.manage');

        try {
            Artisan::call('route:clear');

            return redirect()
                ->route('admin.settings.performance.index')
                ->with('success', 'Le cache des routes a été vidé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.performance.index')
                ->with('error', 'Erreur lors du vidage du cache des routes : '.$e->getMessage());
        }
    }

    /**
     * Vider le cache des vues
     */
    public function clearView(): RedirectResponse
    {
        $this->authorize('settings.manage');

        try {
            Artisan::call('view:clear');

            return redirect()
                ->route('admin.settings.performance.index')
                ->with('success', 'Le cache des vues a été vidé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.performance.index')
                ->with('error', 'Erreur lors du vidage du cache des vues : '.$e->getMessage());
        }
    }

    /**
     * Vider le cache optimisé
     */
    public function clearOptimize(): RedirectResponse
    {
        $this->authorize('settings.manage');

        try {
            Artisan::call('optimize:clear');

            return redirect()
                ->route('admin.settings.performance.index')
                ->with('success', 'Le cache optimisé a été vidé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.performance.index')
                ->with('error', 'Erreur lors du vidage du cache optimisé : '.$e->getMessage());
        }
    }

    /**
     * Vider le cache des events
     */
    public function clearEvent(): RedirectResponse
    {
        $this->authorize('settings.manage');

        try {
            Artisan::call('event:clear');

            return redirect()
                ->route('admin.settings.performance.index')
                ->with('success', 'Le cache des events a été vidé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.performance.index')
                ->with('error', 'Erreur lors du vidage du cache des events : '.$e->getMessage());
        }
    }
}
