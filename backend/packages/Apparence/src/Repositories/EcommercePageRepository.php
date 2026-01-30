<?php

declare(strict_types=1);

namespace Omersia\Apparence\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Apparence\Contracts\EcommercePageRepositoryInterface;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Shared\Repositories\BaseRepository;

class EcommercePageRepository extends BaseRepository implements EcommercePageRepositoryInterface
{
    public function __construct(EcommercePage $model)
    {
        parent::__construct($model);
    }

    public function getByShopId(int $shopId): Collection
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->with('translations')
            ->get();
    }

    public function findByType(string $type, int $shopId): ?EcommercePage
    {
        return $this->model
            ->where('type', $type)
            ->where('shop_id', $shopId)
            ->with('translations')
            ->first();
    }

    public function findBySlug(string $slug, int $shopId, ?string $locale = null): ?EcommercePage
    {
        $locale = $locale ?? app()->getLocale();

        return $this->model
            ->where('shop_id', $shopId)
            ->whereHas('translations', function ($query) use ($slug, $locale) {
                $query->where('slug', $slug)
                    ->where('locale', $locale);
            })
            ->with('translations')
            ->first();
    }

    public function getPublishedPages(int $shopId): Collection
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->where('is_published', true)
            ->with('translations')
            ->get();
    }

    public function updatePageConfig(int $pageId, array $config): bool
    {
        $page = $this->findOrFail($pageId);

        return $page->update(['config' => $config]);
    }

    public function duplicatePage(int $pageId, string $newTitle): EcommercePage
    {
        $original = $this->with(['translations'])->findOrFail($pageId);

        $newPage = $this->create([
            'shop_id' => $original->shop_id,
            'type' => $original->type,
            'config' => $original->config,
            'is_published' => false,
        ]);

        foreach ($original->translations as $translation) {
            $newPage->translations()->create([
                'locale' => $translation->locale,
                'title' => $newTitle,
                'slug' => \Illuminate\Support\Str::slug($newTitle).'-'.uniqid(),
                'meta_title' => $translation->meta_title,
                'meta_description' => $translation->meta_description,
            ]);
        }

        return $newPage->fresh('translations');
    }

    public function getPagesByType(string $type, int $shopId): Collection
    {
        return $this->model
            ->where('type', $type)
            ->where('shop_id', $shopId)
            ->with('translations')
            ->get();
    }
}
