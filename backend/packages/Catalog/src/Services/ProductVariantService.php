<?php

declare(strict_types=1);

namespace Omersia\Catalog\Services;

use Illuminate\Http\Request;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductOption;
use Omersia\Catalog\Models\ProductVariant;

/**
 * Service pour la gestion des options et variantes de produits
 */
class ProductVariantService
{
    /**
     * Synchronise les options et variantes d'un produit
     *
     * @param  array<string, int>  $imageIdByKey
     */
    public function syncOptionsAndVariants(Product $product, Request $request, array $imageIdByKey = []): void
    {
        $optionIdByKey = $this->syncOptions(
            $product,
            $request->input('options', [])
        );

        $this->syncVariants(
            $product,
            $request->input('variants', []),
            $optionIdByKey,
            $imageIdByKey
        );
    }

    /**
     * @return array<string, int>
     */
    private function syncOptions(Product $product, mixed $optionsInput): array
    {
        if (! is_array($optionsInput)) {
            $product->options()->delete();

            return [];
        }

        $optionIdByKey = [];
        $existingOptions = $product->options()->with('values')->get();
        $optionsById = $existingOptions->keyBy('id');
        $optionsByName = $existingOptions->keyBy(
            static fn (ProductOption $option): string => strtolower(trim((string) $option->name))
        );
        $touchedOptionIds = [];

        foreach ($optionsInput as $index => $opt) {
            if (! is_array($opt)) {
                continue;
            }

            $name = trim((string) ($opt['name'] ?? ''));
            $valuesInput = $opt['values'] ?? null;

            if ($name === '' || ! is_array($valuesInput)) {
                continue;
            }

            $values = $this->normalizeOptionValues($valuesInput);
            if ($values === []) {
                continue;
            }

            $position = is_numeric($index) ? (int) $index : 0;
            $optionId = is_numeric($opt['id'] ?? null) ? (int) $opt['id'] : null;
            $option = null;

            if ($optionId !== null && $optionsById->has($optionId)) {
                $option = $optionsById->get($optionId);
            } else {
                $lookupKey = strtolower($name);
                $candidate = $optionsByName->get($lookupKey);
                if ($candidate !== null && ! in_array((int) $candidate->id, $touchedOptionIds, true)) {
                    $option = $candidate;
                }
            }

            if ($option === null) {
                $option = $product->options()->create([
                    'name' => $name,
                    'position' => $position,
                ]);
            } else {
                $option->update([
                    'name' => $name,
                    'position' => $position,
                ]);
            }

            $optionIdInt = (int) $option->id;
            $touchedOptionIds[] = $optionIdInt;
            $this->syncOptionValues($option, $values, $optionIdByKey);
        }

        if ($touchedOptionIds === []) {
            $product->options()->delete();

            return [];
        }

        $product->options()
            ->whereNotIn('id', $touchedOptionIds)
            ->delete();

        return $optionIdByKey;
    }

    /**
     * @param  array<int, string>  $values
     * @param  array<string, int>  $optionIdByKey
     */
    private function syncOptionValues(ProductOption $option, array $values, array &$optionIdByKey): void
    {
        $existingValues = $option->values()->orderBy('id')->get();
        $touchedValueIds = [];

        foreach ($values as $index => $valueLabel) {
            $lookupValue = strtolower($valueLabel);

            $existing = $existingValues->first(function ($value) use ($lookupValue, $touchedValueIds) {
                return ! in_array((int) $value->id, $touchedValueIds, true)
                    && strtolower(trim((string) $value->value)) === $lookupValue;
            });

            if ($existing !== null) {
                $existing->update([
                    'value' => $valueLabel,
                    'position' => $index,
                ]);
                $valueModel = $existing;
            } else {
                $valueModel = $option->values()->create([
                    'value' => $valueLabel,
                    'position' => $index,
                ]);
                $existingValues->push($valueModel);
            }

            $valueId = (int) $valueModel->id;
            $touchedValueIds[] = $valueId;
            $optionIdByKey[$option->name.':'.$valueLabel] = $valueId;
        }

        if ($touchedValueIds === []) {
            $option->values()->delete();

            return;
        }

        $option->values()
            ->whereNotIn('id', $touchedValueIds)
            ->delete();
    }

    /**
     * @param  array<string, int>  $optionIdByKey
     * @param  array<string, int>  $imageIdByKey
     */
    private function syncVariants(
        Product $product,
        mixed $variantsInput,
        array $optionIdByKey,
        array $imageIdByKey
    ): void {
        if (! is_array($variantsInput)) {
            $product->variants()->delete();

            return;
        }

        $existingVariants = $product->variants()->get()->keyBy('id');
        $availableImageIds = $product->images()
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $touchedVariantIds = [];

        foreach ($variantsInput as $variantData) {
            if (! is_array($variantData)) {
                continue;
            }

            $variantId = is_numeric($variantData['id'] ?? null) ? (int) $variantData['id'] : null;
            $variant = null;

            if ($variantId !== null && $existingVariants->has($variantId)) {
                $variant = $existingVariants->get($variantId);
            }

            if (! $variant instanceof ProductVariant) {
                $variant = new ProductVariant;
                $variant->product_id = $product->id;
            }

            $imageId = $this->resolveVariantImageId(
                $variantData['image_key'] ?? null,
                $imageIdByKey,
                $availableImageIds
            );

            $variant->fill([
                'sku' => $this->nullableString($variantData['sku'] ?? null),
                'name' => $this->nullableString($variantData['label'] ?? null),
                'is_active' => ! empty($variantData['is_active']),
                'manage_stock' => $this->toBoolean($variantData['manage_stock'] ?? true, true),
                'stock_qty' => $this->toNonNegativeInt($variantData['stock_qty'] ?? 0),
                'price' => $this->toNullableFloat($variantData['price'] ?? null),
                'compare_at_price' => $this->toNullableFloat($variantData['compare_at_price'] ?? null),
                'product_image_id' => $imageId,
            ]);
            $variant->save();

            $valueIds = [];
            foreach ($this->normalizeVariantValues($variantData['values'] ?? []) as $valueKey) {
                if (isset($optionIdByKey[$valueKey])) {
                    $valueIds[] = $optionIdByKey[$valueKey];
                }
            }

            $variant->values()->sync(array_values(array_unique($valueIds)));
            $touchedVariantIds[] = (int) $variant->id;
        }

        if ($touchedVariantIds === []) {
            $product->variants()->delete();

            return;
        }

        $product->variants()
            ->whereNotIn('id', $touchedVariantIds)
            ->delete();
    }

    /**
     * @param  array<int, mixed>  $valuesInput
     * @return array<int, string>
     */
    private function normalizeOptionValues(array $valuesInput): array
    {
        $values = [];

        foreach ($valuesInput as $value) {
            $normalized = trim((string) $value);
            if ($normalized === '') {
                continue;
            }
            $values[] = $normalized;
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeVariantValues(mixed $valuesInput): array
    {
        if (! is_array($valuesInput)) {
            return [];
        }

        $values = [];
        foreach ($valuesInput as $value) {
            $normalized = trim((string) $value);
            if ($normalized === '') {
                continue;
            }
            $values[] = $normalized;
        }

        return $values;
    }

    private function resolveVariantImageId(
        mixed $imageKey,
        array $imageIdByKey,
        array $availableImageIds
    ): ?int {
        if (! is_string($imageKey)) {
            return null;
        }

        $normalizedKey = trim($imageKey);
        if ($normalizedKey === '') {
            return null;
        }

        $candidateId = null;
        if (isset($imageIdByKey[$normalizedKey])) {
            $candidateId = (int) $imageIdByKey[$normalizedKey];
        } elseif (str_starts_with($normalizedKey, 'existing-')) {
            $rawId = str_replace('existing-', '', $normalizedKey);
            if (ctype_digit($rawId)) {
                $candidateId = (int) $rawId;
            }
        } elseif (ctype_digit($normalizedKey)) {
            $candidateId = (int) $normalizedKey;
        }

        if (! is_int($candidateId) || $candidateId <= 0) {
            return null;
        }

        return in_array($candidateId, $availableImageIds, true)
            ? $candidateId
            : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function toNonNegativeInt(mixed $value): int
    {
        if (! is_numeric($value)) {
            return 0;
        }

        return max(0, (int) $value);
    }

    private function toBoolean(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized ?? $default;
    }
}
