<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $cart_id
 * @property mixed $product_id
 * @property mixed $variant_id
 * @property mixed $name
 * @property mixed $variant_label
 * @property mixed $unit_price
 * @property mixed $old_price
 * @property mixed $qty
 * @property mixed $quantity
 * @property mixed $price
 * @property mixed $image_url
 * @property array<string, mixed>|null $options
 * @property-read Cart|null $cart
 * @property-read Product|null $product
 */
class CartItem extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\CartItemFactory::new();
    }

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'name',
        'variant_label',
        'unit_price',
        'old_price',
        'qty',
        'image_url',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
