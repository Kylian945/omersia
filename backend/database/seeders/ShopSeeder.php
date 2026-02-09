<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Omersia\Core\Models\Shop;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ne créer le shop que s'il n'existe pas
        if (Shop::count() > 0) {
            $this->command->info('Shop already exists, skipping...');

            return;
        }

        $this->command->info('Creating default shop...');

        Shop::create([
            'name' => 'omersia',
            'code' => 'omersia',
            'display_name' => 'Omersia Store',
            'default_locale' => 'fr',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->command->info('✓ Default shop created');
    }
}
