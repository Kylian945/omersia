<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Omersia\Admin\Http\Requests\PageStoreRequest;
use Omersia\Admin\Http\Requests\PageUpdateRequest;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;

class PageController extends Controller
{
    public function index()
    {
        $this->authorize('pages.view');

        $pages = Page::with('translations')
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('admin::pages.index', compact('pages'));
    }

    public function create()
    {
        $this->authorize('pages.create');

        return view('admin::pages.create');
    }

    public function store(PageStoreRequest $request)
    {
        $validated = $request->validated();

        $page = Page::create([
            'shop_id' => 1,
            'type' => $validated['type'] ?? 'page',
            'is_active' => $request->boolean('is_active', true),
            'is_home' => $request->boolean('is_home', false),
        ]);

        $contentJson = $validated['content_json'] ?? null;
        $content = $contentJson ? json_decode($contentJson, true) : null;

        PageTranslation::create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'content' => null,
            'content_json' => $content,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'noindex' => $request->boolean('noindex', false),
        ]);

        return redirect()->route('pages.index')->with('success', 'Page créée.');
    }

    public function edit(Page $page)
    {
        $this->authorize('pages.update');

        $page->load('translations');

        return view('admin::pages.edit', compact('page'));
    }

    public function update(PageUpdateRequest $request, Page $page)
    {
        $validated = $request->validated();

        $page->update([
            'type' => $validated['type'] ?? 'page',
            'is_active' => $request->boolean('is_active', true),
            'is_home' => $request->boolean('is_home', false),
        ]);

        $translation = $page->translations()
            ->where('locale', 'fr')
            ->first();

        if (! $translation) {
            $translation = new PageTranslation([
                'page_id' => $page->id,
                'locale' => 'fr',
            ]);
        }

        $contentJson = $validated['content_json'] ?? null;
        $content = $contentJson ? json_decode($contentJson, true) : null;

        $translation->fill([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'content' => null,
            'content_json' => $content,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'noindex' => $request->boolean('noindex', false),
        ])->save();

        return redirect()->route('pages.index')
            ->with('success', 'Page mise à jour.');
    }

    public function destroy(Page $page)
    {
        $this->authorize('pages.delete');

        $page->delete();

        return redirect()->route('pages.index')
            ->with('success', 'Page supprimée.');
    }
}
