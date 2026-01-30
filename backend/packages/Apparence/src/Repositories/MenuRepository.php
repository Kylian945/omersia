<?php

declare(strict_types=1);

namespace Omersia\Apparence\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Apparence\Contracts\MenuRepositoryInterface;
use Omersia\Apparence\Models\Menu;
use Omersia\Shared\Repositories\BaseRepository;

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
        $menu = $this->create($menuData);

        foreach ($items as $itemData) {
            $menu->items()->create($itemData);
        }

        return $menu->fresh('items');
    }

    public function updateWithItems(int $menuId, array $menuData, array $items): bool
    {
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
        $menu = $this->findOrFail($menuId);
        $menu->items()->delete();

        return $menu->delete();
    }

    public function duplicateMenu(int $menuId, string $newName): Menu
    {
        $original = $this->with(['items'])->findOrFail($menuId);

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

        return $newMenu->fresh('items');
    }
}
