<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductOption;
use Omersia\Catalog\Models\ProductOptionValue;
use Omersia\Catalog\Models\ProductTranslation;
use Omersia\Catalog\Models\ProductVariant;

/**
 * @property Product $resource
 */
final class ProductDetailResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $product = $this->resource;
        $locale = $this->resolveLocale($request);
        $translation = $this->resolveTranslation($product, $locale);

        $summary = (new ProductSummaryResource($product))->toArray($request);

        $relatedProducts = $product->relationLoaded('relatedProducts')
            ? ProductSummaryResource::collection($product->relatedProducts)->toArray($request)
            : [];

        return array_merge($summary, [
            'description' => $translation?->description,
            'short_description' => $translation?->short_description,
            'meta_title' => $translation?->meta_title,
            'meta_description' => $translation?->meta_description,
            'options' => $this->options($product),
            'variants' => $this->variants($product),
            'relatedProducts' => $relatedProducts,
            'related_products' => $relatedProducts,
        ]);
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
     * @return array<int, array<string, mixed>>
     */
    private function options(Product $product): array
    {
        if (! $product->relationLoaded('options')) {
            return [];
        }

        return $product->options->map(function (ProductOption $option) {
            return [
                'id' => $option->id,
                'name' => $option->name,
                'position' => $option->position,
                'values' => $option->relationLoaded('values')
                    ? $option->values->map(function (ProductOptionValue $value) {
                        return [
                            'id' => $value->id,
                            'value' => $value->value,
                            'position' => $value->position,
                        ];
                    })->values()->toArray()
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
            $price = (float) ($variant->price ?? 0);
            $compareAt = $variant->compare_at_price !== null
                ? (float) $variant->compare_at_price
                : null;
            $onSale = $compareAt !== null && $compareAt > $price;

            $optionValues = [];
            if ($variant->relationLoaded('values')) {
                $optionValues = $variant->values->map(function ($value) {
                    $optionName = $value->relationLoaded('option')
                        ? $value->option?->name
                        : null;

                    return [
                        'option' => $optionName,
                        'value' => $value->value,
                    ];
                })
                    ->filter(fn (array $item) => $item['option'] && $item['value'])
                    ->values()
                    ->toArray();
            }

            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'is_active' => (bool) $variant->is_active,
                'manage_stock' => (bool) $variant->manage_stock,
                'stock_qty' => $variant->stock_qty,
                'price' => $price,
                'compare_at_price' => $compareAt,
                'product_image_id' => $variant->product_image_id !== null ? (int) $variant->product_image_id : null,
                'image_url' => $this->variantImageUrl($variant),
                'on_sale' => $onSale,
                'values' => $variant->relationLoaded('values')
                    ? $variant->values->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'value' => $value->value,
                            'option' => $value->relationLoaded('option')
                                ? [
                                    'id' => $value->option?->id,
                                    'name' => $value->option?->name,
                                ]
                                : null,
                        ];
                    })->values()->toArray()
                    : [],
                'option_values' => $optionValues,
            ];
        })->values()->toArray();
    }

    private function variantImageUrl(ProductVariant $variant): ?string
    {
        if (! $variant->relationLoaded('image')) {
            return null;
        }

        return $variant->image?->url;
    }
}
