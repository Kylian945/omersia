<?php

declare(strict_types=1);

namespace Omersia\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Core\Models\Shop;
use Omersia\Customer\Models\CustomerGroup;

class CustomerGroupFactory extends Factory
{
    protected $model = CustomerGroup::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->slug(2),
            'description' => $this->faker->sentence(),
            'is_default' => false,
        ];
    }
}
