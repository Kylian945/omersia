<?php

declare(strict_types=1);

namespace Omersia\Catalog\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Omersia\Catalog\Models\TaxRate;
use Omersia\Catalog\Models\TaxZone;
use Omersia\Core\Models\Shop;

class TaxCalculator
{
    /**
     * Calculate tax for a given amount and address
     *
     * @param  float  $amount  Base amount (subtotal)
     * @param  array  $address  Address data ['country' => 'FR', 'state' => null, 'postal_code' => '75001']
     * @param  float  $shippingAmount  Shipping cost
     * @param  int|null  $shopId  Shop ID (defaults to first shop)
     * @return array ['tax_total' => float, 'tax_rate' => float, 'tax_zone' => TaxZone|null]
     */
    public function calculate(
        float $amount,
        array $address,
        float $shippingAmount = 0,
        ?int $shopId = null
    ): array {
        $shop = $shopId ? Shop::find($shopId) : Shop::first();

        if (! $shop) {
            return [
                'tax_total' => 0,
                'tax_rate' => 0,
                'tax_zone' => null,
                'breakdown' => [],
            ];
        }

        $taxZone = $this->findMatchingTaxZone($shop->id, $address);

        if (! $taxZone) {
            return [
                'tax_total' => 0,
                'tax_rate' => 0,
                'tax_zone' => null,
                'breakdown' => [],
            ];
        }

        /** @var EloquentCollection<int, TaxRate> $taxRates */
        $taxRates = $taxZone->activeTaxRates()->orderBy('priority')->get();

        if ($taxRates->isEmpty()) {
            return [
                'tax_total' => 0,
                'tax_rate' => 0,
                'tax_zone' => $taxZone,
                'breakdown' => [],
            ];
        }

        $taxTotal = 0;
        $breakdown = [];
        $existingTaxAmount = 0;

        foreach ($taxRates as $taxRate) {
            // Calculate product tax
            $productTax = $taxRate->calculateTax($amount, $existingTaxAmount);

            // Calculate shipping tax if applicable
            $shippingTax = 0;
            if ($taxRate->shipping_taxable && $shippingAmount > 0) {
                $shippingTax = $taxRate->calculateTax($shippingAmount, $existingTaxAmount);
            }

            $rateTaxTotal = $productTax + $shippingTax;
            $taxTotal += $rateTaxTotal;

            // Add to breakdown
            $breakdown[] = [
                'name' => $taxRate->name,
                'rate' => $taxRate->rate,
                'type' => $taxRate->type,
                'amount' => round($rateTaxTotal, 2),
                'product_tax' => round($productTax, 2),
                'shipping_tax' => round($shippingTax, 2),
            ];

            // Accumulate tax for compound calculations
            // All taxes (compound or not) are added so the next compound tax can include them
            $existingTaxAmount += $rateTaxTotal;
        }

        // Calculate effective tax rate
        $totalBase = $amount + $shippingAmount;
        $effectiveRate = $totalBase > 0 ? ($taxTotal / $totalBase) * 100 : 0;

        return [
            'tax_total' => round($taxTotal, 2),
            'tax_rate' => round($effectiveRate, 2),
            'tax_zone' => $taxZone,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Find the matching tax zone for an address
     */
    protected function findMatchingTaxZone(int $shopId, array $address): ?TaxZone
    {
        $zones = TaxZone::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('priority', 'desc') // Higher priority first
            ->get();

        foreach ($zones as $zone) {
            if ($zone->matchesAddress($address)) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * Calculate tax inclusive pricing (tax already included in price)
     * Returns the tax amount that's included in the price
     */
    public function calculateIncludedTax(
        float $priceIncludingTax,
        array $address,
        ?int $shopId = null
    ): array {
        $shop = $shopId ? Shop::find($shopId) : Shop::first();

        if (! $shop) {
            return [
                'tax_total' => 0,
                'tax_rate' => 0,
                'price_excluding_tax' => $priceIncludingTax,
            ];
        }

        $taxZone = $this->findMatchingTaxZone($shop->id, $address);

        if (! $taxZone) {
            return [
                'tax_total' => 0,
                'tax_rate' => 0,
                'price_excluding_tax' => $priceIncludingTax,
            ];
        }

        // Get total tax rate
        $taxRate = $taxZone->getTotalTaxRate();

        if ($taxRate <= 0) {
            return [
                'tax_total' => 0,
                'tax_rate' => 0,
                'price_excluding_tax' => $priceIncludingTax,
            ];
        }

        // Calculate price excluding tax
        // Formula: price_excluding_tax = price_including_tax / (1 + tax_rate/100)
        $priceExcludingTax = $priceIncludingTax / (1 + ($taxRate / 100));
        $taxTotal = $priceIncludingTax - $priceExcludingTax;

        return [
            'tax_total' => round($taxTotal, 2),
            'tax_rate' => round($taxRate, 2),
            'price_excluding_tax' => round($priceExcludingTax, 2),
        ];
    }
}
