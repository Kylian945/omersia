<?php

declare(strict_types=1);

namespace Omersia\Apparence\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Apparence\Models\Menu;
use Omersia\Apparence\Models\MenuItem;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MenuModelTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Shop::factory()->create();
    }

    #[Test]
    public function it_has_expected_fillable_attributes(): void
    {
        $fillable = (new Menu)->getFillable();
        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('location', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    #[Test]
    public function it_has_many_items(): void
    {
        $menu = Menu::create(['shop_id' => $this->shop->id, 'name' => 'Main', 'slug' => 'main', 'location' => 'header', 'is_active' => true]);
        MenuItem::create(['menu_id' => $menu->id, 'type' => 'link', 'label' => 'A', 'url' => '/a', 'is_active' => true, 'position' => 1]);
        MenuItem::create(['menu_id' => $menu->id, 'type' => 'link', 'label' => 'B', 'url' => '/b', 'is_active' => true, 'position' => 2]);

        $this->assertCount(2, $menu->items()->get());
    }

    #[Test]
    public function items_are_ordered_by_position_then_id(): void
    {
        $menu = Menu::create(['shop_id' => $this->shop->id, 'name' => 'Nav', 'slug' => 'nav', 'location' => 'header', 'is_active' => true]);
        $second = MenuItem::create(['menu_id' => $menu->id, 'type' => 'link', 'label' => 'Second', 'url' => '/b', 'is_active' => true, 'position' => 2]);
        $first = MenuItem::create(['menu_id' => $menu->id, 'type' => 'link', 'label' => 'First', 'url' => '/a', 'is_active' => true, 'position' => 1]);

        $items = $menu->items()->get();

        $this->assertEquals('First', $items[0]->label);
        $this->assertEquals('Second', $items[1]->label);
    }

    #[Test]
    public function it_returns_empty_items_when_none_exist(): void
    {
        $menu = Menu::create(['shop_id' => $this->shop->id, 'name' => 'Empty', 'slug' => 'empty', 'location' => 'footer', 'is_active' => true]);
        $this->assertCount(0, $menu->items()->get());
    }
}
