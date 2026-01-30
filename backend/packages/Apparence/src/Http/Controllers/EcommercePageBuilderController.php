<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Admin\Config\BuilderWidgets;
use Omersia\Apparence\Contracts\ThemeRepositoryInterface;
use Omersia\Apparence\Http\Requests\PageBuilderUpdateRequest;
use Omersia\Apparence\Models\EcommercePage;

class EcommercePageBuilderController extends Controller
{
    public function __construct(
        protected ThemeRepositoryInterface $themeRepository
    ) {}

    public function edit(EcommercePage $page, Request $request)
    {
        $locale = $request->get('locale', 'fr');

        $translation = $page->translations()->where('locale', $locale)->first();

        $content = $translation?->content_json ?? ['sections' => []];

        // Get active theme widgets
        $activeTheme = $this->themeRepository->getActiveTheme($page->shop_id);
        $widgets = $activeTheme && $activeTheme->getWidgets()
            ? $activeTheme->getWidgets()
            : BuilderWidgets::all(); // Fallback to default widgets

        // Group widgets by category
        $widgetCategories = [];
        $categoryLabels = BuilderWidgets::categoryLabels();

        foreach ($widgets as $widget) {
            $category = $widget['category'] ?? 'content';
            if (! isset($widgetCategories[$category])) {
                $widgetCategories[$category] = [];
            }
            $widgetCategories[$category][] = $widget;
        }

        // Détecter si c'est une page avec contenu natif (category ou product)
        $hasNativeContent = isset($content['beforeNative']) || isset($content['afterNative']);
        $isNativeType = in_array($page->type, ['category', 'product']);

        // Si page avec contenu natif, initialiser la structure si nécessaire
        if ($isNativeType && ! $hasNativeContent) {
            $content = [
                'beforeNative' => ['sections' => []],
                'afterNative' => ['sections' => []],
            ];
        }

        // Utiliser le builder approprié
        if ($isNativeType) {
            return view('admin::builder.builder-with-native', [
                'page' => $page,
                'locale' => $locale,
                'pageType' => $page->type,  // 'category' ou 'product'
                'contentJson' => $content,
                'pageTitle' => 'Builder E-commerce - '.ucfirst($page->type),
                'pageTitleHeader' => 'Builder : '.($translation?->title ?? $page->type),
                'saveUrl' => route('admin.apparence.ecommerce-pages.builder.update', ['page' => $page->id, 'locale' => $locale]),
                'backUrl' => route('admin.apparence.ecommerce-pages.index'),
                'widgetCategories' => $widgetCategories,
                'categoryLabels' => $categoryLabels,
                'themeSlug' => $activeTheme?->slug ?? 'vision',
            ]);
        }

        // Builder classique pour les autres types (home, etc.)
        return view('admin::builder.builder', [
            'page' => $page,
            'locale' => $locale,
            'contentJson' => $content,
            'pageTitle' => 'Builder E-commerce',
            'pageTitleHeader' => 'Builder : '.($page->translations->first()->title ?? 'Page'),
            'saveUrl' => route('admin.apparence.ecommerce-pages.builder.update', ['page' => $page->id, 'locale' => $locale]),
            'backUrl' => route('admin.apparence.ecommerce-pages.index'),
            'widgetCategories' => $widgetCategories,
            'categoryLabels' => $categoryLabels,
            'themeSlug' => $activeTheme?->slug ?? 'vision',
        ]);
    }

    public function update(EcommercePage $page, PageBuilderUpdateRequest $request)
    {
        $locale = $request->get('locale', 'fr');

        $data = $request->validated();

        $decoded = json_decode($data['content_json'], true);

        $translation = $page->translations()->firstOrCreate(
            ['locale' => $locale],
            ['title' => $page->type.' - '.$page->slug ?? 'Page']
        );

        $translation->update(['content_json' => $decoded]);

        return redirect()
            ->route('admin.apparence.ecommerce-pages.builder', ['page' => $page->id, 'locale' => $locale])
            ->with('success', 'Contenu mis à jour avec succès.');
    }
}
