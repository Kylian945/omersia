<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductImage;
use Omersia\Catalog\Services\ProductImageService;
use Tests\TestCase;

class ProductImageServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductImageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductImageService;
        Storage::fake('public');
    }

    public function it_can_upload_images_for_a_product(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $files = [
            UploadedFile::fake()->image('product1.jpg'),
            UploadedFile::fake()->image('product2.jpg'),
        ];

        // Act
        $images = $this->service->uploadImages($product, $files, '0');

        // Assert
        $this->assertCount(2, $images);
        $this->assertEquals($product->id, $images[0]->product_id);
        $this->assertTrue($images[0]->is_main); // Premier est principal
        $this->assertFalse($images[1]->is_main);
    }

    public function it_ensures_first_image_is_main_if_none_specified(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $files = [
            UploadedFile::fake()->image('product1.jpg'),
            UploadedFile::fake()->image('product2.jpg'),
        ];

        // Act - Pas de mainImageIndex spécifié
        $images = $this->service->uploadImages($product, $files, null);

        // Assert
        $this->assertTrue($images[0]->fresh()->is_main);
    }

    public function it_can_upload_additional_images(): void
    {
        // Arrange
        $product = Product::factory()->create();
        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/existing.jpg',
            'position' => 0,
            'is_main' => true,
        ]);

        $files = [
            UploadedFile::fake()->image('additional.jpg'),
        ];

        // Act
        $createdImages = $this->service->uploadAdditionalImages($product, $files);

        // Assert
        $this->assertCount(1, $createdImages);
        $this->assertArrayHasKey('new-0', $createdImages);
        $this->assertEquals(1, $createdImages['new-0']->position); // Position suivante
        $this->assertFalse($createdImages['new-0']->is_main);
    }

    public function it_can_set_main_image_from_existing_image(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $image1 = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/image1.jpg',
            'position' => 0,
            'is_main' => true,
        ]);
        $image2 = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/image2.jpg',
            'position' => 1,
            'is_main' => false,
        ]);

        // Act
        $this->service->setMainImage($product, 'existing-'.$image2->id, []);

        // Assert
        $this->assertFalse($image1->fresh()->is_main);
        $this->assertTrue($image2->fresh()->is_main);
    }

    public function it_can_set_main_image_from_new_image(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $existingImage = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/existing.jpg',
            'position' => 0,
            'is_main' => true,
        ]);

        $newImage = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/new.jpg',
            'position' => 1,
            'is_main' => false,
        ]);

        $newImages = ['new-0' => $newImage];

        // Act
        $this->service->setMainImage($product, 'new-0', $newImages);

        // Assert
        $this->assertFalse($existingImage->fresh()->is_main);
        $this->assertTrue($newImage->fresh()->is_main);
    }

    public function it_can_change_main_image(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $oldMain = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/old.jpg',
            'position' => 0,
            'is_main' => true,
        ]);
        $newMain = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/new.jpg',
            'position' => 1,
            'is_main' => false,
        ]);

        // Act
        $this->service->changeMainImage($product, $newMain);

        // Assert
        $this->assertFalse($oldMain->fresh()->is_main);
        $this->assertTrue($newMain->fresh()->is_main);
    }

    public function it_throws_exception_when_image_does_not_belong_to_product(): void
    {
        // Arrange
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $image = ProductImage::create([
            'product_id' => $product2->id,
            'path' => 'products/other.jpg',
            'position' => 0,
            'is_main' => false,
        ]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Image does not belong to this product');

        // Act
        $this->service->changeMainImage($product1, $image);
    }
}
