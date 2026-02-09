<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\CategoryTranslation;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductTranslation;
use Omersia\Core\Models\Shop;
use Tests\TestCase;
use Tests\WithApiKey;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
    }

    /** @test */
    public function products_index_returns_paginated_shape(): void
    {
        $shop = Shop::factory()->create();
        $category = $this->createCategory($shop, 'vetements');

        $product = $this->createProduct($shop, 't-shirt-homme');
        $product->categories()->attach($category->id);

        $response = $this->getJson('/api/v1/products?locale=fr', $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => ['id', 'slug', 'name', 'price', 'has_variants', 'translations'],
            ],
        ]);

        $response->assertJsonFragment(['slug' => 't-shirt-homme']);
    }

    /** @test */
    public function products_index_filters_by_category_slug(): void
    {
        $shop = Shop::factory()->create();
        $categoryA = $this->createCategory($shop, 'cat-a');
        $categoryB = $this->createCategory($shop, 'cat-b');

        $productA = $this->createProduct($shop, 'prod-a');
        $productB = $this->createProduct($shop, 'prod-b');

        $productA->categories()->attach($categoryA->id);
        $productB->categories()->attach($categoryB->id);

        $response = $this->getJson('/api/v1/products?locale=fr&category=cat-a', $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['slug' => 'prod-a']);
    }

    /** @test */
    public function product_show_returns_detail_payload(): void
    {
        $shop = Shop::factory()->create();
        $product = $this->createProduct($shop, 'prod-detail');

        $response = $this->getJson('/api/v1/products/prod-detail?locale=fr', $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJsonFragment(['slug' => 'prod-detail']);
        $response->assertJsonStructure([
            'id',
            'slug',
            'name',
            'translations',
            'options',
            'variants',
            'relatedProducts',
            'related_products',
        ]);
    }

    /** @test */
    public function categories_index_respects_parent_only_filter(): void
    {
        $shop = Shop::factory()->create();
        $accueil = $this->createCategory($shop, 'accueil');

        $child = Category::factory()->create([
            'shop_id' => $shop->id,
            'parent_id' => $accueil->id,
        ]);
        CategoryTranslation::factory()->create([
            'category_id' => $child->id,
            'locale' => 'fr',
            'name' => 'Enfant',
            'slug' => 'enfant',
        ]);

        $other = $this->createCategory($shop, 'autre');

        $response = $this->getJson('/api/v1/categories?locale=fr&parent_only=true', $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'categories');
        $response->assertJsonFragment(['slug' => 'enfant']);

        $responseAll = $this->getJson('/api/v1/categories?locale=fr', $this->apiHeaders());
        $responseAll->assertStatus(200);
        $responseAll->assertJsonCount(2, 'categories');
    }

    /** @test */
    public function category_show_returns_category_and_products(): void
    {
        $shop = Shop::factory()->create();
        $category = $this->createCategory($shop, 'chemises');
        $product = $this->createProduct($shop, 'chemise-bleue');

        $product->categories()->attach($category->id);

        $response = $this->getJson('/api/v1/categories/chemises?locale=fr', $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJsonStructure(['category', 'products']);
        $response->assertJsonFragment(['slug' => 'chemises']);
        $response->assertJsonFragment(['slug' => 'chemise-bleue']);
    }

    /** @test */
    public function search_returns_empty_results_for_blank_query(): void
    {
        $response = $this->getJson('/api/v1/search?q=&locale=fr', $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'query' => '',
            'total' => 0,
        ]);
        $response->assertJsonStructure([
            'query',
            'total',
            'products',
            'facets' => ['categories', 'price_range' => ['min', 'max']],
        ]);
    }

    private function createCategory(Shop $shop, string $slug): Category
    {
        $category = Category::factory()->create([
            'shop_id' => $shop->id,
        ]);

        CategoryTranslation::factory()->create([
            'category_id' => $category->id,
            'locale' => 'fr',
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
        ]);

        return $category;
    }

    private function createProduct(Shop $shop, string $slug): Product
    {
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
        ]);

        ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale' => 'fr',
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
        ]);

        return $product;
    }
}
