<?php

declare(strict_types=1);

namespace Omersia\Core\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Core\Models\ShopDomain;
use Omersia\Shared\Contracts\RepositoryInterface;

/** @extends RepositoryInterface<ShopDomain> */
interface ShopDomainRepositoryInterface extends RepositoryInterface
{
    public function findByDomain(string $domain): ?ShopDomain;

    public function getByShopId(int $shopId): Collection;

    public function getPrimaryDomain(int $shopId): ?ShopDomain;

    public function setAsPrimary(int $domainId): bool;
}
