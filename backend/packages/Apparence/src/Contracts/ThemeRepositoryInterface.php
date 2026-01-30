<?php

declare(strict_types=1);

namespace Omersia\Apparence\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Apparence\Models\Theme;
use Omersia\Shared\Contracts\RepositoryInterface;

interface ThemeRepositoryInterface extends RepositoryInterface
{
    public function getByShopId(int $shopId): Collection;

    public function getActiveTheme(int $shopId): ?Theme;

    public function findBySlug(string $slug, ?int $shopId = null): ?Theme;

    public function setAsActive(int $themeId, int $shopId): bool;

    public function setAsDefault(int $themeId): bool;

    public function getDefaultTheme(): ?Theme;

    public function updateSettings(int $themeId, array $settings): bool;

    public function getSetting(int $themeId, string $key, $default = null);

    public function duplicate(int $themeId, string $newName): ?Theme;

    public function install(array $themeData): Theme;
}
