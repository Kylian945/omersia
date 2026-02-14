<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property mixed $product_id
 * @property mixed $name
 * @property mixed $position
 * @property-read Product|null $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductOptionValue> $values
 */
class ProductOption extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'position',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<ProductOptionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class)->orderBy('position');
    }
}
