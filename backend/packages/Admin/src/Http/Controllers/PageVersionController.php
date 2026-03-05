<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;
use Omersia\CMS\Models\PageVersion;
use Omersia\CMS\Services\PageVersioningService;

class PageVersionController extends Controller
{
    public function __construct(
        private readonly PageVersioningService $pageVersioningService,
    ) {}

    public function index(Page $page, Request $request)
    {
        $this->authorize('pages.update');

        $locale = (string) $request->get('locale', 'fr');
        $translation = $this->resolveTranslation($page, $locale);

        if (! $translation) {
            return redirect()
                ->route('pages.edit', $page)
                ->with('error', 'Aucune traduction trouvée pour cette locale.');
        }

        $versions = $this->pageVersioningService->getVersions($translation, 20);
        $currentContent = is_array($translation->content_json) ? $translation->content_json : [];

        $diffsByVersionId = [];
        foreach ($versions as $version) {
            $diffsByVersionId[$version->id] = $this->pageVersioningService->buildVisualDiff(
                is_array($version->content_json) ? $version->content_json : [],
                $currentContent
            );
        }

        return view('admin::pages.versions.index', [
            'page' => $page,
            'translation' => $translation,
            'locale' => $locale,
            'versions' => $versions,
            'diffsByVersionId' => $diffsByVersionId,
        ]);
    }

    public function restore(Page $page, PageVersion $version, Request $request): RedirectResponse
    {
        $this->authorize('pages.update');

        $locale = (string) $request->get('locale', 'fr');
        $translation = $this->resolveTranslation($page, $locale);

        if (! $translation || (int) $version->page_translation_id !== (int) $translation->id) {
            abort(404);
        }

        $this->pageVersioningService->restoreVersion(
            $translation,
            $version,
            $request->user()?->id
        );

        if ($request->boolean('return_to_builder')) {
            return redirect()
                ->route('pages.builder', ['page' => $page->id, 'locale' => $locale])
                ->with('success', 'Version restaurée avec succès.');
        }

        return redirect()
            ->route('pages.versions.index', ['page' => $page->id, 'locale' => $locale])
            ->with('success', 'Version restaurée avec succès.');
    }

    private function resolveTranslation(Page $page, string $locale): ?PageTranslation
    {
        return $page->translations()->where('locale', $locale)->first();
    }
}
