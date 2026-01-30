<?php

declare(strict_types=1);

namespace Omersia\Admin\Services\Discounts;

use Illuminate\Support\Collection;
use Omersia\Admin\DTO\Cart;
use Omersia\Admin\DTO\CartDiscountAllocation;
use Omersia\Admin\DTO\CartLine;
use Omersia\Admin\DTO\CartLineResult;
use Omersia\Admin\DTO\CartResult;
use Omersia\Sales\Models\Discount;

class DiscountEngine
{
    public function __construct(
        protected int $shopId,
    ) {}

    public function calculate(Cart $cart): CartResult
    {
        if (empty($cart->lines)) {
            return CartResult::empty();
        }

        $discounts = $this->loadActiveDiscounts($cart);

        $productDiscounts = $discounts->where('type', 'product');
        $orderDiscounts = $discounts->where('type', 'order');
        $shippingDiscounts = $discounts->where('type', 'shipping');
        $bxgyDiscounts = $discounts->where('type', 'buy_x_get_y');

        // Subtotal brut (sans réductions)
        $subtotal = $this->calculateSubtotal($cart->lines);

        // Étape 1 : remises produits (y compris BxGy)
        $linesResult = $this->buildInitialLinesResult($cart->lines);
        $this->applyProductDiscounts($linesResult, $productDiscounts);
        $this->applyBxGyDiscounts($linesResult, $bxgyDiscounts);

        // Recalcul du subtotal après remises produit
        $productDiscountTotal = $this->sumLineDiscounts($linesResult);
        $subtotalAfterProducts = $subtotal - $productDiscountTotal;

        // Étape 2 : remises commande (scénarios)
        [$orderDiscountTotal, $orderAllocations] = $this->applyBestOrderDiscountScenario(
            $orderDiscounts,
            $subtotalAfterProducts,
            $productDiscountTotal,
        );

        // Étape 3 : remises shipping
        [$shippingDiscountTotal, $shippingAllocations] = $this->applyShippingDiscounts(
            $shippingDiscounts,
            $cart->shipping_amount
        );

        $totalDiscounts = $productDiscountTotal + $orderDiscountTotal + $shippingDiscountTotal;
        $total = $subtotal + $cart->shipping_amount - $totalDiscounts;

        $appliedDiscounts = array_merge(
            $this->collectLineAllocations($linesResult),
            $orderAllocations,
            $shippingAllocations,
        );

        return new CartResult(
            lines: $linesResult,
            subtotal: $subtotal,
            product_discount_total: $productDiscountTotal,
            order_discount_total: $orderDiscountTotal,
            shipping_amount: $cart->shipping_amount,
            shipping_discount_total: $shippingDiscountTotal,
            total_discounts: $totalDiscounts,
            total: max($total, 0),
            applied_discounts: $appliedDiscounts,
            discount_code: $cart->discount_code,
            discount_code_error: null,
        );
    }

    /**
     * @param  CartLine[]  $lines
     */
    protected function calculateSubtotal(array $lines): float
    {
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += $line->unit_price * $line->quantity;
        }

        return $subtotal;
    }

    /**
     * @param  CartLine[]  $lines
     * @return CartLineResult[]
     */
    protected function buildInitialLinesResult(array $lines): array
    {
        $results = [];
        foreach ($lines as $line) {
            $lineSubtotal = $line->unit_price * $line->quantity;
            $results[] = new CartLineResult(
                product_id: $line->product_id,
                variant_id: $line->variant_id,
                name: $line->name ?? '',
                quantity: $line->quantity,
                unit_price: $line->unit_price,
                line_subtotal: $lineSubtotal,
                line_discount: 0,
                line_total: $lineSubtotal,
                discounts: [],
            );
        }

        return $results;
    }

    /**
     * @param  CartLineResult[]  $lines
     */
    protected function sumLineDiscounts(array $lines): float
    {
        $total = 0;
        foreach ($lines as $line) {
            $total += $line->line_discount;
        }

        return $total;
    }

    protected function loadActiveDiscounts(Cart $cart): Collection
    {
        $query = Discount::forShop($this->shopId)->active();

        if ($cart->discount_code) {
            $query->where(function ($q) use ($cart) {
                $q->where('method', 'automatic')
                    ->orWhere(function ($q2) use ($cart) {
                        $q2->where('method', 'code')
                            ->where('code', $cart->discount_code);
                    });
            });
        } else {
            $query->where('method', 'automatic');
        }

        $discounts = $query->orderBy('priority')->get();

        // Filtrer usage_limit et audience
        return $discounts->filter(function (Discount $discount) use ($cart) {
            if (! $this->checkUsageLimits($discount, $cart->customer_id)) {
                return false;
            }

            if (! $this->checkCustomerEligibility($discount, $cart->customer_id, $cart->customer_group_ids)) {
                return false;
            }

            return true;
        });
    }

    protected function checkUsageLimits(Discount $discount, ?int $customerId): bool
    {
        if ($discount->usage_limit !== null) {
            $totalUsage = $discount->usages()->sum('usage_count');
            if ($totalUsage >= $discount->usage_limit) {
                return false;
            }
        }

        if ($discount->usage_limit_per_customer !== null && $customerId) {
            $customerUsage = $discount->usages()
                ->where('customer_id', $customerId)
                ->sum('usage_count');

            if ($customerUsage >= $discount->usage_limit_per_customer) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  int[]  $customerGroupIds
     */
    protected function checkCustomerEligibility(Discount $discount, ?int $customerId, array $customerGroupIds): bool
    {
        if ($discount->customer_selection === 'all') {
            return true;
        }

        if ($discount->customer_selection === 'customers') {
            if (! $customerId) {
                return false;
            }

            return $discount->customers()->where('customers.id', $customerId)->exists();
        }

        if ($discount->customer_selection === 'groups') {
            if (empty($customerGroupIds)) {
                return false;
            }

            return $discount->customerGroups()->whereIn('customer_groups.id', $customerGroupIds)->exists();
        }

        return true;
    }

    /**
     * @param  CartLineResult[]  $lines
     * @param  Collection<Discount>  $productDiscounts
     */
    protected function applyProductDiscounts(array &$lines, Collection $productDiscounts): void
    {
        foreach ($lines as $i => $line) {
            $eligibleDiscounts = $productDiscounts->filter(function (Discount $discount) use ($line) {
                $targetProductIds = $discount->products->pluck('id')->all();
                $targetCollectionIds = $discount->collections->pluck('id')->all();

                // Ici on laisse simple : si aucun ciblage => s'applique à tous les produits
                if (empty($targetProductIds) && empty($targetCollectionIds)) {
                    return true;
                }

                if (! empty($targetProductIds) && in_array($line->product_id, $targetProductIds, true)) {
                    return true;
                }

                // Si tu as la collection sur le line, tu peux tester ici
                return false;
            });

            if ($eligibleDiscounts->isEmpty()) {
                continue;
            }

            // On prend celle qui donne la plus grosse remise
            $bestDiscount = null;
            $bestAmount = 0;

            foreach ($eligibleDiscounts as $discount) {
                $amount = $this->calculateLineDiscountAmount($line->line_subtotal, $discount);
                if ($amount > $bestAmount) {
                    $bestAmount = $amount;
                    $bestDiscount = $discount;
                }
            }

            if ($bestDiscount && $bestAmount > 0) {
                $allocation = new CartDiscountAllocation(
                    discount_id: $bestDiscount->id,
                    name: $bestDiscount->name,
                    type: 'product',
                    method: $bestDiscount->method,
                    amount: $bestAmount,
                    description: null,
                );

                $lines[$i]->line_discount += $bestAmount;
                $lines[$i]->line_total = $lines[$i]->line_subtotal - $lines[$i]->line_discount;
                $lines[$i]->discounts[] = $allocation;
            }
        }
    }

    protected function calculateLineDiscountAmount(float $lineSubtotal, Discount $discount): float
    {
        if ($discount->value_type === 'percentage' && $discount->value) {
            return round($lineSubtotal * ($discount->value / 100), 2);
        }

        if ($discount->value_type === 'fixed_amount' && $discount->value) {
            // applique sur la ligne entière (simple)
            return min($discount->value, $lineSubtotal);
        }

        return 0.0;
    }

    /**
     * @param  CartLineResult[]  $lines
     * @param  Collection<Discount>  $bxgyDiscounts
     */
    protected function applyBxGyDiscounts(array &$lines, Collection $bxgyDiscounts): void
    {
        // version simplifiée : on considère que BxGy s'applique sur tous les produits
        foreach ($bxgyDiscounts as $discount) {
            if (! $discount->buy_quantity || ! $discount->get_quantity) {
                continue;
            }

            $totalQty = array_sum(array_map(fn (CartLineResult $l) => $l->quantity, $lines));
            $sets = intdiv($totalQty, $discount->buy_quantity + $discount->get_quantity);

            if ($sets <= 0) {
                continue;
            }

            // Stratégie simple : on rend gratuits les produits les moins chers
            $flatten = [];
            foreach ($lines as $index => $line) {
                for ($i = 0; $i < $line->quantity; $i++) {
                    $flatten[] = [
                        'index' => $index,
                        'unit_price' => $line->unit_price,
                    ];
                }
            }

            usort($flatten, fn ($a, $b) => $a['unit_price'] <=> $b['unit_price']);

            $freeItemsCount = $sets * $discount->get_quantity;
            $toFree = array_slice($flatten, 0, $freeItemsCount);

            foreach ($toFree as $item) {
                $idx = $item['index'];
                $amount = $item['unit_price'];

                $allocation = new CartDiscountAllocation(
                    discount_id: $discount->id,
                    name: $discount->name,
                    type: 'buy_x_get_y',
                    method: $discount->method,
                    amount: $amount,
                    description: null,
                );

                $lines[$idx]->line_discount += $amount;
                $lines[$idx]->line_total = $lines[$idx]->line_subtotal - $lines[$idx]->line_discount;
                $lines[$idx]->discounts[] = $allocation;
            }
        }
    }

    /**
     * @param  Collection<Discount>  $orderDiscounts
     * @return array{0: float, 1: CartDiscountAllocation[]}
     */
    protected function applyBestOrderDiscountScenario(
        Collection $orderDiscounts,
        float $subtotalAfterProducts,
        float $productDiscountTotal
    ): array {
        if ($orderDiscounts->isEmpty()) {
            return [0.0, []];
        }

        $bestAmount = 0.0;
        $bestAlloc = null;

        foreach ($orderDiscounts as $discount) {
            $amount = 0.0;

            if ($discount->value_type === 'percentage' && $discount->value) {
                $amount = round($subtotalAfterProducts * ($discount->value / 100), 2);
            } elseif ($discount->value_type === 'fixed_amount' && $discount->value) {
                $amount = min($discount->value, $subtotalAfterProducts);
            }

            if ($amount > $bestAmount) {
                $bestAmount = $amount;
                $bestAlloc = new CartDiscountAllocation(
                    discount_id: $discount->id,
                    name: $discount->name,
                    type: 'order',
                    method: $discount->method,
                    amount: $amount,
                    description: null,
                );
            }
        }

        if (! $bestAlloc) {
            return [0.0, []];
        }

        return [$bestAmount, [$bestAlloc]];
    }

    /**
     * @return array{0: float, 1: CartDiscountAllocation[]}
     */
    protected function applyShippingDiscounts(Collection $shippingDiscounts, float $shippingAmount): array
    {
        if ($shippingAmount <= 0 || $shippingDiscounts->isEmpty()) {
            return [0.0, []];
        }

        $bestAmount = 0.0;
        $bestAlloc = null;

        /** @var Discount $discount */
        foreach ($shippingDiscounts as $discount) {
            $amount = 0.0;

            if ($discount->value_type === 'free_shipping') {
                $amount = $shippingAmount;
            } elseif ($discount->value_type === 'percentage' && $discount->value) {
                $amount = round($shippingAmount * ($discount->value / 100), 2);
            } elseif ($discount->value_type === 'fixed_amount' && $discount->value) {
                $amount = min($discount->value, $shippingAmount);
            }

            if ($amount > $bestAmount) {
                $bestAmount = $amount;
                $bestAlloc = new CartDiscountAllocation(
                    discount_id: $discount->id,
                    name: $discount->name,
                    type: 'shipping',
                    method: $discount->method,
                    amount: $amount,
                    description: null,
                );
            }
        }

        if (! $bestAlloc) {
            return [0.0, []];
        }

        return [$bestAmount, [$bestAlloc]];
    }

    /**
     * @param  CartLineResult[]  $lines
     * @return CartDiscountAllocation[]
     */
    protected function collectLineAllocations(array $lines): array
    {
        $all = [];
        foreach ($lines as $line) {
            foreach ($line->discounts as $alloc) {
                $all[] = $alloc;
            }
        }

        return $all;
    }
}
