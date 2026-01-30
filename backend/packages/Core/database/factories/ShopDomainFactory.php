<?php

declare(strict_types=1);

namespace Omersia\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Core\Models\ShopDomain;

class ShopDomainFactory extends Factory
{
    protected $model = ShopDomain::class;

    public function definition(): array
    {
        return [
            'shop_id' => 1,
            'domain' => $this->faker->unique()->domainName(),
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
