<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Omersia\Catalog\Models\ShippingMethod;

class DemoShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $method = ShippingMethod::query()->updateOrCreate(
            ['code' => 'demo_test_shipping'],
            [
                'name' => 'Livraison test',
                'description' => 'Méthode de livraison de test pour commandes de démonstration.',
                'price' => 4.90,
                'delivery_time' => '24h à 48h',
                'is_active' => true,
                'use_weight_based_pricing' => false,
                'use_zone_based_pricing' => false,
                'free_shipping_threshold' => 80.00,
            ]
        );

        $this->command?->info("Demo shipping method ready: {$method->name} ({$method->code})");
    }
}
