<?php

declare(strict_types=1);

namespace Omersia\Apparence\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Apparence\Contracts\ThemeRepositoryInterface;
use Omersia\Apparence\Models\Theme;
use Omersia\Shared\Repositories\BaseRepository;

class ThemeRepository extends BaseRepository implements ThemeRepositoryInterface
{
    public function __construct(Theme $model)
    {
        parent::__construct($model);
    }

    public function getByShopId(int $shopId): Collection
    {
        return $this->model->where('shop_id', $shopId)->get();
    }

    public function getActiveTheme(int $shopId): ?Theme
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->first();
    }

    public function findBySlug(string $slug, ?int $shopId = null): ?Theme
    {
        $query = $this->model->where('slug', $slug);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->first();
    }

    public function setAsActive(int $themeId, int $shopId): bool
    {
        $this->model
            ->where('shop_id', $shopId)
            ->where('id', '!=', $themeId)
            ->update(['is_active' => false]);

        $theme = $this->findOrFail($themeId);

        return $theme->update(['is_active' => true]);
    }

    public function setAsDefault(int $themeId): bool
    {
        $this->model
            ->where('id', '!=', $themeId)
            ->update(['is_default' => false]);

        $theme = $this->findOrFail($themeId);

        return $theme->update(['is_default' => true]);
    }

    public function getDefaultTheme(): ?Theme
    {
        return $this->model->where('is_default', true)->first();
    }

    public function updateSettings(int $themeId, array $settings): bool
    {
        $theme = $this->findOrFail($themeId);

        foreach ($settings as $key => $value) {
            $theme->settings()->updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        return true;
    }

    public function getSetting(int $themeId, string $key, $default = null)
    {
        $theme = $this->findOrFail($themeId);

        return $theme->getSetting($key, $default);
    }

    public function duplicate(int $themeId, string $newName): ?Theme
    {
        $original = $this->with(['settings'])->findOrFail($themeId);

        $newTheme = $this->create([
            'shop_id' => $original->shop_id,
            'name' => $newName,
            'slug' => \Illuminate\Support\Str::slug($newName),
            'description' => $original->description,
            'version' => $original->version,
            'author' => $original->author,
            'preview_image' => $original->preview_image,
            'zip_path' => $original->zip_path,
            'component_path' => $original->component_path,
            'pages_config_path' => $original->pages_config_path,
            'metadata' => $original->metadata,
            'is_active' => false,
            'is_default' => false,
        ]);

        foreach ($original->settings as $setting) {
            $newTheme->settings()->create([
                'key' => $setting->key,
                'value' => $setting->value,
                'group' => $setting->group,
                'type' => $setting->type,
            ]);
        }

        return $newTheme;
    }

    public function install(array $themeData): Theme
    {
        return $this->create($themeData);
    }
}
