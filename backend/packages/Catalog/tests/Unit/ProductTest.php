<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductTranslation;
use Omersia\Core\Models\Shop;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_product(): void
    {
        $shop = Shop::factory()->create();

        $product = Product::create([
            'shop_id' => $shop->id,
            'sku' => 'TEST-SKU',
            'type' => 'simple',
            'is_active' => true,
            'manage_stock' => false,
            'price' => 19.99,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-SKU',
            'price' => 19.99,
        ]);
    }

    public function it_belongs_to_shop(): void
    {
        $shop = Shop::factory()->create();
        $product = Product::factory()->create(['shop_id' => $shop->id]);

        $this->assertInstanceOf(Shop::class, $product->shop);
        $this->assertEquals($shop->id, $product->shop->id);
    }

    public function it_has_translations(): void
    {
        $product = Product::factory()->create();
        ProductTranslation::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $product->translations);
        $this->assertCount(1, $product->translations);
    }

    public function it_can_get_translation_by_locale(): void
    {
        $product = Product::factory()->create();
        ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale' => 'fr',
            'name' => 'Produit Test',
        ]);

        $translation = $product->translation('fr');

        $this->assertNotNull($translation);
        $this->assertEquals('Produit Test', $translation->name);
    }

    public function it_belongs_to_many_categories(): void
    {
        $product = Product::factory()->create();
        $category = Category::factory()->create();
        $product->categories()->attach($category);

        $this->assertCount(1, $product->categories);
        $this->assertEquals($category->id, $product->categories->first()->id);
    }

    public function it_has_images(): void
    {
        $product = Product::factory()->create();
        $product->images()->create(['path' => '/image1.jpg', 'position' => 1]);
        $product->images()->create(['path' => '/image2.jpg', 'position' => 2]);
        $product->images()->create(['path' => '/image3.jpg', 'position' => 3]);

        $this->assertCount(3, $product->fresh()->images);
    }

    public function it_has_main_image(): void
    {
        $product = Product::factory()->create();
        $mainImage = $product->images()->create([
            'path' => '/main.jpg',
            'is_main' => true,
            'position' => 1,
        ]);

        $this->assertNotNull($product->fresh()->mainImage);
        $this->assertEquals($mainImage->id, $product->fresh()->mainImage->id);
    }

    public function it_gets_main_image_url_attribute(): void
    {
        $product = Product::factory()->create();
        $product->images()->create([
            'path' => 'images/main.jpg',
            'is_main' => true,
            'position' => 1,
        ]);

        $this->assertStringContainsString('images/main.jpg', $product->fresh()->main_image_url);
    }

    public function it_returns_first_image_when_no_main_image(): void
    {
        $product = Product::factory()->create();
        $product->images()->create([
            'path' => 'images/first.jpg',
            'is_main' => false,
            'position' => 1,
        ]);

        $this->assertStringContainsString('images/first.jpg', $product->fresh()->main_image_url);
    }

    public function it_has_related_products(): void
    {
        $product = Product::factory()->create();
        $relatedProduct = Product::factory()->create();
        $product->relatedProducts()->attach($relatedProduct);

        $this->assertCount(1, $product->relatedProducts);
    }

    public function it_has_options(): void
    {
        $product = Product::factory()->create();
        $product->options()->create(['name' => 'Size', 'position' => 1]);
        $product->options()->create(['name' => 'Color', 'position' => 2]);

        $this->assertCount(2, $product->fresh()->options);
    }

    public function it_has_variants(): void
    {
        $product = Product::factory()->create(['type' => 'variant']);
        $product->variants()->create(['sku' => 'VAR-1', 'name' => 'Variant 1', 'price' => 10.00]);
        $product->variants()->create(['sku' => 'VAR-2', 'name' => 'Variant 2', 'price' => 15.00]);
        $product->variants()->create(['sku' => 'VAR-3', 'name' => 'Variant 3', 'price' => 20.00]);

        $this->assertCount(3, $product->fresh()->variants);
    }

    public function it_knows_if_has_variants(): void
    {
        $variantProduct = Product::factory()->create(['type' => 'variant']);
        $simpleProduct = Product::factory()->create(['type' => 'simple']);

        $this->assertTrue($variantProduct->hasVariants());
        $this->assertFalse($simpleProduct->hasVariants());
    }

    public function it_casts_is_active_to_boolean(): void
    {
        $product = Product::factory()->create(['is_active' => 1]);

        $this->assertIsBool($product->is_active);
        $this->assertTrue($product->is_active);
    }

    public function it_casts_manage_stock_to_boolean(): void
    {
        $product = Product::factory()->create(['manage_stock' => 1]);

        $this->assertIsBool($product->manage_stock);
        $this->assertTrue($product->manage_stock);
    }

    public function it_casts_price_to_float(): void
    {
        $product = Product::factory()->create(['price' => '19.99']);

        $this->assertIsFloat($product->price);
        $this->assertEquals(19.99, $product->price);
    }

    public function it_casts_compare_at_price_to_float(): void
    {
        $product = Product::factory()->create(['compare_at_price' => '29.99']);

        $this->assertIsFloat($product->compare_at_price);
        $this->assertEquals(29.99, $product->compare_at_price);
    }

    public function it_should_be_searchable_when_active(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $this->assertTrue($product->shouldBeSearchable());
    }

    public function it_should_not_be_searchable_when_inactive(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->assertFalse($product->shouldBeSearchable());
    }
}
