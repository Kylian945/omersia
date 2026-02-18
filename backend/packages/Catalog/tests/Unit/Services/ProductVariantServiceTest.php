<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Services\ProductVariantService;
use Tests\TestCase;

class ProductVariantServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductVariantService;
    }

    public function it_can_sync_options_and_variants(): void
    {
        // Arrange
        $product = Product::factory()->create(['type' => 'variant']);

        $request = Request::create('/test', 'POST', [
            'options' => [
                [
                    'name' => 'Taille',
                    'values' => ['S', 'M', 'L'],
                ],
                [
                    'name' => 'Couleur',
                    'values' => ['Rouge', 'Bleu'],
                ],
            ],
            'variants' => [
                [
                    'sku' => 'PROD-S-ROUGE',
                    'label' => 'S / Rouge',
                    'is_active' => true,
                    'stock_qty' => 10,
                    'price' => 25.00,
                    'values' => ['Taille:S', 'Couleur:Rouge'],
                ],
                [
                    'sku' => 'PROD-M-BLEU',
                    'label' => 'M / Bleu',
                    'is_active' => true,
                    'stock_qty' => 5,
                    'price' => 27.00,
                    'values' => ['Taille:M', 'Couleur:Bleu'],
                ],
            ],
        ]);

        // Act
        $this->service->syncOptionsAndVariants($product, $request);

        // Assert
        $product->refresh();

        // Vérifier les options
        $this->assertCount(2, $product->options);
        $tailleOption = $product->options->where('name', 'Taille')->first();
        $this->assertNotNull($tailleOption);
        $this->assertCount(3, $tailleOption->values); // S, M, L

        $couleurOption = $product->options->where('name', 'Couleur')->first();
        $this->assertNotNull($couleurOption);
        $this->assertCount(2, $couleurOption->values); // Rouge, Bleu

        // Vérifier les variantes
        $this->assertCount(2, $product->variants);
        $variant1 = $product->variants->where('sku', 'PROD-S-ROUGE')->first();
        $this->assertNotNull($variant1);
        $this->assertEquals(25.00, $variant1->price);
        $this->assertEquals(10, $variant1->stock_qty);
    }

    public function it_deletes_existing_options_and_variants_before_sync(): void
    {
        // Arrange
        $product = Product::factory()->create(['type' => 'variant']);

        // Créer des options/variantes existantes
        $existingOption = $product->options()->create(['name' => 'OldOption', 'position' => 0]);
        $existingOption->values()->create(['value' => 'OldValue', 'position' => 0]);
        $product->variants()->create([
            'sku' => 'OLD-SKU',
            'name' => 'Old Variant',
            'price' => 10,
            'stock_qty' => 1,
        ]);

        $request = Request::create('/test', 'POST', [
            'options' => [
                [
                    'name' => 'NewOption',
                    'values' => ['NewValue'],
                ],
            ],
            'variants' => [
                [
                    'sku' => 'NEW-SKU',
                    'label' => 'New Variant',
                    'price' => 20,
                    'stock_qty' => 5,
                    'values' => ['NewOption:NewValue'],
                ],
            ],
        ]);

        // Act
        $this->service->syncOptionsAndVariants($product, $request);

        // Assert
        $product->refresh();
        $this->assertCount(1, $product->options);
        $this->assertEquals('NewOption', $product->options->first()->name);
        $this->assertCount(1, $product->variants);
        $this->assertEquals('NEW-SKU', $product->variants->first()->sku);
    }

    public function it_skips_options_with_missing_name_or_values(): void
    {
        // Arrange
        $product = Product::factory()->create(['type' => 'variant']);

        $request = Request::create('/test', 'POST', [
            'options' => [
                [
                    'name' => 'ValidOption',
                    'values' => ['Value1'],
                ],
                [
                    // Pas de name
                    'values' => ['Value2'],
                ],
                [
                    'name' => 'InvalidOption',
                    // Pas de values
                ],
                [
                    'name' => 'AnotherInvalid',
                    'values' => 'NotAnArray', // Pas un tableau
                ],
            ],
            'variants' => [],
        ]);

        // Act
        $this->service->syncOptionsAndVariants($product, $request);

        // Assert
        $product->refresh();
        $this->assertCount(1, $product->options); // Seulement ValidOption
        $this->assertEquals('ValidOption', $product->options->first()->name);
    }

    public function it_creates_variants_with_correct_attributes(): void
    {
        // Arrange
        $product = Product::factory()->create(['type' => 'variant']);

        $request = Request::create('/test', 'POST', [
            'options' => [
                ['name' => 'Size', 'values' => ['S']],
            ],
            'variants' => [
                [
                    'sku' => 'TEST-SKU',
                    'label' => 'Size S',
                    'is_active' => true,
                    'stock_qty' => 50,
                    'price' => 99.99,
                    'compare_at_price' => 120.00,
                    'values' => ['Size:S'],
                ],
            ],
        ]);

        // Act
        $this->service->syncOptionsAndVariants($product, $request);

        // Assert
        $product->refresh();
        $variant = $product->variants->first();

        $this->assertEquals('TEST-SKU', $variant->sku);
        $this->assertEquals('Size S', $variant->name);
        $this->assertTrue($variant->is_active);
        $this->assertEquals(50, $variant->stock_qty);
        $this->assertEquals(99.99, $variant->price);
        $this->assertEquals(120.00, $variant->compare_at_price);
        $this->assertTrue($variant->manage_stock);
    }

    public function it_updates_existing_variant_when_id_is_provided(): void
    {
        $product = Product::factory()->create(['type' => 'variant']);

        $option = $product->options()->create([
            'name' => 'Taille',
            'position' => 0,
        ]);
        $valueM = $option->values()->create([
            'value' => 'M',
            'position' => 0,
        ]);
        $valueL = $option->values()->create([
            'value' => 'L',
            'position' => 1,
        ]);

        $variant = $product->variants()->create([
            'sku' => 'OLD-SKU',
            'name' => 'Ancienne variante',
            'is_active' => true,
            'manage_stock' => true,
            'stock_qty' => 10,
            'price' => 20.00,
        ]);
        $variant->values()->sync([$valueM->id]);

        $request = Request::create('/test', 'POST', [
            'options' => [
                [
                    'name' => 'Taille',
                    'values' => ['M', 'L'],
                ],
            ],
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => 'NEW-SKU',
                    'label' => 'Taille L',
                    'is_active' => true,
                    'stock_qty' => 4,
                    'price' => 25.00,
                    'compare_at_price' => 30.00,
                    'values' => ['Taille:L'],
                ],
            ],
        ]);

        $this->service->syncOptionsAndVariants($product, $request);

        $product->refresh();
        $this->assertCount(1, $product->variants);

        $updated = $product->variants()->with('values')->first();
        $this->assertNotNull($updated);
        $this->assertEquals($variant->id, $updated->id);
        $this->assertEquals('NEW-SKU', $updated->sku);
        $this->assertEquals(25.00, $updated->price);
        $this->assertEquals(30.00, $updated->compare_at_price);
        $this->assertEquals(4, $updated->stock_qty);
        $this->assertEquals('L', $updated->values->first()?->value);
    }

    public function it_assigns_variant_image_from_image_key_mapping(): void
    {
        $product = Product::factory()->create(['type' => 'variant']);

        $image = $product->images()->create([
            'path' => 'products/test-variant-image.jpg',
            'position' => 0,
            'is_main' => true,
        ]);

        $request = Request::create('/test', 'POST', [
            'options' => [
                [
                    'name' => 'Taille',
                    'values' => ['M'],
                ],
            ],
            'variants' => [
                [
                    'sku' => 'SKU-M',
                    'label' => 'Taille M',
                    'is_active' => true,
                    'stock_qty' => 5,
                    'price' => 29.00,
                    'image_key' => 'existing-'.$image->id,
                    'values' => ['Taille:M'],
                ],
            ],
        ]);

        $this->service->syncOptionsAndVariants(
            $product,
            $request,
            ['existing-'.$image->id => $image->id]
        );

        $variant = $product->variants()->first();
        $this->assertNotNull($variant);
        $this->assertEquals($image->id, $variant->product_image_id);
    }
}
