<?php

declare(strict_types=1);

namespace Omersia\Apparence\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Shared\Contracts\RepositoryInterface;

interface EcommercePageRepositoryInterface extends RepositoryInterface
{
    public function getByShopId(int $shopId): Collection;

    public function findByType(string $type, int $shopId): ?EcommercePage;

    public function findBySlug(string $slug, int $shopId, ?string $locale = null): ?EcommercePage;

    public function getPublishedPages(int $shopId): Collection;

    public function updatePageConfig(int $pageId, array $config): bool;

    public function duplicatePage(int $pageId, string $newTitle): EcommercePage;

    public function getPagesByType(string $type, int $shopId): Collection;
}
