<?php

declare(strict_types=1);

namespace Omersia\Core\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Core\Models\Shop;
use Omersia\Shared\Contracts\RepositoryInterface;

interface ShopRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Shop;

    public function findByDomain(string $domain): ?Shop;

    public function getActiveShops(): Collection;

    public function getDefaultShop(): ?Shop;

    public function updateSettings(int $shopId, array $settings): bool;
}
