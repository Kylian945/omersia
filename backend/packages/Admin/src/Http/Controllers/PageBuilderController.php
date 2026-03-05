<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Admin\Config\BuilderWidgets;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;
use Omersia\CMS\Services\PageService;
use Omersia\CMS\Services\PageVersioningService;

class PageBuilderController extends Controller
{
    public function __construct(
        private readonly PageService $pageService,
        private readonly PageVersioningService $pageVersioningService,
    ) {}

    public function edit(Page $page, Request $request)
    {
        $this->authorize('pages.update');

        $locale = $request->get('locale', 'fr');

        /** @var PageTranslation|null $translation */
        $translation = $page->translations()->where('locale', $locale)->first();

        $content = ($translation instanceof PageTranslation && is_array($translation->content_json))
            ? $translation->content_json
            : ['sections' => []];

        $versionHistory = null;
        $versionDiffsById = [];
        if ($translation instanceof PageTranslation) {
            $versionHistory = $this->pageVersioningService->getVersions($translation, 10);
            foreach ($versionHistory as $version) {
                $versionDiffsById[$version->id] = $this->pageVersioningService->buildVisualDiff(
                    is_array($version->content_json) ? $version->content_json : [],
                    $content
                );
            }
        }
        $widgets = array_values(BuilderWidgets::all());

        return view('admin::builder.builder', [
            'page' => $page,
            'locale' => $locale,
            'contentJson' => $content,
            'pageTitle' => 'Builder de page CMS',
            'pageTitleHeader' => 'Builder : '.($page->translations->first()->title ?? 'Page'),
            'saveUrl' => route('pages.builder.update', ['page' => $page->id, 'locale' => $locale]),
            'backUrl' => route('pages.index'),
            'widgets' => $widgets,
            'widgetCategories' => BuilderWidgets::grouped(),
            'categoryLabels' => BuilderWidgets::categoryLabels(),
            'versionHistory' => $versionHistory,
            'versionDiffsById' => $versionDiffsById,
        ]);
    }

    public function update(Page $page, Request $request)
    {
        $this->authorize('pages.update');

        $locale = $request->get('locale', 'fr');

        $request->validate([
            'content_json' => ['required', 'string'],
        ]);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($request->input('content_json'), true);

        $this->pageService->saveBuilderLayout($page, $decoded, $locale);

        return redirect()
            ->route('pages.builder', ['page' => $page->id, 'locale' => $locale])
            ->with('success', 'Contenu mis à jour avec succès.');
    }
}
