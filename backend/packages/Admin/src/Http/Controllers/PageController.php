<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Omersia\CMS\Http\Requests\PageStoreRequest;
use Omersia\CMS\Http\Requests\PageUpdateRequest;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Repositories\Contracts\PageRepositoryInterface;
use Omersia\CMS\Services\PageService;

class PageController extends Controller
{
    public function __construct(
        private readonly PageRepositoryInterface $pageRepository,
        private readonly PageService $pageService,
    ) {}

    public function index()
    {
        $this->authorize('pages.view');

        $pages = $this->pageRepository->getByShopId(shopId: 1);

        return view('admin::pages.index', compact('pages'));
    }

    public function create()
    {
        $this->authorize('pages.create');

        return view('admin::pages.create');
    }

    public function store(PageStoreRequest $request)
    {
        $this->pageService->create($request->validated(), shopId: 1);

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
        $this->pageService->update($page, $request->validated());

        return redirect()->route('pages.index')->with('success', 'Page mise à jour.');
    }

    public function destroy(Page $page)
    {
        $this->authorize('pages.delete');

        $this->pageService->delete($page);

        return redirect()->route('pages.index')->with('success', 'Page supprimée.');
    }
}
