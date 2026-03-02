<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Apparence\Models\Menu;
use Omersia\Apparence\Models\MenuItem;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\WithApiKey;

class MenuApiTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
        $this->shop = Shop::factory()->create();
    }

    #[Test]
    public function it_shows_menu_by_slug(): void
    {
        // Arrange
        $menu = Menu::create([
            'shop_id' => $this->shop->id,
            'name' => 'Menu Principal',
            'slug' => 'main',
            'location' => 'header',
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => 'link',
            'label' => 'Accueil',
            'url' => '/',
            'is_active' => true,
            'position' => 1,
        ]);

        // Act
        $response = $this->getJson('/api/v1/menus/main?locale=fr', $this->apiHeaders());

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'slug' => 'main',
            'name' => 'Menu Principal',
            'location' => 'header',
        ]);
        $response->assertJsonStructure(['slug', 'name', 'location', 'items']);
    }

    #[Test]
    public function it_returns_404_for_unknown_menu(): void
    {
        // Act
        $response = $this->getJson('/api/v1/menus/menu-inexistant?locale=fr', $this->apiHeaders());

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function it_returns_menu_items_in_order(): void
    {
        // Arrange: items avec positions différentes créés dans le désordre
        $menu = Menu::create([
            'shop_id' => $this->shop->id,
            'name' => 'Menu Ordonné',
            'slug' => 'menu-ordered',
            'location' => 'header',
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => 'link',
            'label' => 'Troisième',
            'url' => '/trois',
            'is_active' => true,
            'position' => 3,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => 'link',
            'label' => 'Premier',
            'url' => '/un',
            'is_active' => true,
            'position' => 1,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => 'link',
            'label' => 'Deuxième',
            'url' => '/deux',
            'is_active' => true,
            'position' => 2,
        ]);

        // Act
        $response = $this->getJson('/api/v1/menus/menu-ordered?locale=fr', $this->apiHeaders());

        // Assert: items triés par position
        $response->assertOk();
        $items = $response->json('items');
        $this->assertCount(3, $items);
        $this->assertEquals('Premier', $items[0]['label']);
        $this->assertEquals('Deuxième', $items[1]['label']);
        $this->assertEquals('Troisième', $items[2]['label']);
    }

    #[Test]
    public function it_includes_menu_items_in_response(): void
    {
        // Arrange
        $menu = Menu::create([
            'shop_id' => $this->shop->id,
            'name' => 'Menu avec Items',
            'slug' => 'menu-with-items',
            'location' => 'footer',
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => 'link',
            'label' => 'Contact',
            'url' => '/contact',
            'is_active' => true,
            'position' => 1,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => 'link',
            'label' => 'À propos',
            'url' => '/a-propos',
            'is_active' => true,
            'position' => 2,
        ]);

        // Act
        $response = $this->getJson('/api/v1/menus/menu-with-items?locale=fr', $this->apiHeaders());

        // Assert: items présents et bien structurés
        $response->assertOk();
        $response->assertJsonStructure([
            'slug',
            'name',
            'location',
            'items' => [
                '*' => ['id', 'label', 'type', 'url'],
            ],
        ]);
        $this->assertCount(2, $response->json('items'));
    }

    #[Test]
    public function it_returns_404_for_inactive_menu(): void
    {
        // Arrange: menu inactif
        Menu::create([
            'shop_id' => $this->shop->id,
            'name' => 'Menu Inactif',
            'slug' => 'menu-inactif',
            'location' => 'header',
            'is_active' => false,
        ]);

        // Act
        $response = $this->getJson('/api/v1/menus/menu-inactif?locale=fr', $this->apiHeaders());

        // Assert: 404 car le menu est inactif (le contrôleur filtre is_active=true)
        $response->assertNotFound();
    }
}
