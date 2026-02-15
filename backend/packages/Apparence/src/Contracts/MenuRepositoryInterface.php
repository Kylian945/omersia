<?php

declare(strict_types=1);

namespace Omersia\Apparence\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Apparence\Models\Menu;
use Omersia\Shared\Contracts\RepositoryInterface;

/** @extends RepositoryInterface<Menu> */
interface MenuRepositoryInterface extends RepositoryInterface
{
    public function getByShopId(int $shopId): Collection;

    public function findByLocation(string $location, int $shopId): ?Menu;

    public function createWithItems(array $menuData, array $items): Menu;

    public function updateWithItems(int $menuId, array $menuData, array $items): bool;

    public function deleteWithItems(int $menuId): bool;

    public function duplicateMenu(int $menuId, string $newName): Menu;
}
