<?php

declare(strict_types=1);

namespace Omersia\Apparence\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Apparence\Contracts\MenuRepositoryInterface;
use Omersia\Apparence\Models\Menu;
use Omersia\Shared\Repositories\BaseRepository;

/** @extends BaseRepository<Menu> */
class MenuRepository extends BaseRepository implements MenuRepositoryInterface
{
    public function __construct(Menu $model)
    {
        parent::__construct($model);
    }

    public function getByShopId(int $shopId): Collection
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->with('items')
            ->get();
    }

    public function findByLocation(string $location, int $shopId): ?Menu
    {
        return $this->model
            ->where('location', $location)
            ->where('shop_id', $shopId)
            ->with('items')
            ->first();
    }

    public function createWithItems(array $menuData, array $items): Menu
    {
        /** @var Menu $menu */
        $menu = $this->create($menuData);

        foreach ($items as $itemData) {
            $menu->items()->create($itemData);
        }

        return $menu->fresh('items') ?? $menu->load('items');
    }

    public function updateWithItems(int $menuId, array $menuData, array $items): bool
    {
        /** @var Menu $menu */
        $menu = $this->findOrFail($menuId);

        $menu->update($menuData);

        $menu->items()->delete();

        foreach ($items as $itemData) {
            $menu->items()->create($itemData);
        }

        return true;
    }

    public function deleteWithItems(int $menuId): bool
    {
        /** @var Menu $menu */
        $menu = $this->findOrFail($menuId);
        $menu->items()->delete();

        return $menu->delete();
    }

    public function duplicateMenu(int $menuId, string $newName): Menu
    {
        /** @var Menu $original */
        $original = $this->with(['items'])->findOrFail($menuId);

        /** @var Menu $newMenu */
        $newMenu = $this->create([
            'shop_id' => $original->shop_id,
            'name' => $newName,
            'location' => $original->location.'_copy',
        ]);

        foreach ($original->items as $item) {
            $newMenu->items()->create([
                'label' => $item->label,
                'url' => $item->url,
                'parent_id' => $item->parent_id,
                'position' => $item->position,
            ]);
        }

        return $newMenu->fresh('items') ?? $newMenu->load('items');
    }
}
