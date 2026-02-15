<?php

declare(strict_types=1);

namespace Omersia\Catalog\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Omersia\Catalog\DTO\ProductCreateDTO;
use Omersia\Catalog\DTO\ProductUpdateDTO;
use Omersia\Catalog\Models\Product;

/**
 * Service pour la création et mise à jour de produits
 * Gère les transactions DB et orchestre les autres services
 */
class ProductCreationService
{
    public function __construct(
        private readonly ProductImageService $imageService,
        private readonly ProductVariantService $variantService
    ) {}

    /**
     * Créer un nouveau produit avec toutes ses données
     *
     * @param  Request  $request  Pour les fichiers et variantes
     */
    public function createProduct(ProductCreateDTO $dto, Request $request): Product
    {
        return DB::transaction(function () use ($dto, $request) {
            // 1. Créer le produit
            $product = Product::create($dto->toProductArray());

            // 2. Créer la traduction
            $translation = $product->translations()->create($dto->toTranslationArray());

            // 3. Gérer les images
            $createdImages = [];

            if ($request->hasFile('images')) {
                $uploadedImages = $this->imageService->uploadImages(
                    $product,
                    $request->file('images'),
                    null
                );

                foreach ($uploadedImages as $index => $image) {
                    $createdImages['new-'.(string) $index] = $image;
                }
            }

            if (is_array($request->input('ai_generated_images'))) {
                $generatedImages = $this->imageService->uploadGeneratedImages(
                    $product,
                    $request->input('ai_generated_images', [])
                );

                $createdImages = [...$createdImages, ...$generatedImages];
            }

            $mainImageKey = $this->normalizeMainImageKey(
                $request->input('main_image')
            );

            if (! empty($createdImages) || $mainImageKey !== null) {
                $this->imageService->setMainImage($product, $mainImageKey, $createdImages);
            }

            // 4. Synchroniser les catégories
            $product->categories()->sync($dto->categoryIds);

            // 5. Synchroniser les produits associés
            if (! empty($dto->relatedProductIds)) {
                $product->relatedProducts()->sync($dto->relatedProductIds);
            }

            // 6. Gérer les variantes si produit de type "variant"
            if ($dto->type === 'variant') {
                $this->variantService->syncOptionsAndVariants($product, $request);
            }

            return $product;
        });
    }

    /**
     * Mettre à jour un produit existant
     *
     * @param  Request  $request  Pour les fichiers et variantes
     */
    public function updateProduct(Product $product, ProductUpdateDTO $dto, Request $request): Product
    {
        return DB::transaction(function () use ($product, $dto, $request) {
            // 1. Mettre à jour le produit
            $product->update($dto->toProductArray());

            // 2. Mettre à jour la traduction (create or update)
            $translation = $product->translations()->firstOrNew(['locale' => $dto->locale]);
            $translation->fill($dto->toTranslationArray());
            $translation->save();

            // 3. Gérer l'ajout de nouvelles images
            $createdImages = [];

            if ($request->hasFile('images')) {
                $createdImages = $this->imageService->uploadAdditionalImages(
                    $product,
                    $request->file('images')
                );
            }

            if (is_array($request->input('ai_generated_images'))) {
                $generatedImages = $this->imageService->uploadGeneratedImages(
                    $product,
                    $request->input('ai_generated_images', [])
                );

                $createdImages = [...$createdImages, ...$generatedImages];
            }

            // 4. Gérer l'image principale
            $mainImageKey = $this->normalizeMainImageKey(
                $request->input('main_image')
            );
            $this->imageService->setMainImage($product, $mainImageKey, $createdImages);

            // 5. Synchroniser les catégories
            $product->categories()->sync($dto->categoryIds);

            // 6. Synchroniser les produits associés
            $product->relatedProducts()->sync($dto->relatedProductIds);

            // 7. Gérer les variantes si produit de type "variant"
            if ($dto->type === 'variant') {
                $this->variantService->syncOptionsAndVariants($product, $request);
            }

            return $product;
        });
    }

    private function normalizeMainImageKey(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (ctype_digit($trimmed)) {
            return 'new-'.$trimmed;
        }

        return $trimmed;
    }
}
