<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $order_id
 * @property mixed $product_id
 * @property mixed $variant_id
 * @property mixed $name
 * @property mixed $sku
 * @property mixed $quantity
 * @property mixed $image_url
 * @property float|int|string|null $unit_price
 * @property float|int|string|null $total_price
 * @property array<string, mixed>|null $meta
 * @property-read Order|null $order
 * @property-read Product|null $product
 * @property-read ProductVariant|null $variant
 */
class OrderItem extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\OrderItemFactory::new();
    }

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'name',
        'sku',
        'quantity',
        'unit_price',
        'total_price',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Appartient à une commande
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Appartient potentiellement à un product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Appartient potentiellement à un variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Prix total calculé (fallback)
     */
    public function getComputedTotalAttribute(): float
    {
        return $this->total_price ?: ($this->unit_price * $this->quantity);
    }
}
