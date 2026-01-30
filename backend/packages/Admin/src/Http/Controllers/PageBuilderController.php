<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Admin\Config\BuilderWidgets;
use Omersia\CMS\Models\Page;

class PageBuilderController extends Controller
{
    public function edit(Page $page, Request $request)
    {
        $this->authorize('pages.update');

        $locale = $request->get('locale', 'fr');

        $translation = $page->translations()->where('locale', $locale)->first();

        $content = $translation?->content_json ?? ['sections' => []];

        return view('admin::builder.builder', [
            'page' => $page,
            'locale' => $locale,
            'contentJson' => $content,
            'pageTitle' => 'Builder de page CMS',
            'pageTitleHeader' => 'Builder : '.($page->translations->first()->title ?? 'Page'),
            'saveUrl' => route('pages.builder.update', ['page' => $page->id, 'locale' => $locale]),
            'backUrl' => route('pages.index'),
            'widgetCategories' => BuilderWidgets::grouped(),
            'categoryLabels' => BuilderWidgets::categoryLabels(),
        ]);
    }

    public function update(Page $page, Request $request)
    {
        $this->authorize('pages.update');

        $locale = $request->get('locale', 'fr');

        $request->validate([
            'content_json' => ['required', 'string'],
        ]);

        $decoded = json_decode($request->input('content_json'), true);

        $translation = $page->translations()->firstOrCreate(
            ['locale' => $locale],
            ['title' => $page->slug ?? 'Page', 'slug' => $page->slug ?? 'page-'.$page->id]
        );

        $translation->update(['content_json' => $decoded]);

        return redirect()
            ->route('pages.builder', ['page' => $page->id, 'locale' => $locale])
            ->with('success', 'Contenu mis à jour avec succès.');
    }
}
