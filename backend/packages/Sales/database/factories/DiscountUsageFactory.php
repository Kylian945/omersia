<?php

declare(strict_types=1);

namespace Omersia\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Catalog\Models\Order;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Models\Discount;
use Omersia\Sales\Models\DiscountUsage;

class DiscountUsageFactory extends Factory
{
    protected $model = DiscountUsage::class;

    public function definition(): array
    {
        return [
            'discount_id' => Discount::factory(),
            'customer_id' => Customer::factory(),
            'order_id' => Order::factory(),
            'usage_count' => 1,
        ];
    }
}
