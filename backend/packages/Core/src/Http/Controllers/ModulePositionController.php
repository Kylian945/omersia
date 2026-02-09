<?php

declare(strict_types=1);

namespace Omersia\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Omersia\Core\Models\Module;
use Omersia\Core\Models\ModuleHook;

class ModulePositionController
{
    /**
     * Afficher la page des positions de modules
     */
    public function index()
    {
        // Récupérer tous les hooks groupés par position
        $hooksByPosition = ModuleHook::getGroupedByPosition();

        // Récupérer les labels de positions
        $positionLabels = ModuleHook::getHookLabels();

        // Récupérer tous les modules actifs
        $activeModules = Module::where('enabled', true)->pluck('name', 'slug');

        return view('admin::modules.positions', compact('hooksByPosition', 'positionLabels', 'activeModules'));
    }

    /**
     * Activer/désactiver un hook
     */
    public function toggle(int $hookId)
    {
        $hook = ModuleHook::findOrFail($hookId);
        $hook->is_active = ! $hook->is_active;
        $hook->save();

        // Synchroniser avec le storefront
        $this->syncToStorefront($hook);

        return redirect()
            ->route('admin.modules.positions')
            ->with('success', 'Hook '.($hook->is_active ? 'activé' : 'désactivé').' avec succès.');
    }

    /**
     * Mettre à jour la priorité d'un hook
     */
    public function updatePriority(Request $request, int $hookId)
    {
        $validated = $request->validate([
            'priority' => 'required|integer|min:0|max:999',
        ]);

        $hook = ModuleHook::findOrFail($hookId);
        $hook->priority = $validated['priority'];
        $hook->save();

        // Synchroniser avec le storefront
        $this->syncToStorefront($hook);

        return redirect()
            ->route('admin.modules.positions')
            ->with('success', 'Priorité mise à jour avec succès.');
    }

    /**
     * Mettre à jour plusieurs priorités à la fois (drag & drop)
     */
    public function updateBulkPriorities(Request $request)
    {
        $validated = $request->validate([
            'hooks' => 'required|array',
            'hooks.*.id' => 'required|integer|exists:module_hooks,id',
            'hooks.*.priority' => 'required|integer|min:0|max:999',
        ]);

        foreach ($validated['hooks'] as $hookData) {
            $hook = ModuleHook::find($hookData['id']);
            if ($hook) {
                $hook->priority = $hookData['priority'];
                $hook->save();

                // Synchroniser avec le storefront
                $this->syncToStorefront($hook);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Priorités mises à jour avec succès.',
        ]);
    }

    /**
     * Assigner un module à un hook
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'hook_name' => 'required|string',
            'module_slug' => 'required|string|exists:modules,slug',
            'priority' => 'required|integer|min:0|max:999',
        ]);

        // Vérifier que le module est activé
        $module = Module::where('slug', $validated['module_slug'])
            ->where('enabled', true)
            ->firstOrFail();

        // Vérifier si ce module n'est pas déjà sur ce hook
        $existingHook = ModuleHook::where('module_slug', $validated['module_slug'])
            ->where('hook_name', $validated['hook_name'])
            ->first();

        if ($existingHook) {
            return redirect()
                ->route('admin.modules.positions')
                ->with('error', 'Ce module est déjà assigné à ce hook.');
        }

        // Récupérer le manifest pour obtenir le vendor et le package
        $manifest = $module->manifest ?? [];
        $vendor = $manifest['vendor'] ?? 'Omersia';
        $package = $manifest['package'] ?? $module->slug;

        // Charger la configuration du module pour trouver le composant
        // Le chemin correct est: packages/Modules/{vendor}/{package}/storefront-components/module-config.json
        $moduleConfigPath = base_path("packages/Modules/{$vendor}/{$package}/storefront-components/module-config.json");

        if (! file_exists($moduleConfigPath)) {
            // Fallback: chercher dans tous les dossiers
            $modulesBasePath = base_path('packages/Modules');
            $vendors = glob($modulesBasePath.'/*', GLOB_ONLYDIR);
            $moduleConfigPath = null;

            foreach ($vendors as $vendorPath) {
                // Essayer avec le package name
                $configPath = $vendorPath.'/'.$package.'/storefront-components/module-config.json';
                if (file_exists($configPath)) {
                    $moduleConfigPath = $configPath;
                    break;
                }

                // Essayer avec le slug
                $configPath = $vendorPath.'/'.$module->slug.'/storefront-components/module-config.json';
                if (file_exists($configPath)) {
                    $moduleConfigPath = $configPath;
                    break;
                }

                // Chercher tous les sous-dossiers du vendor
                $moduleFolders = glob($vendorPath.'/*', GLOB_ONLYDIR);
                foreach ($moduleFolders as $moduleFolder) {
                    $configPath = $moduleFolder.'/storefront-components/module-config.json';
                    if (file_exists($configPath)) {
                        // Vérifier que c'est bien le bon module en lisant le module.json
                        $moduleJsonPath = $moduleFolder.'/module.json';
                        if (file_exists($moduleJsonPath)) {
                            $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);
                            if (($moduleJson['slug'] ?? null) === $module->slug) {
                                $moduleConfigPath = $configPath;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        if (! $moduleConfigPath || ! file_exists($moduleConfigPath)) {
            Log::error('Configuration module introuvable', [
                'module_slug' => $module->slug,
                'vendor' => $vendor,
                'package' => $package,
                'expected_path' => base_path("packages/Modules/{$vendor}/{$package}/storefront-components/module-config.json"),
            ]);

            return redirect()
                ->route('admin.modules.positions')
                ->with('error', 'Configuration du module introuvable.');
        }

        $moduleConfig = json_decode(file_get_contents($moduleConfigPath), true);

        // Trouver le premier composant disponible du module
        // On permet d'assigner n'importe quel module sur n'importe quel hook
        $componentPath = null;
        if (! empty($moduleConfig['hooks'])) {
            // Essayer de trouver le composant pour ce hook spécifique
            foreach ($moduleConfig['hooks'] as $hookConfig) {
                if ($hookConfig['hookName'] === $validated['hook_name']) {
                    $componentPath = $hookConfig['componentPath'];
                    break;
                }
            }

            // Si pas trouvé, utiliser le premier composant disponible
            if (! $componentPath) {
                $componentPath = $moduleConfig['hooks'][0]['componentPath'];
            }
        }

        if (! $componentPath) {
            return redirect()
                ->route('admin.modules.positions')
                ->with('error', 'Aucun composant trouvé pour ce module.');
        }

        // Créer le hook
        ModuleHook::create([
            'module_slug' => $validated['module_slug'],
            'hook_name' => $validated['hook_name'],
            'component_path' => $componentPath,
            'priority' => $validated['priority'],
            'is_active' => true,
        ]);

        // Synchroniser avec le storefront
        $this->syncAllHooksToStorefront();

        return redirect()
            ->route('admin.modules.positions')
            ->with('success', 'Module assigné au hook avec succès.');
    }

    /**
     * Supprimer un hook
     */
    public function destroy(int $hookId)
    {
        $hook = ModuleHook::findOrFail($hookId);
        $hook->delete();

        // Synchroniser avec le storefront (suppression)
        $this->syncAllHooksToStorefront();

        return redirect()
            ->route('admin.modules.positions')
            ->with('success', 'Hook supprimé avec succès.');
    }

    /**
     * Synchroniser un hook avec le storefront
     * Génère le fichier de configuration des hooks pour le frontend
     */
    protected function syncToStorefront(ModuleHook $hook): void
    {
        $this->syncAllHooksToStorefront();
    }

    /**
     * Synchroniser tous les hooks avec le storefront
     * Génère un fichier JSON que le frontend peut charger
     */
    protected function syncAllHooksToStorefront(): void
    {
        // Récupérer tous les hooks actifs
        $hooks = ModuleHook::active()
            ->with('module')
            ->orderBy('hook_name')
            ->orderBy('priority')
            ->get();

        // Grouper par module
        $moduleConfigs = [];
        foreach ($hooks as $hook) {
            if (! isset($moduleConfigs[$hook->module_slug])) {
                $moduleConfigs[$hook->module_slug] = [
                    'slug' => $hook->module_slug,
                    'name' => $hook->module->name ?? $hook->module_slug,
                    'version' => $hook->module->version ?? '1.0.0',
                    'hooks' => [],
                ];
            }

            $moduleConfigs[$hook->module_slug]['hooks'][] = [
                'hookName' => $hook->hook_name,
                'componentPath' => $hook->component_path,
                'condition' => $hook->condition,
                'priority' => $hook->priority,
            ];
        }

        // Écrire dans le fichier de configuration du storefront
        $storefrontConfigPath = is_dir('/var/www/storefront')
            ? '/var/www/storefront/public/module-hooks.json'
            : base_path('../storefront/public/module-hooks.json');

        try {
            file_put_contents(
                $storefrontConfigPath,
                json_encode(array_values($moduleConfigs), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        } catch (\Exception $e) {
            Log::warning('Impossible de synchroniser les hooks avec le storefront: '.$e->getMessage());
        }
    }
}
