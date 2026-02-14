<?php

declare(strict_types=1);

namespace Omersia\Catalog\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Catalog\Models\ProductVariant;
use Omersia\Shared\Contracts\RepositoryInterface;

/** @extends RepositoryInterface<ProductVariant> */
interface ProductVariantRepositoryInterface extends RepositoryInterface
{
    public function getByProductId(int $productId): Collection;

    public function findBySku(string $sku, ?int $productId = null): ?ProductVariant;

    public function findByOptions(int $productId, array $optionValues): ?ProductVariant;

    public function getInStockVariants(int $productId): Collection;

    public function updateStock(int $variantId, int $quantity): bool;

    public function decrementStock(int $variantId, int $quantity): bool;

    public function incrementStock(int $variantId, int $quantity): bool;
}
