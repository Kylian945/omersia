<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $shipping_method_id
 * @property mixed $shipping_zone_id
 * @property float|int|string|null $min_weight
 * @property float|int|string|null $max_weight
 * @property float|int|string|null $price
 * @property int $priority
 * @property-read ShippingMethod|null $shippingMethod
 * @property-read ShippingZone|null $shippingZone
 */
class ShippingRate extends Model
{
    protected $fillable = [
        'shipping_method_id',
        'shipping_zone_id',
        'min_weight',
        'max_weight',
        'price',
        'priority',
    ];

    protected $casts = [
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'price' => 'decimal:2',
        'priority' => 'integer',
    ];

    /**
     * @return BelongsTo<ShippingMethod, $this>
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    /**
     * @return BelongsTo<ShippingZone, $this>
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    /**
     * Vérifie si ce tarif s'applique à un poids donné (en kg)
     */
    public function matchesWeight(?float $weight): bool
    {
        if ($weight === null) {
            return $this->min_weight === null && $this->max_weight === null;
        }

        $matchesMin = $this->min_weight === null || $weight >= $this->min_weight;
        $matchesMax = $this->max_weight === null || $weight <= $this->max_weight;

        return $matchesMin && $matchesMax;
    }

    /**
     * Scope pour trouver les tarifs applicables à un poids et une zone
     */
    public function scopeForWeightAndZone($query, ?float $weight, ?int $zoneId)
    {
        return $query->where(function ($q) use ($weight, $zoneId) {
            // Filtre par zone
            if ($zoneId) {
                $q->where('shipping_zone_id', $zoneId);
            } else {
                $q->whereNull('shipping_zone_id');
            }

            // Filtre par poids
            if ($weight !== null) {
                $q->where(function ($subQ) use ($weight) {
                    $subQ->where(function ($minQ) use ($weight) {
                        $minQ->whereNull('min_weight')
                            ->orWhere('min_weight', '<=', $weight);
                    })
                        ->where(function ($maxQ) use ($weight) {
                            $maxQ->whereNull('max_weight')
                                ->orWhere('max_weight', '>=', $weight);
                        });
                });
            }
        })->orderBy('priority', 'desc');
    }
}
