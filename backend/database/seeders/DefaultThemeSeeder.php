<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Services\ThemeCustomizationService;
use Omersia\Core\Models\Shop;

class DefaultThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shop = Shop::first();

        if (! $shop) {
            $this->command->error('No shop found. Please run ShopSeeder first.');

            return;
        }

        // Check if a theme already exists for this shop
        $existingTheme = Theme::where('shop_id', $shop->id)->first();

        if ($existingTheme) {
            $this->command->info('Theme already exists for this shop. Skipping.');

            return;
        }

        // Load Vision theme configuration
        $visionConfigPath = storage_path('app/theme-vision.json');

        if (! file_exists($visionConfigPath)) {
            $this->command->error('Vision theme configuration file not found at: '.$visionConfigPath);

            return;
        }

        $visionConfig = json_decode(file_get_contents($visionConfigPath), true);

        // Create the default Vision theme
        $theme = Theme::create([
            'shop_id' => $shop->id,
            'name' => 'Vision',
            'slug' => 'vision',
            'description' => 'Thème moderne et élégant pour votre e-commerce avec tous les widgets essentiels',
            'version' => '1.0.0',
            'author' => 'Omersia',
            'component_path' => 'vision',
            'widgets_config' => $visionConfig['widgets'] ?? [],
            'settings_schema' => $visionConfig['settings_schema'] ?? null,
            'is_active' => true,
            'is_default' => true,
            'metadata' => [
                'technologies' => ['Next.js 14', 'Tailwind CSS', 'TypeScript'],
                'features' => ['Responsive', 'SEO optimisé', 'Performance élevée', '17 widgets'],
            ],
        ]);

        // Initialize default customization settings
        $customizationService = app(ThemeCustomizationService::class);
        $customizationService->initializeDefaultSettings($theme);

        $this->command->info('Vision theme created and activated successfully.');
    }
}
