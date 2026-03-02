<?php

declare(strict_types=1);

namespace Omersia\CMS\Services;

use Illuminate\Database\Eloquent\Collection;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;
use Omersia\CMS\Repositories\Contracts\PageRepositoryInterface;

class PageService
{
    public function __construct(
        private readonly PageRepositoryInterface $pageRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $shopId, string $locale = 'fr'): Page
    {
        /** @var Page $page */
        $page = $this->pageRepository->create([
            'shop_id' => $shopId,
            'type' => $data['type'] ?? 'page',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_home' => (bool) ($data['is_home'] ?? false),
        ]);

        $contentJson = isset($data['content_json'])
            ? json_decode((string) $data['content_json'], true)
            : null;

        PageTranslation::create([
            'page_id' => $page->id,
            'locale' => $locale,
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => null,
            'content_json' => $contentJson,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'noindex' => (bool) ($data['noindex'] ?? false),
        ]);

        return $page->load('translations');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Page $page, array $data, string $locale = 'fr'): Page
    {
        $page->update([
            'type' => $data['type'] ?? 'page',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_home' => (bool) ($data['is_home'] ?? false),
        ]);

        $translation = $page->translations()->where('locale', $locale)->first()
            ?? new PageTranslation(['page_id' => $page->id, 'locale' => $locale]);

        $contentJson = isset($data['content_json'])
            ? json_decode((string) $data['content_json'], true)
            : null;

        $translation->fill([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => null,
            'content_json' => $contentJson,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'noindex' => (bool) ($data['noindex'] ?? false),
        ])->save();

        return $page->load('translations');
    }

    /**
     * @return Collection<int, Page>
     */
    public function getPublicPages(string $locale): Collection
    {
        return $this->pageRepository->getActiveByLocale($locale);
    }

    public function getPublicPage(string $slug, string $locale): ?Page
    {
        return $this->pageRepository->findBySlug($slug, $locale, activeOnly: true);
    }

    public function delete(Page $page): bool
    {
        return (bool) $page->delete();
    }

    /**
     * @param  array<string, mixed>  $layout
     */
    public function saveBuilderLayout(Page $page, array $layout, string $locale = 'fr'): PageTranslation
    {
        /** @var PageTranslation $translation */
        $translation = $page->translations()->firstOrCreate(
            ['locale' => $locale],
            ['title' => 'Page', 'slug' => 'page-'.$page->id],
        );

        $translation->update(['content_json' => $layout]);

        return $translation;
    }
}
