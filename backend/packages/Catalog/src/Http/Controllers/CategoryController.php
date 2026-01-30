<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Omersia\Catalog\Http\Requests\CategoryStoreRequest;
use Omersia\Catalog\Http\Requests\CategoryUpdateRequest;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\CategoryTranslation;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('categories.view');

        $categories = Category::with(['translations'])
            ->orderBy('position')
            ->get();

        return view('admin::categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorize('categories.create');

        $parents = Category::with('translations')->orderBy('position')->get();

        return view('admin::categories.create', compact('parents'));
    }

    public function store(CategoryStoreRequest $request)
    {
        $validated = $request->validated();

        $category = Category::create([
            'shop_id' => 1, // à remplacer par ton resolver de boutique
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'position' => $validated['position'] ?? 0,
        ]);

        CategoryTranslation::create([
            'category_id' => $category->id,
            'locale' => 'fr',
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    public function edit(Category $category)
    {
        $this->authorize('categories.update');

        $category->load('translations');
        $parents = Category::where('id', '!=', $category->id)
            ->with('translations')
            ->orderBy('position')
            ->get();

        return view('admin::categories.edit', compact('category', 'parents'));
    }

    public function update(CategoryUpdateRequest $request, Category $category)
    {
        $validated = $request->validated();

        $category->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'position' => $validated['position'] ?? 0,
        ]);

        $translation = $category->translations()
            ->where('locale', 'fr')
            ->first();

        if (! $translation) {
            $translation = new CategoryTranslation([
                'category_id' => $category->id,
                'locale' => 'fr',
            ]);
        }

        $translation->fill([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ])->save();

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('categories.delete');

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie supprimée.');
    }

    /**
     * API endpoint to get categories list for builder
     */
    public function apiList()
    {
        $this->authorize('categories.view');

        $categories = Category::with(['translations' => function ($query) {
            $query->where('locale', 'fr');
        }])
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->map(function ($category) {
                $translation = $category->translations->first();

                return [
                    'id' => $category->id,
                    'name' => $translation?->name ?? 'Sans nom',
                    'slug' => $translation?->slug ?? '',
                ];
            });

        return response()->json($categories);
    }
}
