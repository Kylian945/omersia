<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Apparence\Http\Requests\MenuItemStoreRequest;
use Omersia\Apparence\Http\Requests\MenuItemUpdateRequest;
use Omersia\Apparence\Http\Requests\MenuStoreRequest;
use Omersia\Apparence\Models\Menu;
use Omersia\Apparence\Models\MenuItem;
use Omersia\Catalog\Models\Category;
use Omersia\CMS\Models\Page;

class MenuController extends Controller
{
    /**
     * Récupère le menu courant en fonction du paramètre `menu`.
     * - Si `menu` est fourni => on tente de le charger.
     * - Sinon => on prend le premier menu existant.
     * - Si aucun menu => on crée un menu "main" par défaut.
     */
    protected function getCurrentMenu(Request $request): Menu
    {
        $slug = $request->get('menu');

        if ($slug) {
            $menu = Menu::where('slug', $slug)->first();
            if ($menu) {
                return $menu;
            }
        }

        $menu = Menu::orderBy('id')->first();

        if (! $menu) {
            $menu = Menu::create([
                'name' => 'Menu principal',
                'slug' => 'main',
                'location' => 'header',
                'is_active' => true,
            ]);
        }

        return $menu;
    }

    /**
     * Création d'un nouveau menu (header, footer, etc.).
     */
    public function storeBaseMenu(MenuStoreRequest $request)
    {
        $data = $request->validated();

        $data['is_active'] = true;

        $menu = Menu::create($data);

        return redirect()
            ->route('admin.apparence.menus.index', ['menu' => $menu->slug])
            ->with('success', 'Nouveau menu créé.');
    }

    public function index(Request $request)
    {
        $menu = $this->getCurrentMenu($request);

        $menus = Menu::orderBy('location')->orderBy('name')->get();

        $menuItems = $menu->items()
            ->with([
                'category.translations' => function ($q) {
                    $q->where('locale', 'fr');
                },
                'cmsPage.translations' => function ($q) {
                    $q->where('locale', 'fr');
                },
            ])
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(25);

        return view('admin::apparence.menu.index', compact('menu', 'menus', 'menuItems'));
    }

    public function create(Request $request)
    {
        $menu = $this->getCurrentMenu($request);

        $categories = Category::with(['translations' => function ($q) {
            $q->where('locale', 'fr');
        }])->get();

        $cmsPages = Page::with(['translations' => function ($q) {
            $q->where('locale', 'fr');
        }])->get();

        return view('admin::apparence.menu.create', compact('menu', 'categories', 'cmsPages'));
    }

    public function store(MenuItemStoreRequest $request)
    {
        $menu = $this->getCurrentMenu($request);

        $data = $request->validated();

        $data['menu_id'] = $menu->id;
        $data['is_active'] = $request->has('is_active');
        $data['position'] = $data['position'] ?? 1;

        if ($data['type'] === 'category') {
            $data['url'] = $this->resolveCategoryUrl($data['category_id'] ?? null);
            $data['cms_page_id'] = null;
        } elseif ($data['type'] === 'cms_page') {
            $data['category_id'] = null;
            $data['url'] = $this->resolveCmsPageUrl($data['cms_page_id'] ?? null);
        } elseif ($data['type'] === 'link') {
            $data['category_id'] = null;
            $data['cms_page_id'] = null;
        } elseif ($data['type'] === 'text') {
            $data['category_id'] = null;
            $data['cms_page_id'] = null;
            $data['url'] = null;
        }

        MenuItem::create($data);

        return redirect()
            ->route('admin.apparence.menus.index', ['menu' => $menu->slug])
            ->with('success', 'Élément de menu ajouté.');
    }

    public function edit(MenuItem $menu)
    {
        $menuItem = $menu;

        $categories = Category::with(['translations' => function ($q) {
            $q->where('locale', 'fr');
        }])->get();

        $cmsPages = Page::with(['translations' => function ($q) {
            $q->where('locale', 'fr');
        }])->get();

        return view('admin::apparence.menu.edit', compact('menuItem', 'categories', 'cmsPages'));
    }

    public function update(MenuItemUpdateRequest $request, MenuItem $menu)
    {
        $menuItem = $menu;

        $data = $request->validated();

        $data['is_active'] = $request->has('is_active');
        $data['position'] = $data['position'] ?? $menuItem->position ?? 1;

        if ($data['type'] === 'category') {
            $data['url'] = $this->resolveCategoryUrl($data['category_id'] ?? null);
            $data['cms_page_id'] = null;
        } elseif ($data['type'] === 'cms_page') {
            $data['category_id'] = null;
            $data['url'] = $this->resolveCmsPageUrl($data['cms_page_id'] ?? null);
        } elseif ($data['type'] === 'link') {
            $data['category_id'] = null;
            $data['cms_page_id'] = null;
        } elseif ($data['type'] === 'text') {
            $data['category_id'] = null;
            $data['cms_page_id'] = null;
            $data['url'] = null;
        }

        $menuItem->update($data);

        return redirect()
            ->route('admin.apparence.menus.index', ['menu' => $menuItem->menu->slug ?? 'main'])
            ->with('success', 'Élément de menu mis à jour.');
    }

    public function destroy(MenuItem $menu)
    {
        $menuItem = $menu;
        $slug = $menuItem->menu->slug ?? 'main';

        $menuItem->delete();

        return redirect()
            ->route('admin.apparence.menus.index', ['menu' => $slug])
            ->with('success', 'Élément de menu supprimé.');
    }

    protected function resolveCategoryUrl(mixed $categoryId): ?string
    {
        if (! is_numeric($categoryId)) {
            return null;
        }

        $categoryId = (int) $categoryId;

        $category = Category::with(['translations' => function ($q) {
            $q->where('locale', 'fr');
        }])->find($categoryId);

        if (! $category) {
            return null;
        }

        $translation = $category->translations->first();
        $slug = $translation?->slug;

        return $slug ? '/categories/'.$slug : null;
    }

    protected function resolveCmsPageUrl(mixed $cmsPageId): ?string
    {
        if (! is_numeric($cmsPageId)) {
            return null;
        }

        $cmsPageId = (int) $cmsPageId;

        $page = Page::with(['translations' => function ($q) {
            $q->where('locale', 'fr');
        }])->find($cmsPageId);

        if (! $page) {
            return null;
        }

        $translation = $page->translations->first();
        $slug = $translation?->slug;

        return $slug ? '/content/'.$slug : null;
    }
}
