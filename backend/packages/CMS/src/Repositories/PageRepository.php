<?php

declare(strict_types=1);

namespace Omersia\CMS\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Repositories\Contracts\PageRepositoryInterface;
use Omersia\Shared\Repositories\BaseRepository;

/** @extends BaseRepository<Page> */
class PageRepository extends BaseRepository implements PageRepositoryInterface
{
    public function __construct(Page $model)
    {
        parent::__construct($model);
    }

    /**
     * @return LengthAwarePaginator<int, Page>
     */
    public function getByShopId(int $shopId, int $perPage = 25): LengthAwarePaginator
    {
        return $this->model
            ->with('translations')
            ->where('shop_id', $shopId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Page>
     */
    public function getActiveByLocale(string $locale): Collection
    {
        /** @var Collection<int, Page> */
        return $this->model
            ->where('is_active', true)
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->get();
    }

    public function findBySlug(string $slug, string $locale, bool $activeOnly = true): ?Page
    {
        $query = $this->model->newQuery();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query
            ->whereHas('translations', fn ($q) => $q->where('locale', $locale)->where('slug', $slug))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->first();
    }
}
