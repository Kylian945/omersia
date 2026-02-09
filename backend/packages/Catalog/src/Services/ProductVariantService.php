<?php

declare(strict_types=1);

namespace Omersia\Catalog\Services;

use Illuminate\Http\Request;
use Omersia\Catalog\Models\Product;

/**
 * Service pour la gestion des options et variantes de produits
 */
class ProductVariantService
{
    /**
     * Synchronise les options et variantes d'un produit
     */
    public function syncOptionsAndVariants(Product $product, Request $request): void
    {
        // 1. Gérer les options
        $product->options()->delete(); // Reset simple pour MVP
        $optionsInput = $request->input('options', []);

        $optionIdByKey = [];

        foreach ($optionsInput as $index => $opt) {
            if (empty($opt['name']) || empty($opt['values']) || ! is_array($opt['values'])) {
                continue;
            }

            $option = $product->options()->create([
                'name' => $opt['name'],
                'position' => $index,
            ]);

            foreach ($opt['values'] as $vIndex => $value) {
                $val = $option->values()->create([
                    'value' => $value,
                    'position' => $vIndex,
                ]);

                $optionIdByKey[$opt['name'].':'.$value] = $val->id;
            }
        }

        // 2. Gérer les variantes
        $product->variants()->delete();
        $variantsInput = $request->input('variants', []);

        foreach ($variantsInput as $variantData) {
            $variant = $product->variants()->create([
                'sku' => $variantData['sku'] ?? null,
                'name' => $variantData['label'] ?? null,
                'is_active' => ! empty($variantData['is_active']),
                'manage_stock' => true,
                'stock_qty' => (int) ($variantData['stock_qty'] ?? 0),
                'price' => $variantData['price'] ?? null,
                'compare_at_price' => $variantData['compare_at_price'] ?? null,
            ]);

            // Associer les valeurs d'options à la variante
            $valueIds = [];

            foreach ($variantData['values'] ?? [] as $valueLabel) {
                $key = $valueLabel;

                if (isset($optionIdByKey[$key])) {
                    $valueIds[] = $optionIdByKey[$key];
                }
            }

            if ($valueIds) {
                $variant->values()->sync($valueIds);
            }
        }
    }
}
