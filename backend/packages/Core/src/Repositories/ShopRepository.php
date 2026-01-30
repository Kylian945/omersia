<?php

declare(strict_types=1);

namespace Omersia\Core\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Omersia\Core\Contracts\ShopRepositoryInterface;
use Omersia\Core\Models\Shop;
use Omersia\Shared\Repositories\BaseRepository;

class ShopRepository extends BaseRepository implements ShopRepositoryInterface
{
    public function __construct(Shop $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): ?Shop
    {
        return $this->model->where('code', $code)->first();
    }

    public function findByDomain(string $domain): ?Shop
    {
        return $this->model->whereHas('domains', function ($query) use ($domain) {
            $query->where('domain', $domain);
        })->first();
    }

    public function getActiveShops(): Collection
    {
        // Vérifier si la colonne is_active existe
        if (Schema::hasColumn('shops', 'is_active')) {
            return $this->model->where('is_active', true)->get();
        }

        // Sinon, retourner tous les shops
        return $this->model->all();
    }

    public function getDefaultShop(): ?Shop
    {
        return $this->model->where('is_default', true)->first();
    }

    public function updateSettings(int $shopId, array $settings): bool
    {
        $shop = $this->findOrFail($shopId);

        foreach ($settings as $key => $value) {
            if (in_array($key, $shop->getFillable())) {
                $shop->{$key} = $value;
            }
        }

        return $shop->save();
    }
}
