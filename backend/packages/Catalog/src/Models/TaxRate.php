<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRate extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\TaxRateFactory::new();
    }

    protected $fillable = [
        'tax_zone_id',
        'name',
        'type',
        'rate',
        'compound',
        'shipping_taxable',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'compound' => 'boolean',
        'shipping_taxable' => 'boolean',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the tax zone that owns this rate
     */
    public function taxZone(): BelongsTo
    {
        return $this->belongsTo(TaxZone::class);
    }

    /**
     * Calculate tax amount for a given price
     *
     * @param  float  $existingTaxAmount  For compound taxes
     */
    public function calculateTax(float $price, float $existingTaxAmount = 0): float
    {
        if ($this->type === 'percentage') {
            $base = $this->compound ? ($price + $existingTaxAmount) : $price;

            return ($base * $this->rate) / 100;
        }

        // Fixed amount
        return $this->rate;
    }

    /**
     * Get the rate as a percentage string
     */
    public function getRateDisplayAttribute(): string
    {
        if ($this->type === 'percentage') {
            return number_format($this->rate, 2).'%';
        }

        return number_format($this->rate, 2).' €';
    }
}
