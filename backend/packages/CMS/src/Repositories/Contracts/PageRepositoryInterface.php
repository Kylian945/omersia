<?php

declare(strict_types=1);

namespace Omersia\CMS\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Omersia\CMS\Models\Page;
use Omersia\Shared\Contracts\RepositoryInterface;

/** @extends RepositoryInterface<Page> */
interface PageRepositoryInterface extends RepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Page>
     */
    public function getByShopId(int $shopId, int $perPage = 25): LengthAwarePaginator;

    /**
     * @return Collection<int, Page>
     */
    public function getActiveByLocale(
        string $locale,
        bool $publishedOnly = true,
        bool $activeOnly = true
    ): Collection;

    public function findBySlug(
        string $slug,
        string $locale,
        bool $activeOnly = true,
        bool $publishedOnly = true
    ): ?Page;
}
