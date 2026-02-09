<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\CategoryTranslation;
use Omersia\Catalog\Models\Product;
use Omersia\Core\Models\Shop;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_category(): void
    {
        $shop = Shop::factory()->create();

        $category = Category::create([
            'shop_id' => $shop->id,
            'is_active' => true,
            'position' => 1,
        ]);

        $this->assertDatabaseHas('categories', [
            'shop_id' => $shop->id,
            'is_active' => true,
            'position' => 1,
        ]);
    }

    public function it_can_have_parent_category(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function it_can_have_children_categories(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->count(3)->create(['parent_id' => $parent->id]);

        $this->assertCount(3, $parent->children);
    }

    public function it_has_translations(): void
    {
        $category = Category::factory()->create();
        CategoryTranslation::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $category->translations);
        $this->assertCount(1, $category->translations);
    }

    public function it_can_get_translation_by_locale(): void
    {
        $category = Category::factory()->create();
        CategoryTranslation::factory()->create([
            'category_id' => $category->id,
            'locale' => 'fr',
            'name' => 'Catégorie Test',
        ]);

        $translation = $category->translation('fr');

        $this->assertNotNull($translation);
        $this->assertEquals('Catégorie Test', $translation->name);
    }

    public function it_belongs_to_many_products(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $category->products()->attach($product);

        $this->assertCount(1, $category->products);
        $this->assertEquals($product->id, $category->products->first()->id);
    }

    public function it_has_fillable_attributes(): void
    {
        $category = new Category;
        $fillable = $category->getFillable();

        $this->assertContains('shop_id', $fillable);
        $this->assertContains('parent_id', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('position', $fillable);
    }

    public function it_can_create_nested_categories(): void
    {
        $grandParent = Category::factory()->create();
        $parent = Category::factory()->create(['parent_id' => $grandParent->id]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertEquals($grandParent->id, $parent->parent->id);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function it_can_have_multiple_products(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(5)->create();
        $category->products()->attach($products);

        $this->assertCount(5, $category->products);
    }
}
