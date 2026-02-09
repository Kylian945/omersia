<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\CategoryTranslation;

/**
 * @property Category $resource
 */
final class CategoryDetailResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $category = $this->resource;
        $locale = $this->resolveLocale($request);

        return $this->mapCategory($category, $locale, true);
    }

    private function resolveLocale($request): string
    {
        $locale = $request?->get('locale');

        return is_string($locale) && $locale !== ''
            ? $locale
            : (string) (app()->getLocale() ?: 'fr');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCategory(Category $category, string $locale, bool $includeRelations): array
    {
        $translation = $this->resolveTranslation($category, $locale);

        $data = [
            'id' => $category->id,
            'name' => $translation?->name ?? 'Sans nom',
            'slug' => $translation?->slug ?? '',
            'description' => $translation?->description,
            'image' => $category->image_url,
            'image_url' => $category->image_url,
            'parent_id' => $category->parent_id,
            'position' => $category->position,
            'translations' => $this->translations($category),
        ];

        if ($includeRelations) {
            $data['parent'] = $category->relationLoaded('parent') && $category->parent
                ? $this->mapCategory($category->parent, $locale, false)
                : null;

            $data['children'] = $category->relationLoaded('children')
                ? $category->children->map(fn (Category $child) => $this->mapCategory($child, $locale, true))
                    ->values()
                    ->toArray()
                : [];
        }

        return $data;
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
