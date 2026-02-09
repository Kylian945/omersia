<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Omersia\Catalog\DTO\ProductCreateDTO;
use Omersia\Catalog\DTO\ProductUpdateDTO;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Services\ProductCreationService;
use Omersia\Catalog\Services\ProductImageService;
use Omersia\Catalog\Services\ProductVariantService;
use Tests\TestCase;

class ProductCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductCreationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->service = new ProductCreationService(
            new ProductImageService,
            new ProductVariantService
        );
    }

    public function it_can_create_a_simple_product(): void
    {
        // Arrange - Créer un produit minimal pour obtenir un shop_id valide
        $existingProduct = Product::factory()->create();
        $shopId = $existingProduct->shop_id;

        $category = Category::factory()->create();

        $dto = new ProductCreateDTO(
            shopId: $shopId,
            type: 'simple',
            isActive: true,
            sku: 'SIMPLE-001',
            price: 29.99,
            compareAtPrice: 39.99,
            stockQty: 100,
            manageStock: true,
            name: 'Test Product',
            slug: 'test-product',
            shortDescription: 'A test product',
            description: 'Full description',
            metaTitle: 'Test Product - Shop',
            metaDescription: 'Buy our test product',
            locale: 'fr',
            categoryIds: [$category->id],
            relatedProductIds: []
        );

        $request = Request::create('/test', 'POST');

        // Act
        $product = $this->service->createProduct($dto, $request);

        // Assert
        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('SIMPLE-001', $product->sku);
        $this->assertEquals('simple', $product->type);
        $this->assertEquals(29.99, $product->price);
        $this->assertEquals(100, $product->stock_qty);
        $this->assertTrue($product->is_active);

        // Vérifier la traduction
        $translation = $product->translations()->where('locale', 'fr')->first();
        $this->assertNotNull($translation);
        $this->assertEquals('Test Product', $translation->name);
        $this->assertEquals('test-product', $translation->slug);

        // Vérifier les catégories
        $this->assertTrue($product->categories->contains($category));
    }

    public function it_can_create_a_variant_product_with_options(): void
    {
        // Arrange
        $existingProduct = Product::factory()->create();
        $shopId = $existingProduct->shop_id;

        $dto = ProductCreateDTO::fromArray([
            'shop_id' => $shopId,
            'type' => 'variant',
            'is_active' => true,
            'sku' => 'TSHIRT',
            'price' => 0, // Produit parent, prix dans les variantes
            'name' => 'T-Shirt',
            'slug' => 't-shirt',
            'locale' => 'fr',
        ]);

        $request = Request::create('/test', 'POST', [
            'options' => [
                ['name' => 'Taille', 'values' => ['S', 'M', 'L']],
            ],
            'variants' => [
                [
                    'sku' => 'TSHIRT-S',
                    'label' => 'T-Shirt S',
                    'price' => 19.99,
                    'stock_qty' => 10,
                    'is_active' => true,
                    'values' => ['Taille:S'],
                ],
            ],
        ]);

        // Act
        $product = $this->service->createProduct($dto, $request);

        // Assert
        $this->assertEquals('variant', $product->type);
        $this->assertCount(1, $product->options);
        $this->assertCount(1, $product->variants);
        $this->assertEquals('TSHIRT-S', $product->variants->first()->sku);
    }

    public function it_can_create_product_with_images(): void
    {
        // Arrange
        $existingProduct = Product::factory()->create();
        $shopId = $existingProduct->shop_id;

        $dto = new ProductCreateDTO(
            shopId: $shopId,
            type: 'simple',
            isActive: true,
            sku: 'IMG-001',
            price: 50.00,
            name: 'Product with Images',
            slug: 'product-images',
            locale: 'fr'
        );

        $request = Request::create('/test', 'POST');
        $request->files->set('images', [
            UploadedFile::fake()->image('image1.jpg'),
            UploadedFile::fake()->image('image2.jpg'),
        ]);
        $request->merge(['main_image' => '1']); // Deuxième image = principale

        // Act
        $product = $this->service->createProduct($dto, $request);

        // Assert
        $this->assertCount(2, $product->images);
        $mainImage = $product->images->where('is_main', true)->first();
        $this->assertNotNull($mainImage);
        $this->assertEquals(1, $mainImage->position);
    }

    public function it_creates_product_within_transaction(): void
    {
        // Arrange
        $existingProduct = Product::factory()->create();
        $shopId = $existingProduct->shop_id;

        $dto = new ProductCreateDTO(
            shopId: $shopId,
            type: 'simple',
            isActive: true,
            sku: 'TRANS-001',
            price: 100.00,
            name: 'Transaction Test',
            slug: 'transaction-test',
            locale: 'fr'
        );

        $request = Request::create('/test', 'POST');

        // Act
        $product = $this->service->createProduct($dto, $request);

        // Assert - Si une partie échoue, tout est rollback
        $this->assertDatabaseHas('products', ['sku' => 'TRANS-001']);
        $this->assertDatabaseHas('product_translations', ['name' => 'Transaction Test']);
    }

    public function it_can_update_an_existing_product(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'sku' => 'OLD-SKU',
            'price' => 10.00,
            'type' => 'simple',
        ]);

        $product->translations()->create([
            'locale' => 'fr',
            'name' => 'Old Name',
            'slug' => 'old-slug',
        ]);

        $dto = new ProductUpdateDTO(
            type: 'simple',
            isActive: true,
            sku: 'NEW-SKU',
            price: 20.00,
            compareAtPrice: 25.00,
            stockQty: 50,
            manageStock: true,
            name: 'Updated Name',
            slug: 'updated-slug',
            shortDescription: 'Updated description',
            locale: 'fr'
        );

        $request = Request::create('/test', 'POST');

        // Act
        $updatedProduct = $this->service->updateProduct($product, $dto, $request);

        // Assert
        $this->assertEquals('NEW-SKU', $updatedProduct->sku);
        $this->assertEquals(20.00, $updatedProduct->price);

        $translation = $updatedProduct->translations()->where('locale', 'fr')->first();
        $this->assertEquals('Updated Name', $translation->name);
        $this->assertEquals('updated-slug', $translation->slug);
    }

    public function it_can_add_images_during_update(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $product->translations()->create(['locale' => 'fr', 'name' => 'Test', 'slug' => 'test']);

        $dto = ProductUpdateDTO::fromArray([
            'type' => 'simple',
            'is_active' => true,
            'sku' => 'TEST-SKU',
            'price' => 10.00,
            'name' => 'Test',
            'slug' => 'test',
        ]);

        $request = Request::create('/test', 'POST');
        $request->files->set('images', [
            UploadedFile::fake()->image('new-image.jpg'),
        ]);
        $request->merge(['main_image' => 'new-0']);

        // Act
        $updatedProduct = $this->service->updateProduct($product, $dto, $request);

        // Assert
        $this->assertCount(1, $updatedProduct->images);
        $this->assertTrue($updatedProduct->images->first()->is_main);
    }

    public function it_updates_product_within_transaction(): void
    {
        // Arrange
        $product = Product::factory()->create(['sku' => 'BEFORE']);
        $product->translations()->create(['locale' => 'fr', 'name' => 'Before', 'slug' => 'before']);

        $dto = ProductUpdateDTO::fromArray([
            'type' => 'simple',
            'is_active' => true,
            'sku' => 'AFTER',
            'name' => 'After',
            'slug' => 'after',
        ]);

        $request = Request::create('/test', 'POST');

        // Act
        $this->service->updateProduct($product, $dto, $request);

        // Assert - Toutes les mises à jour doivent être persistées
        $this->assertDatabaseHas('products', ['id' => $product->id, 'sku' => 'AFTER']);
        $this->assertDatabaseHas('product_translations', ['product_id' => $product->id, 'name' => 'After']);
    }
}
