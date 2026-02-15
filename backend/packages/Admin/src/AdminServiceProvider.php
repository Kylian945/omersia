<?php

declare(strict_types=1);

namespace Omersia\Admin;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Omersia\Admin\Support\Modules\ModuleManager;
use Omersia\Ai\Models\AiProvider;
use Omersia\Core\Repositories\ShopRepository;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Routes core admin
        Route::middleware(['web', 'auth', 'can:access-admin', 'ensure.shop'])
            ->prefix('admin')
            ->group(__DIR__.'/routes/admin.php');

        /** @var ModuleManager $manager */
        $manager = app(ModuleManager::class);

        foreach ($manager->enabled() as $mod) {
            // 1) Autoload dynamique pour ce module
            $this->registerModuleAutoload($mod);

            // 2) Enregistrer les providers déclarés dans module.json
            foreach ($mod['providers'] ?? [] as $provider) {
                if (class_exists($provider)) {
                    $this->app->register($provider);
                } else {
                    Log::warning('[Modules] Provider introuvable', [
                        'provider' => $provider,
                        'slug' => $mod['slug'] ?? null,
                        'base_path' => $mod['base_path'] ?? null,
                    ]);
                }
            }
        }

        // Vues du core admin
        $this->loadViewsFrom(__DIR__.'/resources/views', 'admin');

        // View composer pour injecter les shops actifs dans le layout
        View::composer('admin::layout', function ($view) {
            $shopRepository = app(ShopRepository::class);
            $shops = $shopRepository->getActiveShops();
            $hasConfiguredAiProvider = false;

            if (Schema::hasTable('ai_providers')) {
                $hasConfiguredAiProvider = AiProvider::query()
                    ->where('is_enabled', true)
                    ->get()
                    ->contains(static fn (AiProvider $provider): bool => $provider->hasApiKey());
            }

            $view->with('activeShops', $shops);
            $view->with('hasConfiguredAiProvider', $hasConfiguredAiProvider);
            $view->with('aiProviderSettingsUrl', route('admin.settings.ai.index'));
        });

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Enregistre un autoloader PSR-4 minimaliste pour un module donné.
     *
     * @param  array  $mod  Manifest + base_path (fourni par ModuleManager::all())
     */
    protected function registerModuleAutoload(array $mod): void
    {
        if (empty($mod['base_path'])) {
            return;
        }

        // On essaie de déterminer vendor + package
        $vendor = $mod['vendor'] ?? null;
        $package = $mod['package'] ?? null;

        // Si pas fourni, on essaie à partir de "name": "acme/testmodule"
        if ((! $vendor || ! $package) && ! empty($mod['name']) && str_contains($mod['name'], '/')) {
            [$v, $p] = explode('/', $mod['name'], 2);
            $vendor = $vendor ?: ucfirst($v);
            $package = $package ?: str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $p)));
        }

        if (! $vendor || ! $package) {
            // Impossible de déduire un namespace propre
            return;
        }

        // Exemple: "Acme\TestModule\"
        $prefix = $vendor.'\\'.$package.'\\';

        // Exemple: ".../packages/Modules/Acme/TestModule/src/"
        $src = rtrim($mod['base_path'], '/\\').DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR;

        // On enregistre un autoloader PSR-4 minimal pour CE prefix uniquement
        spl_autoload_register(function (string $class) use ($prefix, $src) {
            // Si la classe ne commence pas par ce prefix, on ignore
            if (! str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix)); // ex: "TestModuleServiceProvider"
            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative).'.php';
            $file = $src.$relativePath;

            if (is_file($file)) {
                require_once $file;
            }
        });
    }
}
