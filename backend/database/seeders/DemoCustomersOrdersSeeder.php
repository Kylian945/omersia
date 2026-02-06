<?php

declare(strict_types=1);

namespace Database\Seeders;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Omersia\Catalog\Models\Order;
use Omersia\Core\Models\Shop;
use Omersia\Customer\Models\Customer;

class DemoCustomersOrdersSeeder extends Seeder
{
    private const CUSTOMER_COUNT = 5;
    private const ORDERS_PREVIOUS_YEAR = 150;
    private const ORDERS_CURRENT_YEAR = 250;

    private Generator $faker;

    public function run(): void
    {
        $this->faker = FakerFactory::create('fr_FR');

        $shop = Shop::query()->first();

        if (! $shop) {
            $shop = Shop::factory()->default()->create([
                'name' => 'Demo Shop',
                'display_name' => 'Demo Shop',
                'is_active' => true,
            ]);
        }

        $customers = Customer::factory()
            ->count(self::CUSTOMER_COUNT)
            ->create([
                'shop_id' => $shop->id,
                'is_active' => true,
            ]);

        $seedTag = now()->format('ymdHis');
        $currentYear = Carbon::now()->year;
        $previousYear = Carbon::now()->subYear()->year;

        $this->seedOrdersForYear($customers, $previousYear, self::ORDERS_PREVIOUS_YEAR, $seedTag);
        $this->seedOrdersForYear($customers, $currentYear, self::ORDERS_CURRENT_YEAR, $seedTag);
    }

    private function seedOrdersForYear(Collection $customers, int $year, int $count, string $seedTag): void
    {
        $startDate = Carbon::create($year, 1, 1, 0, 0, 0);
        $endDate = Carbon::create($year, 12, 31, 23, 59, 59);

        for ($i = 1; $i <= $count; $i++) {
            /** @var Customer $customer */
            $customer = $customers->random();
            $placedAt = Carbon::instance($this->faker->dateTimeBetween($startDate, $endDate));
            $totals = $this->makeTotals();

            Order::factory()->create([
                'number' => sprintf('ORD-%d-%s-%04d', $year, $seedTag, $i),
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'customer_firstname' => $customer->firstname,
                'customer_lastname' => $customer->lastname,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'fulfillment_status' => $this->faker->randomElement(['fulfilled', 'partial', 'unfulfilled']),
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'shipping_total' => $totals['shipping'],
                'tax_total' => $totals['tax'],
                'total' => $totals['total'],
                'shipping_address' => $this->makeAddress($customer),
                'billing_address' => $this->makeAddress($customer),
                'placed_at' => $placedAt,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
                'meta' => [
                    'seed' => 'demo',
                    'year' => $year,
                ],
            ]);
        }
    }

    /**
     * @return array{subtotal: float, discount: float, shipping: float, tax: float, total: float}
     */
    private function makeTotals(): array
    {
        $subtotal = $this->money(20, 450);
        $discount = $this->faker->boolean(20)
            ? $this->money(1, min(60.0, $subtotal * 0.3))
            : 0.0;
        $shipping = $this->money(4, 15);
        $taxable = max($subtotal - $discount + $shipping, 0.0);
        $tax = round($taxable * 0.2, 2);
        $total = round($taxable + $tax, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    /**
     * @return array{line1: string, line2: string, postcode: string, city: string, country: string, phone: string}
     */
    private function makeAddress(Customer $customer): array
    {
        return [
            'line1' => $this->faker->streetAddress(),
            'line2' => $this->faker->boolean(25) ? $this->faker->secondaryAddress() : '',
            'postcode' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'country' => 'France',
            'phone' => $customer->phone ?? $this->faker->phoneNumber(),
        ];
    }

    private function money(float $min, float $max): float
    {
        $minCents = (int) round($min * 100);
        $maxCents = (int) round($max * 100);
        $maxCents = max($maxCents, $minCents);

        return random_int($minCents, $maxCents) / 100;
    }
}
