<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Omersia\Apparence\Models\Menu;
use Omersia\Apparence\Models\MenuItem;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\CategoryTranslation;
use Omersia\Core\Models\Shop;

class DemoMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shop = Shop::first();

        if (! $shop) {
            $this->command->error('No shop found. Please create a shop first.');

            return;
        }

        $this->command->info('🌱 Seeding demo menu for header...');

        // Check if main menu already exists
        $existingMenu = Menu::where('slug', 'main')->first();
        if ($existingMenu) {
            $this->command->warn('Main menu already exists. Skipping menu seeding.');

            return;
        }

        // Create main menu
        $menu = Menu::create([
            'name' => 'Menu principal',
            'slug' => 'main',
            'location' => 'header',
            'is_active' => true,
        ]);

        // Menu items data with parent categories
        $menuItemsData = [
            [
                'label' => 'Accueil',
                'type' => 'link',
                'url' => '/',
                'position' => 1,
            ],
            [
                'label' => 'Vêtements',
                'type' => 'category',
                'categoryName' => 'Vêtements',
                'position' => 2,
            ],
            [
                'label' => 'Accessoires',
                'type' => 'category',
                'categoryName' => 'Accessoires',
                'position' => 3,
            ],
            [
                'label' => 'Sport',
                'type' => 'category',
                'categoryName' => 'Sport',
                'position' => 4,
            ],
        ];

        // Create menu items
        foreach ($menuItemsData as $itemData) {
            $menuItemAttributes = [
                'menu_id' => $menu->id,
                'type' => $itemData['type'],
                'label' => $itemData['label'],
                'is_active' => true,
                'position' => $itemData['position'],
            ];

            // If type is 'category', find category and generate URL
            if ($itemData['type'] === 'category') {
                // Chercher les catégories de niveau 1 (parent_id = null) pour le menu principal
                $category = Category::whereNull('parent_id')
                    ->whereHas('translations', function ($query) use ($itemData) {
                        $query->where('name', $itemData['categoryName'])
                            ->where('locale', 'fr');
                    })->first();

                if ($category) {
                    $translation = CategoryTranslation::where('category_id', $category->id)
                        ->where('locale', 'fr')
                        ->first();

                    if ($translation) {
                        $menuItemAttributes['category_id'] = $category->id;
                        $menuItemAttributes['url'] = '/categories/'.$translation->slug;
                    }
                } else {
                    $this->command->warn("Category '{$itemData['categoryName']}' not found. Skipping menu item.");

                    continue;
                }
            } else {
                // For type 'link', use the provided URL
                $menuItemAttributes['url'] = $itemData['url'];
            }

            MenuItem::create($menuItemAttributes);
        }

        $this->command->info('✅ Created main menu with '.count($menuItemsData).' items');
        $this->command->info('🎉 Demo menu seeded successfully!');
    }
}
