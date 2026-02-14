<?php

declare(strict_types=1);

namespace Omersia\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Omersia\Core\Models\Module;
use Omersia\Core\Models\ModuleHook;

class SyncModuleCommand extends Command
{
    protected $signature = 'module:sync {slug? : Module slug to sync (leave empty to sync all)}';

    protected $description = 'Synchronize module components and hooks to storefront';

    public function handle(): int
    {
        $moduleSlug = $this->argument('slug');

        if ($moduleSlug) {
            $this->info("Synchronizing module: {$moduleSlug}");

            return $this->syncModule($moduleSlug);
        }

        $this->info('Synchronizing all modules...');

        return $this->syncAllModules();
    }

    protected function syncModule(string $slug): int
    {
        // 1. Find module in packages/Modules
        $modulePath = $this->findModulePath($slug);
        if (! $modulePath) {
            $this->error("Module {$slug} not found in packages/Modules");

            return Command::FAILURE;
        }

        $this->info("Found module at: {$modulePath}");

        // 2. Extract storefront components if present
        $componentsSrc = $modulePath.'/storefront-components';
        if (! File::isDirectory($componentsSrc)) {
            $this->warn('No storefront-components folder found in module');
        } else {
            $this->syncStorefrontComponents($slug, $componentsSrc);
        }

        // 3. Read module-config.json and register hooks + API routes
        $configPath = $componentsSrc.'/module-config.json';
        if (File::exists($configPath)) {
            $config = json_decode(File::get($configPath), true);
            if ($config && isset($config['hooks'])) {
                $this->registerModuleHooks($slug, $config['hooks']);
                $this->info('Registered '.count($config['hooks']).' hooks');
            }
            if ($config && isset($config['apiRoutes'])) {
                $this->generateApiRoutes($slug, $config['apiRoutes']);
                $this->info('Generated '.count($config['apiRoutes']).' API routes');
            }
        } else {
            $this->warn('No module-config.json found');
        }

        // 4. Sync all hooks to storefront
        $this->syncHooksToStorefront();

        $this->info("✓ Module {$slug} synchronized successfully");

        return Command::SUCCESS;
    }

    protected function syncAllModules(): int
    {
        $modulesPath = base_path('packages/Modules');
        if (! File::isDirectory($modulesPath)) {
            $this->error("Modules directory not found: {$modulesPath}");

            return Command::FAILURE;
        }

        $synced = 0;
        $vendors = File::directories($modulesPath);

        foreach ($vendors as $vendorPath) {
            $modules = File::directories($vendorPath);
            foreach ($modules as $modulePath) {
                $slug = $this->guessSlugFromPath($modulePath);
                $this->info("Syncing {$slug}...");
                if ($this->syncModule($slug) === Command::SUCCESS) {
                    $synced++;
                }
            }
        }

        $this->info("✓ Synchronized {$synced} modules");

        return Command::SUCCESS;
    }

    protected function syncStorefrontComponents(string $slug, string $sourcePath): void
    {
        $storefrontBasePath = is_dir('/var/www/storefront')
            ? '/var/www/storefront/src/components/modules'
            : base_path('../storefront/src/components/modules');

        $destPath = $storefrontBasePath.'/'.$slug;

        // Create directory if not exists
        if (! File::isDirectory($destPath)) {
            File::makeDirectory($destPath, 0755, true);
        }

        // Copy all files from storefront-components to destination (exclude docs)
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
        }

        $this->info("✓ Copied {$copiedCount} component files to storefront");
    }

    protected function registerModuleHooks(string $moduleSlug, array $hooks): void
    {
        // Delete existing hooks for this module
        ModuleHook::where('module_slug', $moduleSlug)->delete();

        // Register new hooks
        foreach ($hooks as $hook) {
            ModuleHook::create([
                'module_slug' => $moduleSlug,
                'hook_name' => $hook['hookName'] ?? '',
                'component_path' => $hook['componentPath'] ?? '',
                'condition' => $hook['condition'] ?? null,
                'priority' => $hook['priority'] ?? 100,
                'is_active' => true,
            ]);
        }
    }

    protected function syncHooksToStorefront(): void
    {
        // Get all active hooks
        $hooks = ModuleHook::active()
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

            $this->info('✓ Generated module-hooks.json with '.count($moduleConfigs).' modules');

            Log::info('Module hooks synchronized to storefront', [
                'modules' => count($moduleConfigs),
                'hooks' => count($hooks),
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to sync hooks to storefront: '.$e->getMessage());
            Log::warning('Failed to sync hooks to storefront: '.$e->getMessage());
        }
    }

    protected function findModulePath(string $slug): ?string
    {
        $modulesPath = base_path('packages/Modules');
        $vendors = File::directories($modulesPath);

        foreach ($vendors as $vendorPath) {
            $modules = File::directories($vendorPath);
            foreach ($modules as $modulePath) {
                if ($this->guessSlugFromPath($modulePath) === $slug) {
                    return $modulePath;
                }
            }
        }

        return null;
    }

    protected function guessSlugFromPath(string $path): string
    {
        $moduleName = basename($path);

        // Convert PascalCase to kebab-case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $moduleName));
    }

    protected function generateApiRoutes(string $moduleSlug, array $apiRoutes): void
    {
        $storefrontBasePath = is_dir('/var/www/storefront')
            ? '/var/www/storefront/src/app/api'
            : base_path('../storefront/src/app/api');

        foreach ($apiRoutes as $route) {
            $path = $route['path'] ?? '';
            $backendEndpoint = $route['backendEndpoint'] ?? '';
            $method = strtoupper($route['method'] ?? 'GET');

            if (! $path || ! $backendEndpoint) {
                $this->warn('Skipping invalid API route: missing path or backendEndpoint');

                continue;
            }

            // Create directory structure for the route
            $routePath = $storefrontBasePath.'/'.$path;
            if (! File::isDirectory($routePath)) {
                File::makeDirectory($routePath, 0755, true);
            }

            // Generate route.ts file
            $routeFile = $routePath.'/route.ts';
            $routeContent = $this->generateApiRouteContent($backendEndpoint, $method, $moduleSlug);

            File::put($routeFile, $routeContent);
            $this->info("✓ Generated API route: /api/{$path}");
        }
    }

    protected function generateApiRouteContent(string $backendEndpoint, string $method, string $moduleSlug): string
    {
        $backendUrl = (string) config('storefront.backend_url', 'http://backend');

        return <<<TS
/**
 * API Route generated by module: {$moduleSlug}
 * This file is auto-generated by the module sync system.
 * DO NOT EDIT MANUALLY - changes will be overwritten on next sync.
 */

import { NextRequest, NextResponse } from 'next/server';

export async function {$method}(request: NextRequest) {
  try {
    const apiKey = process.env.FRONT_API_KEY;
    if (!apiKey) {
      return NextResponse.json(
        { error: 'FRONT_API_KEY is not configured' },
        { status: 500 }
      );
    }

    // Extract query parameters
    const { searchParams } = new URL(request.url);
    const queryString = searchParams.toString();

    // Build backend URL
    const backendUrl = process.env.BACKEND_URL || '{$backendUrl}';
    const endpoint = '{$backendEndpoint}';
    const url = `\${backendUrl}\${endpoint}\${queryString ? '?' + queryString : ''}`;

    // Forward request to backend
    const response = await fetch(url, {
      method: '{$method}',
      headers: {
        'X-API-KEY': apiKey,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
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
}
