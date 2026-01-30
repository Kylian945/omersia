<?php

declare(strict_types=1);

namespace Omersia\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Core\Models\Shop;
use Omersia\Customer\Models\Customer;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ];
    }
}
