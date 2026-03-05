<?php

declare(strict_types=1);

namespace Omersia\CMS\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;
use Omersia\CMS\Repositories\Contracts\PageRepositoryInterface;

class PageService
{
    public function __construct(
        private readonly PageRepositoryInterface $pageRepository,
        private readonly PageVersioningService $pageVersioningService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $shopId, string $locale = 'fr', ?int $actorId = null): Page
    {
        $status = $this->resolveStatus($data);

        /** @var Page $page */
        $page = $this->pageRepository->create([
            'shop_id' => $shopId,
            'type' => $data['type'] ?? 'page',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_home' => (bool) ($data['is_home'] ?? false),
            'status' => $status,
            'published_at' => $status === Page::STATUS_PUBLISHED ? now() : null,
            'published_by' => $status === Page::STATUS_PUBLISHED ? $actorId : null,
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

        $this->flushApiCache();

        return $page->load('translations');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Page $page, array $data, string $locale = 'fr', ?int $actorId = null): Page
    {
        $status = $this->resolveStatus($data, $page);
        $pageAttributes = [
            'type' => $data['type'] ?? 'page',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_home' => (bool) ($data['is_home'] ?? false),
            'status' => $status,
        ];

        if ($status === Page::STATUS_PUBLISHED) {
            if ($page->status !== Page::STATUS_PUBLISHED || $page->published_at === null) {
                $pageAttributes['published_at'] = now();
            }
            if ($actorId !== null && ($page->status !== Page::STATUS_PUBLISHED || $page->published_by === null)) {
                $pageAttributes['published_by'] = $actorId;
            }
        } elseif ($status === Page::STATUS_DRAFT) {
            $pageAttributes['published_at'] = null;
            $pageAttributes['published_by'] = null;
        }

        $page->update($pageAttributes);

        $existingTranslation = $page->translations()->where('locale', $locale)->first();

        $contentJson = isset($data['content_json'])
            ? json_decode((string) $data['content_json'], true)
            : null;

        if (
            $existingTranslation instanceof PageTranslation &&
            is_array($existingTranslation->content_json) &&
            $this->pageVersioningService->contentChanged($existingTranslation->content_json, $contentJson)
        ) {
            $this->pageVersioningService->createSnapshot(
                $existingTranslation,
                $actorId,
                'Sauvegarde auto avant modification '.now()->format('d/m/Y H:i')
            );
        }

        $translation = $existingTranslation
            ?? new PageTranslation(['page_id' => $page->id, 'locale' => $locale]);

        $translation->fill([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => null,
            'content_json' => $contentJson,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'noindex' => (bool) ($data['noindex'] ?? false),
        ])->save();

        $this->flushApiCache();

        return $page->load('translations');
    }

    /**
     * @return Collection<int, Page>
     */
    public function getPublicPages(string $locale, bool $includeUnpublished = false): Collection
    {
        return $this->pageRepository->getActiveByLocale(
            $locale,
            publishedOnly: ! $includeUnpublished,
            activeOnly: ! $includeUnpublished
        );
    }

    public function getPublicPage(string $slug, string $locale, bool $includeUnpublished = false): ?Page
    {
        return $this->pageRepository->findBySlug(
            $slug,
            $locale,
            activeOnly: ! $includeUnpublished,
            publishedOnly: ! $includeUnpublished
        );
    }

    public function delete(Page $page): bool
    {
        $deleted = (bool) $page->delete();
        if ($deleted) {
            $this->flushApiCache();
        }

        return $deleted;
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

        if (
            is_array($translation->content_json) &&
            $this->pageVersioningService->contentChanged($translation->content_json, $layout)
        ) {
            $this->pageVersioningService->createSnapshot(
                $translation,
                auth()->id(),
                'Sauvegarde auto avant modification '.now()->format('d/m/Y H:i')
            );
        }

        $translation->update(['content_json' => $layout]);

        $this->flushApiCache();

        return $translation;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveStatus(array $data, ?Page $page = null): string
    {
        $rawStatus = isset($data['status']) ? (string) $data['status'] : null;
        if (is_string($rawStatus) && in_array($rawStatus, Page::STATUSES, true)) {
            return $rawStatus;
        }

        if (array_key_exists('is_active', $data)) {
            return (bool) $data['is_active'] ? Page::STATUS_PUBLISHED : Page::STATUS_DRAFT;
        }

        return $page?->status ?? Page::STATUS_DRAFT;
    }

    private function flushApiCache(): void
    {
        Cache::tags(['pages'])->flush();
        Cache::tags(['menus'])->flush();
    }
}
