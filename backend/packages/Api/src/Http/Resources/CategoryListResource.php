<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\CategoryTranslation;

/**
 * @property Category $resource
 */
final class CategoryListResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $category = $this->resource;
        $locale = $this->resolveLocale($request);
        $translation = $this->resolveTranslation($category, $locale);
        $name = ($translation && is_string($translation->name) && $translation->name !== '')
            ? $translation->name
            : 'Sans nom';
        $slug = ($translation && is_string($translation->slug))
            ? $translation->slug
            : '';

        return [
            'id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'description' => $translation?->description,
            'image' => $category->image_url,
            'count' => $category->products_count ?? 0,
            'parent_id' => $category->parent_id,
            'position' => $category->position,
            'translations' => $this->translations($category),
        ];
    }

    private function resolveLocale($request): string
    {
        $locale = $request?->get('locale');

        return is_string($locale) && $locale !== ''
            ? $locale
            : (string) (app()->getLocale() ?: 'fr');
    }

    private function resolveTranslation(Category $category, string $locale): ?CategoryTranslation
    {
        if ($category->relationLoaded('translations')) {
            return $category->translations->firstWhere('locale', $locale)
                ?? $category->translations->first();
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function translations(Category $category): array
    {
        if (! $category->relationLoaded('translations')) {
            return [];
        }

        return $category->translations->map(function ($translation) {
            return [
                'locale' => $translation->locale,
                'name' => $translation->name,
                'slug' => $translation->slug,
                'description' => $translation->description,
                'meta_title' => $translation->meta_title,
                'meta_description' => $translation->meta_description,
            ];
        })->values()->toArray();
    }
}
