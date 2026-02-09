<?php

declare(strict_types=1);

namespace Omersia\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Omersia\Admin\Support\Modules\ModuleManager;
use Omersia\Core\Models\Module as ModuleModel;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use ZipArchive;

class ModuleUploadController extends Controller
{
    protected string $modulesPath;

    public function __construct()
    {
        $this->modulesPath = base_path('packages/Modules');
        File::ensureDirectoryExists($this->modulesPath);
    }

    public function index(ModuleManager $manager)
    {
        // Manifests présents sur disque
        $all = $manager->all(); // [slug => manifest + base_path]
        // États DB
        $states = ModuleModel::select('slug', 'enabled', 'version')->get()->keyBy('slug');

        // Map pour la vue
        $modules = collect($all)
            ->map(function ($m) use ($states) {
                $state = $states[$m['slug']] ?? null;
                $iconPath = ($m['base_path'] ?? '').'/icon.png';
                $iconUrl = File::exists($iconPath)
                    ? asset("modules/{$m['vendor']}/{$m['slug']}/icon.png")
                    : asset('images/modules/module-no-icon.png');

                return [
                    'slug' => $m['slug'],
                    'title' => $m['name'] ?? $m['slug'],
                    'author' => $m['author'] ?? null,
                    'version' => $m['version'] ?? ($state->version ?? '—'),
                    'enabled' => (bool) optional($state)->enabled,
                    'description' => $m['description'] ?? null,
                    'vendor' => $m['vendor'] ?? (str_contains($m['name'] ?? '', '/') ? explode('/', $m['name'])[0] : 'Custom'),
                    'providers' => $m['providers'] ?? [],
                    'permissions' => $m['permissions'] ?? [],
                    'menu' => $m['menu'] ?? [],
                    'base_path' => $m['base_path'] ?? null,
                    'icon_url' => $iconUrl,
                ];
            })
            ->sortByDesc('enabled')   // actifs en premier
            ->values();

        return view('admin::modules.index', compact('modules'));
    }

    public function migrate(string $slug)
    {
        // Migration ciblée du module (si tu veux un bouton rapide)
        $path = $this->guessModulePathFromSlug($slug);
        if (! $path) {
            return redirect()
                ->route('admin.modules.index')->withErrors(['migrate' => "Chemin introuvable pour {$slug}."]);
        }
        $migPath = $path.'/database/migrations';
        if (! File::isDirectory($migPath)) {
            return redirect()
                ->route('admin.modules.index')->withErrors(['migrate' => "Aucune migration pour {$slug}."]);
        }
        Artisan::call('migrate', ['--path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $migPath), '--force' => true]);

        return redirect()
            ->route('admin.modules.index')->with('success', "Migrations exécutées pour {$slug}.");
    }

    public function create()
    {
        return view('admin::modules.upload');
    }

    public function store(Request $request, ModuleManager $manager)
    {
        $request->validate([
            'zip' => ['required', 'file', 'mimes:zip', 'max:51200'], // 50 Mo
            'activate' => ['nullable', 'boolean'],
            'migrate' => ['nullable', 'boolean'],
            'seed' => ['nullable', 'boolean'],
        ]);

        $zipPath = $request->file('zip')->storeAs(
            'modules/tmp',
            'mod_'.bin2hex(random_bytes(16)).'.zip'
        );
        $absZip = storage_path('app/'.$zipPath);

        // 1) Extraire vers un dossier temporaire
        $tmpExtract = storage_path('app/modules/tmp/'.uniqid('extract_'));
        File::ensureDirectoryExists($tmpExtract);

        $zip = new ZipArchive;
        if ($zip->open($absZip) !== true) {
            return redirect()
                ->route('admin.modules.index')->withErrors(['zip' => 'Impossible d’ouvrir l’archive.']);
        }

        // Protection Zip Slip
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains($name, '../') || str_starts_with($name, '/')) {
                $zip->close();

                return redirect()
                    ->route('admin.modules.index')->withErrors(['zip' => 'Archive invalide (chemins non sûrs).']);
            }
        }
        $zip->extractTo($tmpExtract);

        // Vérifier la taille totale extraite (protection zip bomb)
        $maxExtractedSize = 100 * 1024 * 1024; // 100 MB max
        $totalSize = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpExtract, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $totalSize += $file->getSize();
            if ($totalSize > $maxExtractedSize) {
                File::deleteDirectory($tmpExtract);
                File::delete($absZip);

                return redirect()
                    ->route('admin.modules.index')
                    ->withErrors(['zip' => 'Archive trop volumineuse une fois décompressée (max 100 MB).']);
            }
        }

        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar', 'sh', 'bash', 'exe', 'bat'];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                // Les fichiers PHP sont autorisés seulement dans src/, app/, ou database/
                if (in_array($ext, $dangerousExtensions)) {
                    $relativePath = str_replace($tmpExtract, '', $file->getPathname());
                    // Pattern accepte: /src/, /app/, /database/, ou /ModuleName/src/, /ModuleName/app/, /ModuleName/database/
                    if (! preg_match('#^/(src|app|database)/#', $relativePath) &&
                        ! preg_match('#^/[^/]+/(src|app|database)/#', $relativePath)) {
                        File::deleteDirectory($tmpExtract);
                        File::delete($absZip);

                        return redirect()
                            ->route('admin.modules.index')
                            ->withErrors(['zip' => "Fichier non autorisé détecté: {$relativePath}"]);
                    }
                }
            }
        }

        $zip->close();

        // 2) Trouver le dossier racine du module (qui contient module.json)
        $manifestPath = $this->findManifest($tmpExtract);
        if (! $manifestPath) {
            File::deleteDirectory($tmpExtract);

            return redirect()
                ->route('admin.modules.index')->withErrors(['zip' => 'module.json introuvable dans l’archive.']);
        }
        $moduleRoot = dirname($manifestPath);

        // 3) Lire manifest
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (! is_array($manifest) || empty($manifest['slug']) || empty($manifest['name'])) {
            File::deleteDirectory($tmpExtract);

            return redirect()
                ->route('admin.modules.index')->withErrors(['zip' => 'Manifest invalide: "slug" et "name" requis.']);
        }

        // 4) Déterminer Vendor/Name
        // Convention: module dans packages/Modules/Vendor/Name
        // Si "vendor" et "package" sont dans le manifest, on les respecte,
        // sinon on split "name" par "/" (ex: acme/blog).
        [$vendor, $name] = $this->resolveVendorName($manifest);

        $destPath = $this->modulesPath.'/'.$vendor.'/'.$name;
        if (File::exists($destPath)) {
            // Évite d’écraser sans confirmation. (Tu peux gérer un flag "overwrite" si tu veux.)
            File::deleteDirectory($tmpExtract);

            return redirect()
                ->route('admin.modules.index')->withErrors(['zip' => "Le module {$vendor}/{$name} existe déjà."]);
        }

        File::ensureDirectoryExists(dirname($destPath));
        File::moveDirectory($moduleRoot, $destPath);

        // 4.1) Extract storefront components if present in module (like themes do)
        // Re-open ZIP to check for storefront-components/ folder
        $moduleHooksConfig = null;
        $zipForComponents = new ZipArchive;
        if ($zipForComponents->open($absZip) === true) {
            $moduleHooksConfig = $this->extractStorefrontComponents($zipForComponents, $manifest['slug']);
            $zipForComponents->close();
        }

        // 4.2) Fallback: extract from installed module directory if ZIP extraction didn't work
        if (! $moduleHooksConfig && File::isDirectory($destPath.'/storefront-components')) {
            $moduleHooksConfig = $this->extractStorefrontComponentsFromDirectory(
                $destPath.'/storefront-components',
                $manifest['slug']
            );
        }

        $iconSource = $destPath.'/icon.png';
        $iconDest = public_path("images/modules/{$manifest['slug']}.png");

        if (File::exists($iconSource)) {
            File::ensureDirectoryExists(public_path('images/modules'));
            File::copy($iconSource, $iconDest);
        }

        File::deleteDirectory($tmpExtract);
        File::delete($absZip);

        // 5) Enregistrer/mettre à jour en DB
        $module = ModuleModel::updateOrCreate(
            ['slug' => $manifest['slug']],
            [
                'name' => $manifest['name'],
                'version' => $manifest['version'] ?? null,
                'enabled' => (bool) $request->boolean('activate'),
                'manifest' => $manifest,
            ]
        );

        // 6) Purger caches modules pour re-scan
        $manager->flush();

        // 7) Exécuter migrations (optionnel)
        if ($request->boolean('migrate')) {
            $migrationsPath = "packages/Modules/{$vendor}/{$name}/database/migrations";
            if (File::isDirectory(base_path($migrationsPath))) {
                Artisan::call('migrate', ['--path' => $migrationsPath, '--force' => true]);
            }
        }

        // 8) Seeder (optionnel)
        if ($request->boolean('seed')) {
            // Enregistrer l'autoloader pour ce module AVANT d'appeler le seeder
            $prefix = $vendor.'\\'.$name.'\\';
            $src = base_path("packages/Modules/{$vendor}/{$name}/src/");

            spl_autoload_register(function (string $class) use ($prefix, $src) {
                if (! str_starts_with($class, $prefix)) {
                    return;
                }
                $relative = substr($class, strlen($prefix));
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative).'.php';
                $file = $src.$relativePath;
                if (is_file($file)) {
                    require_once $file;
                }
            });

            // Convention: Seeder nommé {Name}Seeder dans namespace du module
            $seederClass = "\\{$vendor}\\{$name}\\Database\\Seeders\\{$name}Seeder";
            if (class_exists($seederClass)) {
                Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);
            }
        }

        // 9) Register module hooks in database if module-config.json was found
        if ($moduleHooksConfig && isset($moduleHooksConfig['hooks'])) {
            $this->registerModuleHooks($manifest['slug'], $moduleHooksConfig['hooks']);
        }

        // 10) Generate API routes if defined in module-config.json
        if ($moduleHooksConfig && isset($moduleHooksConfig['apiRoutes'])) {
            $this->generateApiRoutes($manifest['slug'], $moduleHooksConfig['apiRoutes']);
        }

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Module {$manifest['name']} importé".($module->enabled ? ' et activé' : '').' ✔');
    }

    /**
     * Re-synchronize a module's storefront components and hooks
     * Useful for manually installed modules or after manual updates
     */
    public function sync(string $slug)
    {
        $modulePath = $this->guessModulePathFromSlug($slug);
        if (! $modulePath) {
            return redirect()
                ->route('admin.modules.index')
                ->withErrors(['sync' => "Module {$slug} introuvable."]);
        }

        // Check if module has storefront-components
        $componentsSrc = $modulePath.'/storefront-components';
        if (! File::isDirectory($componentsSrc)) {
            return redirect()
                ->route('admin.modules.index')
                ->withErrors(['sync' => "Module {$slug} n'a pas de composants storefront."]);
        }

        // Extract components and get module config
        $moduleHooksConfig = $this->extractStorefrontComponentsFromDirectory($componentsSrc, $slug);

        // Register hooks if found
        if ($moduleHooksConfig && isset($moduleHooksConfig['hooks'])) {
            $this->registerModuleHooks($slug, $moduleHooksConfig['hooks']);
        }

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Module {$slug} synchronisé avec succès ✔");
    }

    public function enable(string $slug, ModuleManager $manager)
    {
        ModuleModel::where('slug', $slug)->update(['enabled' => true]);
        $manager->flush();

        return redirect()
            ->route('admin.modules.index')->with('success', "Module {$slug} activé.");
    }

    public function disable(string $slug, ModuleManager $manager)
    {
        ModuleModel::where('slug', $slug)->update(['enabled' => false]);
        $manager->flush();

        return redirect()
            ->route('admin.modules.index')->with('success', "Module {$slug} désactivé.");
    }

    public function destroy(string $slug, ModuleManager $manager)
    {
        // 1. Récupérer le module DB et le manifest
        $mod = ModuleModel::where('slug', $slug)->firstOrFail();
        $manifest = $mod->manifest ?? [];

        // 2. Deviner le chemin du module (pour trouver ses migrations AVANT de supprimer le dossier)
        $path = $this->guessModulePathFromSlug($slug);
        $migrationNames = [];

        if ($path) {
            $migPath = $path.'/database/migrations';

            if (File::isDirectory($migPath)) {
                // On récupère les noms de fichiers sans .php
                foreach (File::files($migPath) as $file) {
                    $filename = $file->getFilename();
                    $migrationNames[] = pathinfo($filename, PATHINFO_FILENAME);
                }
            }
        }

        // 3. Drop des tables déclarées dans uninstall.tables
        if (! empty($manifest['uninstall']['tables']) && is_array($manifest['uninstall']['tables'])) {
            foreach ($manifest['uninstall']['tables'] as $table) {
                if (Schema::hasTable($table)) {
                    Schema::dropIfExists($table);
                }
            }
        }

        // 4. Nettoyer la table `migrations` pour les migrations de ce module
        if (! empty($migrationNames) && Schema::hasTable('migrations')) {
            DB::table('migrations')
                ->whereIn('migration', $migrationNames)
                ->delete();
        }

        // 4.1. Nettoyer les shipping methods créés par le module
        if (! empty($manifest['uninstall']['cleanup']['shipping_methods']) && Schema::hasTable('shipping_methods')) {
            $codes = $manifest['uninstall']['cleanup']['shipping_methods'];

            // Supprimer les shipping_method_rates liés
            if (Schema::hasTable('shipping_method_rates')) {
                $methodIds = DB::table('shipping_methods')
                    ->whereIn('code', $codes)
                    ->pluck('id');

                DB::table('shipping_method_rates')
                    ->whereIn('shipping_method_id', $methodIds)
                    ->delete();
            }

            // Supprimer les shipping_zones liés
            if (Schema::hasTable('shipping_zones')) {
                $methodIds = DB::table('shipping_methods')
                    ->whereIn('code', $codes)
                    ->pluck('id');

                DB::table('shipping_zones')
                    ->whereIn('shipping_method_id', $methodIds)
                    ->delete();
            }

            // Supprimer les shipping methods
            DB::table('shipping_methods')
                ->whereIn('code', $codes)
                ->delete();
        }

        // 4.2. Supprimer les hooks du module
        \Omersia\Core\Models\ModuleHook::where('module_slug', $slug)->delete();

        // 4.3. Supprimer les composants storefront
        $storefrontComponentDir = is_dir('/var/www/storefront')
            ? '/var/www/storefront/src/components/modules/'.$slug
            : base_path('../storefront/src/components/modules/'.$slug);
        if (File::isDirectory($storefrontComponentDir)) {
            File::deleteDirectory($storefrontComponentDir);
        }

        // 4.4. Supprimer les API routes générées par le module
        $this->deleteModuleApiRoutes($slug, $path);

        // 5. Supprimer l'entrée en base des modules
        $mod->delete();

        // 6. Supprimer les fichiers du module (optionnel et sensible)
        if ($path && str_starts_with($path, $this->modulesPath) && File::isDirectory($path)) {
            File::deleteDirectory($path);
        }

        // 7. Synchroniser les hooks après suppression
        $this->syncHooksToStorefront();

        // 8. Flush cache modules
        $manager->flush();

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Module {$slug} supprimé (fichiers, tables, migrations, hooks, routes API et composants storefront).");
    }

    // Helpers
    protected function findManifest(string $root): ?string
    {
        // Cherche module.json à la racine ou un niveau en dessous
        $candidates = glob($root.'/module.json');
        if (! empty($candidates)) {
            return $candidates[0];
        }
        $candidates = glob($root.'/*/module.json');

        return $candidates[0] ?? null;
    }

    protected function resolveVendorName(array $manifest): array
    {
        if (! empty($manifest['vendor']) && ! empty($manifest['package'])) {
            return [$manifest['vendor'], $manifest['package']];
        }
        // fallback: "name": "acme/blog" -> ["Acme","Blog"]
        if (str_contains($manifest['name'], '/')) {
            [$v, $p] = explode('/', $manifest['name'], 2);

            return [ucfirst($v), ucfirst($p)];
        }

        // dernier recours: slug -> vendor "Custom", name Studly(slug)
        return ['Custom', str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $manifest['slug'])))];
    }

    protected function guessModulePathFromSlug(string $slug): ?string
    {
        foreach (glob($this->modulesPath.'/*/*/module.json') as $manifest) {
            $json = json_decode(file_get_contents($manifest), true);
            if (($json['slug'] ?? null) === $slug) {
                return dirname($manifest);
            }
        }

        return null;
    }

    /**
     * Extract storefront components from module ZIP (similar to theme installation)
     * Looks for storefront-components/ folder in ZIP and extracts to storefront
     * Returns the module-config.json content if found
     */
    protected function extractStorefrontComponents(ZipArchive $zip, string $moduleSlug): ?array
    {
        // Check if storefront-components/ folder exists in ZIP
        $hasComponents = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (strpos($filename, 'storefront-components/') !== false) {
                $hasComponents = true;
                break;
            }
        }

        if (! $hasComponents) {
            return null;
        }

        // Extract to storefront/src/components/modules/{slug}/
        // In Docker, storefront is mounted at /var/www/storefront (not ../storefront)
        $storefrontBasePath = '/var/www/storefront/src/components/modules';
        if (! is_dir('/var/www/storefront')) {
            // Fallback for non-Docker environments
            $storefrontBasePath = base_path('../storefront/src/components/modules');
        }
        $moduleComponentDir = $storefrontBasePath.'/'.$moduleSlug;

        if (! is_dir($moduleComponentDir)) {
            mkdir($moduleComponentDir, 0755, true);
        }

        $moduleConfig = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            $pos = strpos($filename, 'storefront-components/');
            if ($pos === false) {
                continue;
            }

            // Relative path from "storefront-components/"
            $relativePath = substr($filename, $pos + strlen('storefront-components/'));

            // Skip directories
            if (empty($relativePath) || str_ends_with($relativePath, '/')) {
                continue;
            }

            $destPath = $moduleComponentDir.'/'.$relativePath;
            $destDir = dirname($destPath);

            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $fileContent = $zip->getFromIndex($i);
            if ($fileContent !== false) {
                file_put_contents($destPath, $fileContent);

                // If this is module-config.json, parse it
                if (basename($relativePath) === 'module-config.json') {
                    $moduleConfig = json_decode($fileContent, true);
                }
            }
        }

        return $moduleConfig;
    }

    /**
     * Extract storefront components from an already installed module directory
     * Used as fallback when ZIP extraction doesn't work or for manually installed modules
     */
    protected function extractStorefrontComponentsFromDirectory(string $sourcePath, string $moduleSlug): ?array
    {
        if (! File::isDirectory($sourcePath)) {
            return null;
        }

        // Determine storefront destination path
        $storefrontBasePath = is_dir('/var/www/storefront')
            ? '/var/www/storefront/src/components/modules'
            : base_path('../storefront/src/components/modules');

        $destPath = $storefrontBasePath.'/'.$moduleSlug;

        // Create destination directory
        if (! File::isDirectory($destPath)) {
            File::makeDirectory($destPath, 0755, true);
        }

        // Copy all files from source to destination (exclude documentation)
        $moduleConfig = null;
        $files = File::allFiles($sourcePath);
        $excludedExtensions = ['md', 'txt', 'pdf'];
        $copiedCount = 0;

        foreach ($files as $file) {
            // Skip documentation files
            $extension = strtolower($file->getExtension());
            if (in_array($extension, $excludedExtensions)) {
                continue;
            }

            $relativePath = $file->getRelativePathname();
            $destFile = $destPath.'/'.$relativePath;
            $destDir = dirname($destFile);

            if (! File::isDirectory($destDir)) {
                File::makeDirectory($destDir, 0755, true);
            }

            File::copy($file->getPathname(), $destFile);
            $copiedCount++;

            // Parse module-config.json if found
            if ($file->getFilename() === 'module-config.json') {
                $moduleConfig = json_decode(File::get($file->getPathname()), true);
            }
        }

        Log::info("Extracted storefront components from directory for {$moduleSlug}", [
            'files' => $copiedCount,
            'source' => $sourcePath,
            'destination' => $destPath,
        ]);

        return $moduleConfig;
    }

    /**
     * Register module hooks in database from module-config.json
     */
    protected function registerModuleHooks(string $moduleSlug, array $hooks): void
    {
        // Delete existing hooks for this module
        \Omersia\Core\Models\ModuleHook::where('module_slug', $moduleSlug)->delete();

        // Register new hooks
        foreach ($hooks as $hook) {
            \Omersia\Core\Models\ModuleHook::create([
                'module_slug' => $moduleSlug,
                'hook_name' => $hook['hookName'] ?? '',
                'component_path' => $hook['componentPath'] ?? '',
                'condition' => $hook['condition'] ?? null,
                'priority' => $hook['priority'] ?? 100,
                'is_active' => true,
            ]);
        }

        // Synchronize hooks to storefront
        $this->syncHooksToStorefront();
    }

    /**
     * Synchronize all module hooks to storefront/public/module-hooks.json
     */
    protected function syncHooksToStorefront(): void
    {
        // Get all active hooks
        $hooks = \Omersia\Core\Models\ModuleHook::active()
            ->with('module')
            ->orderBy('hook_name')
            ->orderBy('priority')
            ->get();

        // Group by module
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

        // Write to storefront config file
        $storefrontConfigPath = is_dir('/var/www/storefront')
            ? '/var/www/storefront/public/module-hooks.json'
            : base_path('../storefront/public/module-hooks.json');

        try {
            if (! is_dir(dirname($storefrontConfigPath))) {
                mkdir(dirname($storefrontConfigPath), 0755, true);
            }

            file_put_contents(
                $storefrontConfigPath,
                json_encode(array_values($moduleConfigs), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            Log::info('Module hooks synchronized to storefront', [
                'modules' => count($moduleConfigs),
                'hooks' => count($hooks),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to sync hooks to storefront: '.$e->getMessage());
        }

        // Also sync module types
        $this->syncModuleTypesToStorefront();
    }

    /**
     * Synchronize module types to storefront/src/lib/module-types.ts
     * Reads module-config.json from each active module to find exported types
     */
    protected function syncModuleTypesToStorefront(): void
    {
        $storefrontBasePath = is_dir('/var/www/storefront')
            ? '/var/www/storefront/src/components/modules'
            : base_path('../storefront/src/components/modules');

        $storefrontTypesPath = is_dir('/var/www/storefront')
            ? '/var/www/storefront/src/lib/module-types.ts'
            : base_path('../storefront/src/lib/module-types.ts');

        // Get all active modules with hooks
        $modules = \Omersia\Core\Models\ModuleHook::active()
            ->with('module')
            ->get()
            ->pluck('module_slug')
            ->unique();

        $typeExports = [];
        foreach ($modules as $moduleSlug) {
            $moduleConfigPath = $storefrontBasePath.'/'.$moduleSlug.'/module-config.json';

            if (! File::exists($moduleConfigPath)) {
                continue;
            }

            $moduleConfig = json_decode(File::get($moduleConfigPath), true);

            // Check if module exports types
            if (isset($moduleConfig['types']) && isset($moduleConfig['types']['exports'])) {
                $typesFile = $moduleConfig['types']['typesFile'] ?? 'types.ts';
                $exports = $moduleConfig['types']['exports'];

                if (is_array($exports) && count($exports) > 0) {
                    $typeExports[$moduleSlug] = [
                        'typesFile' => $typesFile,
                        'exports' => $exports,
                    ];
                }
            }
        }

        // Generate module-types.ts file
        $content = $this->generateModuleTypesContent($typeExports);

        try {
            if (! is_dir(dirname($storefrontTypesPath))) {
                mkdir(dirname($storefrontTypesPath), 0755, true);
            }

            file_put_contents($storefrontTypesPath, $content);

            Log::info('Module types synchronized to storefront', [
                'modules' => count($typeExports),
                'types_file' => $storefrontTypesPath,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to sync module types to storefront: '.$e->getMessage());
        }
    }

    /**
     * Generate content for module-types.ts file
     */
    protected function generateModuleTypesContent(array $typeExports): string
    {
        $content = <<<'TS'
/**
 * Module Types Registry
 *
 * This file provides a type-safe way to access types exported by modules.
 * Types are re-exported from module directories and made available globally.
 *
 * When a module is installed, its types are automatically added here.
 * When a module is uninstalled, its types are automatically removed.
 *
 * This file is auto-generated by the module sync system.
 * DO NOT EDIT MANUALLY - changes will be overwritten on next sync.
 */

TS;

        if (empty($typeExports)) {
            $content .= "\n// No modules with exported types currently installed\n";

            return $content;
        }

        foreach ($typeExports as $moduleSlug => $config) {
            $typesImportPath = '@/components/modules/'.$moduleSlug.'/'.str_replace('.ts', '', $config['typesFile']);
            $exports = implode(', ', $config['exports']);

            $content .= "\n// {$moduleSlug} module types\n";
            $content .= "export type { {$exports} } from '{$typesImportPath}';\n";
        }

        return $content;
    }

    protected function generateApiRoutes(string $moduleSlug, array $apiRoutes): void
    {
        $storefrontBasePath = is_dir('/var/www/storefront')
            ? '/var/www/storefront/src/app/api/modules'
            : base_path('../storefront/src/app/api/modules');

        foreach ($apiRoutes as $route) {
            $path = $route['path'] ?? '';
            $backendEndpoint = $route['backendEndpoint'] ?? '';
            $method = strtoupper($route['method'] ?? 'GET');

            if (! $path || ! $backendEndpoint) {
                Log::warning('Skipping invalid API route: missing path or backendEndpoint');

                continue;
            }

            // Create directory structure for the route under /api/modules/{moduleSlug}/{path}
            $routePath = $storefrontBasePath.'/'.$moduleSlug.'/'.$path;
            if (! File::isDirectory($routePath)) {
                File::makeDirectory($routePath, 0755, true);
            }

            // Generate route.ts file
            $routeFile = $routePath.'/route.ts';
            $routeContent = $this->generateApiRouteContent($backendEndpoint, $method, $moduleSlug);

            File::put($routeFile, $routeContent);
            Log::info("Generated API route for module {$moduleSlug}: /api/modules/{$moduleSlug}/{$path}");
        }
    }

    protected function generateApiRouteContent(string $backendEndpoint, string $method, string $moduleSlug): string
    {
        $backendUrl = env('BACKEND_URL', 'http://backend');
        $apiKey = env('FRONT_API_KEY', '');

        // Determine if we need to include request body (POST, PUT, PATCH)
        $includeBody = in_array($method, ['POST', 'PUT', 'PATCH']);
        $bodyCode = $includeBody
            ? "const body = await request.text();\n\n    "
            : '';
        $bodyParam = $includeBody
            ? ",\n      body,"
            : '';

        return <<<TS
/**
 * API Route generated by module: {$moduleSlug}
 * This file is auto-generated by the module sync system.
 * DO NOT EDIT MANUALLY - changes will be overwritten on next sync.
 */

import { NextRequest, NextResponse } from 'next/server';

export async function {$method}(request: NextRequest) {
  try {
    // Extract query parameters
    const { searchParams } = new URL(request.url);
    const queryString = searchParams.toString();

    // Build backend URL
    const backendUrl = process.env.BACKEND_URL || '{$backendUrl}';
    const endpoint = '{$backendEndpoint}';
    const url = `\${backendUrl}\${endpoint}\${queryString ? '?' + queryString : ''}`;
    {$bodyCode}// Forward request to backend
    const response = await fetch(url, {
      method: '{$method}',
      headers: {
        'X-API-KEY': process.env.FRONT_API_KEY || '{$apiKey}',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      }{$bodyParam}
    });

    const data = await response.json();

    return NextResponse.json(data, { status: response.status });
  } catch (error) {
    console.error('API route error:', error);
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}

TS;
    }

    protected function deleteModuleApiRoutes(string $moduleSlug, ?string $modulePath): void
    {
        // Supprimer tout le dossier du module dans /api/modules/{moduleSlug}
        // Même si le module-config.json n'existe pas/plus
        $storefrontBasePath = is_dir('/var/www/storefront')
            ? '/var/www/storefront/src/app/api/modules'
            : base_path('../storefront/src/app/api/modules');

        $moduleApiPath = $storefrontBasePath.'/'.$moduleSlug;
        if (File::isDirectory($moduleApiPath)) {
            File::deleteDirectory($moduleApiPath);
            Log::info("Deleted all API routes for module {$moduleSlug}: /api/modules/{$moduleSlug}");
        }
    }
}
