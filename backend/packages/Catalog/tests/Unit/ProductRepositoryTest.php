<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductTranslation;
use Omersia\Catalog\Repositories\ProductRepository;
use Omersia\Core\Models\Shop;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository(new Product);
    }

    public function it_can_find_product_by_sku(): void
    {
        $shop = Shop::factory()->create();
        Product::factory()->create(['sku' => 'TEST-123', 'shop_id' => $shop->id]);

        $result = $this->repository->findBySku('TEST-123');

        $this->assertNotNull($result);
        $this->assertEquals('TEST-123', $result->sku);
    }

    public function it_can_find_product_by_sku_and_shop(): void
    {
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        Product::factory()->create(['sku' => 'SKU-001', 'shop_id' => $shop1->id]);
        Product::factory()->create(['sku' => 'SKU-002', 'shop_id' => $shop2->id]);

        $result = $this->repository->findBySku('SKU-001', $shop1->id);

        $this->assertEquals($shop1->id, $result->shop_id);
    }

    public function it_returns_null_when_sku_not_found(): void
    {
        $result = $this->repository->findBySku('NON-EXISTENT');

        $this->assertNull($result);
    }

    public function it_can_get_products_by_shop_id(): void
    {
        $shop = Shop::factory()->create();
        Product::factory()->count(3)->create(['shop_id' => $shop->id]);
        Product::factory()->count(2)->create();

        $result = $this->repository->getByShopId($shop->id);

        $this->assertCount(3, $result);
    }

    public function it_can_get_active_products(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $result = $this->repository->getActiveProducts();

        $this->assertCount(2, $result);
    }

    public function it_can_get_active_products_by_shop(): void
    {
        $shop = Shop::factory()->create();
        Product::factory()->count(2)->create(['is_active' => true, 'shop_id' => $shop->id]);
        Product::factory()->create(['is_active' => true]);

        $result = $this->repository->getActiveProducts($shop->id);

        $this->assertCount(2, $result);
    }

    public function it_can_get_products_by_category(): void
    {
        $category = Category::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Product::factory()->create();

        $product1->categories()->attach($category);
        $product2->categories()->attach($category);

        $result = $this->repository->getByCategory($category->id);

        $this->assertCount(2, $result);
    }

    public function it_can_search_products_by_sku(): void
    {
        Product::factory()->create(['sku' => 'ABC-123']);
        Product::factory()->create(['sku' => 'ABC-456']);
        Product::factory()->create(['sku' => 'XYZ-789']);

        $result = $this->repository->searchProducts('ABC');

        $this->assertEquals(2, $result->total());
    }

    public function it_can_search_products_by_name(): void
    {
        $product = Product::factory()->create();
        ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'name' => 'Blue Widget',
        ]);

        $result = $this->repository->searchProducts('Blue');

        $this->assertEquals(1, $result->total());
    }

    public function it_can_update_stock(): void
    {
        $product = Product::factory()->create(['stock_qty' => 10]);

        $result = $this->repository->updateStock($product->id, 20);

        $this->assertTrue($result);
        $this->assertEquals(20, $product->fresh()->stock_qty);
    }

    public function it_can_decrement_stock(): void
    {
        $product = Product::factory()->create(['stock_qty' => 10]);

        $result = $this->repository->decrementStock($product->id, 3);

        $this->assertTrue($result);
        $this->assertEquals(7, $product->fresh()->stock_qty);
    }

    public function it_fails_to_decrement_when_insufficient_stock(): void
    {
        $product = Product::factory()->create(['stock_qty' => 5]);

        $result = $this->repository->decrementStock($product->id, 10);

        $this->assertFalse($result);
        $this->assertEquals(5, $product->fresh()->stock_qty);
    }

    public function it_can_increment_stock(): void
    {
        $product = Product::factory()->create(['stock_qty' => 10]);

        $result = $this->repository->incrementStock($product->id, 5);

        $this->assertTrue($result);
        $this->assertEquals(15, $product->fresh()->stock_qty);
    }

    public function it_can_attach_categories(): void
    {
        $product = Product::factory()->create();
        $categories = Category::factory()->count(2)->create();

        $this->repository->attachCategories($product->id, $categories->pluck('id')->toArray());

        $this->assertCount(2, $product->fresh()->categories);
    }

    public function it_can_sync_categories(): void
    {
        $product = Product::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        $product->categories()->attach($category1);

        $this->repository->syncCategories($product->id, [$category2->id]);

        $this->assertCount(1, $product->fresh()->categories);
        $this->assertEquals($category2->id, $product->fresh()->categories->first()->id);
    }

    public function it_can_attach_images(): void
    {
        $product = Product::factory()->create();
        $images = [
            ['path' => '/image1.jpg', 'position' => 1],
            ['path' => '/image2.jpg', 'position' => 2],
        ];

        $this->repository->attachImages($product->id, $images);

        $this->assertCount(2, $product->fresh()->images);
    }

    public function it_can_get_related_products(): void
    {
        $product = Product::factory()->create();
        $related = Product::factory()->count(3)->create();
        $product->relatedProducts()->attach($related);

        $result = $this->repository->getRelatedProducts($product->id, 2);

        $this->assertCount(2, $result);
    }

    public function it_can_get_new_arrivals(): void
    {
        Product::factory()->create(['is_active' => true, 'created_at' => now()->subDays(1)]);
        Product::factory()->create(['is_active' => true, 'created_at' => now()->subDays(2)]);
        Product::factory()->create(['is_active' => false, 'created_at' => now()]);

        $result = $this->repository->getNewArrivals(null, 10);

        $this->assertCount(2, $result);
        $this->assertTrue($result->first()->created_at->gt($result->last()->created_at));
    }
}
