<?php

declare(strict_types=1);

namespace Omersia\Core\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Core\Contracts\ShopDomainRepositoryInterface;
use Omersia\Core\Models\ShopDomain;
use Omersia\Shared\Repositories\BaseRepository;

/** @extends BaseRepository<ShopDomain> */
class ShopDomainRepository extends BaseRepository implements ShopDomainRepositoryInterface
{
    public function __construct(ShopDomain $model)
    {
        parent::__construct($model);
    }

    public function findByDomain(string $domain): ?ShopDomain
    {
        return $this->model->where('domain', $domain)->first();
    }

    public function getByShopId(int $shopId): Collection
    {
        return $this->model->where('shop_id', $shopId)->get();
    }

    public function getPrimaryDomain(int $shopId): ?ShopDomain
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->where('is_primary', true)
            ->first();
    }

    public function setAsPrimary(int $domainId): bool
    {
        $domain = $this->findOrFail($domainId);

        $this->model
            ->where('shop_id', $domain->shop_id)
            ->where('id', '!=', $domainId)
            ->update(['is_primary' => false]);

        return $domain->update(['is_primary' => true]);
    }
}
