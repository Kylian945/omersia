<?php

declare(strict_types=1);

namespace Omersia\Admin\Support\Modules;

use Illuminate\Support\Facades\Cache;
use Omersia\Core\Models\Module as ModuleModel;

class ModuleManager
{
    public function __construct(protected ?string $path = null)
    {
        $this->path = $path ?? base_path('packages/Modules');
    }

    public function all(): array
    {
        try {
            return Cache::remember('modules.all', 60, function () {
                return $this->scanModules();
            });
        } catch (\Exception $e) {
            // Cache indisponible (ex: Redis non accessible sur le host lors de package:discover)
            return $this->scanModules();
        }
    }

    private function scanModules(): array
    {
        $modules = [];
        foreach (glob($this->path.'/*/*/module.json') as $manifest) {
            $json = json_decode(file_get_contents($manifest), true) ?: [];
            $json['base_path'] = dirname($manifest);
            $modules[$json['slug']] = $json;
        }

        return $modules;
    }

    public function enabled(): array
    {
        $all = $this->all();

        try {
            $enabledSlugs = ModuleModel::where('enabled', true)->pluck('slug')->all();

            return array_intersect_key($all, array_flip($enabledSlugs));
        } catch (\Exception $e) {
            // Si la table n'existe pas encore (migrations non exécutées), retourner un tableau vide
            return [];
        }
    }

    public function flush(): void
    {
        Cache::forget('modules.all');
    }
}
