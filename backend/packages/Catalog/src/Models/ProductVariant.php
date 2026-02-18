<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property mixed $product_id
 * @property mixed $product_image_id
 * @property mixed $sku
 * @property mixed $name
 * @property bool $is_active
 * @property bool $manage_stock
 * @property mixed $stock_qty
 * @property float $price
 * @property float|null $compare_at_price
 * @property-read Product|null $product
 * @property-read ProductImage|null $image
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductOptionValue> $values
 */
class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'product_image_id',
        'sku',
        'name',
        'is_active',
        'manage_stock',
        'stock_qty',
        'price',
        'compare_at_price',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'manage_stock' => 'bool',
        'price' => 'float',
        'compare_at_price' => 'float',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductImage, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'product_image_id');
    }

    /**
     * @return BelongsToMany<ProductOptionValue, $this>
     */
    public function values(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_values'
        );
    }
}
