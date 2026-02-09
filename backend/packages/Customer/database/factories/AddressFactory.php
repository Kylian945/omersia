<?php

declare(strict_types=1);

namespace Omersia\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Customer\Models\Address;
use Omersia\Customer\Models\Customer;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => $this->faker->randomElement(['Home', 'Work', 'Other']),
            'line1' => $this->faker->streetAddress(),
            'line2' => $this->faker->optional()->secondaryAddress(),
            'postcode' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'state' => $this->faker->optional()->state(),
            'country' => $this->faker->countryCode(),
            'phone' => $this->faker->phoneNumber(),
            'is_default_billing' => false,
            'is_default_shipping' => false,
        ];
    }

    public function defaultBilling(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default_billing' => true,
        ]);
    }

    public function defaultShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default_shipping' => true,
        ]);
    }
}
