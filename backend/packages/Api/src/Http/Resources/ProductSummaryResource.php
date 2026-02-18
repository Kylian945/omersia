<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductTranslation;
use Omersia\Catalog\Models\ProductVariant;

/**
 * @property Product $resource
 */
final class ProductSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $product = $this->resource;
        $locale = $this->resolveLocale($request);
        $translation = $this->resolveTranslation($product, $locale);
        $pricing = $this->pricing($product);

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'type' => $product->type,
            'is_active' => (bool) $product->is_active,
            'manage_stock' => (bool) $product->manage_stock,
            'stock_qty' => $pricing['stock_qty'],
            'price' => $pricing['price'],
            'compare_at_price' => $pricing['compare_at_price'],
            'on_sale' => $pricing['on_sale'],
            'has_variants' => $pricing['has_variants'],
            'from_price' => $pricing['from_price'],
            'name' => $translation?->name,
            'slug' => $translation?->slug,
            'short_description' => $translation?->short_description,
            'main_image_url' => $pricing['main_image_url'],
            'images' => $this->images($product, $translation?->name),
            'translations' => $this->translations($product),
            'categories' => $this->categories($product, $locale),
            'variants' => $this->variants($product),
        ];
    }

    private function resolveLocale($request): string
    {
        $locale = $request?->get('locale');

        return is_string($locale) && $locale !== ''
            ? $locale
            : (string) (app()->getLocale() ?: 'fr');
    }

    private function resolveTranslation(Product $product, string $locale): ?ProductTranslation
    {
        if ($product->relationLoaded('translations')) {
            return $product->translations->firstWhere('locale', $locale)
                ?? $product->translations->first();
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function pricing(Product $product): array
    {
        $hasVariants = $product->type === 'variant'
            || ($product->relationLoaded('variants') && $product->variants->count() > 0);

        $activeVariants = $product->relationLoaded('variants')
            ? $product->variants->filter(fn (ProductVariant $variant) => $variant->is_active)
            : collect();

        $fromPrice = null;
        $price = (float) ($product->price ?? 0);
        $compareAtPrice = $product->compare_at_price !== null
            ? (float) $product->compare_at_price
            : null;
        $onSale = false;

        if ($hasVariants) {
            $fromPrice = (float) ($activeVariants->whereNotNull('price')->min('price') ?? 0);
            $price = $fromPrice;
            $compareAtPrice = null;
        } else {
            $onSale = $compareAtPrice !== null && $compareAtPrice > $price;
        }

        $stockQty = $product->stock_qty;
        if ($hasVariants && $product->relationLoaded('variants')) {
            $stockQty = $activeVariants->sum('stock_qty');
        }

        return [
            'has_variants' => $hasVariants,
            'from_price' => $fromPrice,
            'price' => $price,
            'compare_at_price' => $compareAtPrice,
            'on_sale' => $onSale,
            'stock_qty' => $stockQty,
            'main_image_url' => $this->mainImageUrl($product),
        ];
    }

    private function mainImageUrl(Product $product): ?string
    {
        if ($product->relationLoaded('images') && $product->images->count() > 0) {
            $mainImage = $product->images->firstWhere('is_main', true)
                ?? $product->images->first();

            return $mainImage?->url;
        }

        if ($product->relationLoaded('mainImage') && $product->mainImage) {
            return $product->mainImage->url;
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function images(Product $product, ?string $fallbackAlt): array
    {
        if (! $product->relationLoaded('images')) {
            return [];
        }

        return $product->images->map(function ($image) use ($fallbackAlt) {
            return [
                'id' => $image->id,
                'url' => $image->url,
                'path' => $image->path,
                'alt' => $image->alt_text ?? $fallbackAlt ?? '',
                'position' => $image->position,
                'is_main' => (bool) $image->is_main,
            ];
        })->values()->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function translations(Product $product): array
    {
        if (! $product->relationLoaded('translations')) {
            return [];
        }

        return $product->translations->map(function ($translation) {
            return [
                'locale' => $translation->locale,
                'name' => $translation->name,
                'slug' => $translation->slug,
                'description' => $translation->description,
                'short_description' => $translation->short_description,
                'meta_title' => $translation->meta_title,
                'meta_description' => $translation->meta_description,
            ];
        })->values()->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categories(Product $product, string $locale): array
    {
        if (! $product->relationLoaded('categories')) {
            return [];
        }

        return $product->categories->map(function (Category $category) use ($locale) {
            $translation = null;
            if ($category->relationLoaded('translations')) {
                $translation = $category->translations->firstWhere('locale', $locale)
                    ?? $category->translations->first();
            }

            return [
                'id' => $category->id,
                'name' => $translation?->name,
                'slug' => $translation?->slug,
                'translations' => $category->relationLoaded('translations')
                    ? $category->translations->map(fn ($t) => [
                        'locale' => $t->locale,
                        'name' => $t->name,
                        'slug' => $t->slug,
                    ])->values()->toArray()
                    : [],
            ];
        })->values()->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function variants(Product $product): array
    {
        if (! $product->relationLoaded('variants')) {
            return [];
        }

        return $product->variants->map(function (ProductVariant $variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'is_active' => (bool) $variant->is_active,
                'manage_stock' => (bool) $variant->manage_stock,
                'stock_qty' => $variant->stock_qty,
                'price' => (float) ($variant->price ?? 0),
                'compare_at_price' => $variant->compare_at_price !== null
                    ? (float) $variant->compare_at_price
                    : null,
                'product_image_id' => $variant->product_image_id !== null ? (int) $variant->product_image_id : null,
                'image_url' => $variant->relationLoaded('image') ? $variant->image?->url : null,
            ];
        })->values()->toArray();
    }
}
