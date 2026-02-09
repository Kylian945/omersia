<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Controllers;

use App\Http\Controllers\Controller;
use Omersia\Apparence\Http\Requests\EcommercePageStoreRequest;
use Omersia\Apparence\Http\Requests\EcommercePageUpdateRequest;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use Omersia\Core\Models\Shop;

class EcommercePageController extends Controller
{
    public function index()
    {
        $shop = Shop::firstOrFail();
        $pages = EcommercePage::where('shop_id', $shop->id)
            ->with('translations')
            ->orderBy('type')
            ->get();

        return view('admin::apparence.ecommerce-pages.index', compact('pages'));
    }

    public function create()
    {
        $shop = Shop::firstOrFail();

        // Récupérer toutes les catégories avec leurs traductions
        $categories = Category::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('position')
            ->get();

        // Récupérer tous les produits avec leurs traductions
        $products = Product::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('id')
            ->get();

        return view('admin::apparence.ecommerce-pages.create', compact('categories', 'products'));
    }

    public function store(EcommercePageStoreRequest $request)
    {
        $validated = $request->validated();

        $shop = Shop::firstOrFail();

        $page = EcommercePage::create([
            'shop_id' => $shop->id,
            'type' => $validated['type'],
            'slug' => $validated['slug'],
            'is_active' => true,
        ]);

        $page->translations()->create([
            'locale' => $validated['locale'],
            'title' => $validated['title'],
            'content_json' => ['sections' => []],
        ]);

        return redirect()
            ->route('admin.apparence.ecommerce-pages.builder', ['page' => $page->id])
            ->with('success', 'Page e-commerce créée avec succès.');
    }

    public function edit(EcommercePage $page)
    {
        $shop = Shop::firstOrFail();

        // Récupérer toutes les catégories avec leurs traductions
        $categories = Category::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('position')
            ->get();

        // Récupérer tous les produits avec leurs traductions
        $products = Product::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('id')
            ->get();

        return view('admin::apparence.ecommerce-pages.edit', compact('page', 'categories', 'products'));
    }

    public function update(EcommercePageUpdateRequest $request, EcommercePage $page)
    {
        $validated = $request->validated();

        $page->update([
            'type' => $validated['type'],
            'slug' => $validated['slug'],
            'is_active' => $request->boolean('is_active', false),
        ]);

        $locale = $request->get('locale', 'fr');
        $translation = $page->translations()->firstOrCreate(
            ['locale' => $locale],
            ['title' => $page->type.' - '.($page->slug ?? 'default')]
        );

        $translation->update([
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'noindex' => $request->boolean('noindex', false),
        ]);

        return redirect()
            ->route('admin.apparence.ecommerce-pages.index')
            ->with('success', 'Page mise à jour avec succès.');
    }

    public function destroy(EcommercePage $page)
    {
        $page->delete();

        return redirect()
            ->route('admin.apparence.ecommerce-pages.index')
            ->with('success', 'Page supprimée avec succès.');
    }
}
