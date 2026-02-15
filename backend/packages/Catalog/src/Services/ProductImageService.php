<?php

declare(strict_types=1);

namespace Omersia\Catalog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductImage;

/**
 * Service pour la gestion des images de produits
 */
class ProductImageService
{
    /**
     * Upload et création des images pour un produit
     *
     * @param  array<UploadedFile>  $files
     * @param  string|null  $mainImageIndex  Index de l'image principale (ex: '0', '1')
     * @return array<ProductImage>
     */
    public function uploadImages(Product $product, array $files, ?string $mainImageIndex = null): array
    {
        $images = [];
        $position = 0;

        foreach ($files as $index => $file) {
            $path = $file->store('products', 'public');

            $image = ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'position' => $position,
                'is_main' => (string) $index === (string) $mainImageIndex,
            ]);

            $images[] = $image;
            $position++;
        }

        // Si aucune image principale choisie, forcer la première
        if (! ProductImage::where('product_id', $product->id)->where('is_main', true)->exists()) {
            $first = ProductImage::where('product_id', $product->id)
                ->orderBy('position')
                ->first();

            if ($first) {
                $first->update(['is_main' => true]);
            }
        }

        return $images;
    }

    /**
     * Upload de nouvelles images lors d'une mise à jour
     *
     * @param  array<UploadedFile>  $files
     * @return array<string, ProductImage> Map 'new-{index}' => ProductImage
     */
    public function uploadAdditionalImages(Product $product, array $files): array
    {
        $createdImages = [];
        $position = (int) ($product->images()->max('position') ?? 0);

        foreach ($files as $index => $file) {
            $path = $file->store('products', 'public');

            $image = ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'position' => ++$position,
                'is_main' => false,
            ]);

            $createdImages['new-'.$index] = $image;
        }

        return $createdImages;
    }

    /**
     * Définir l'image principale
     *
     * @param  string|null  $mainImageKey  Format: "existing-{id}" ou "new-{index}"
     * @param  array<string, ProductImage>  $newImages  Map des nouvelles images uploadées
     */
    public function setMainImage(Product $product, ?string $mainImageKey, array $newImages = []): void
    {
        if (! $mainImageKey) {
            // Garantir qu'une image principale existe
            $this->ensureMainImageExists($product);

            return;
        }

        // Reset toutes les images
        $product->images()->update(['is_main' => false]);

        if (str_starts_with($mainImageKey, 'existing-')) {
            $id = (int) str_replace('existing-', '', $mainImageKey);
            $image = $product->images()->where('id', $id)->first();

            if ($image) {
                $image->update(['is_main' => true]);
            }
        } elseif (isset($newImages[$mainImageKey])) {
            $newImages[$mainImageKey]->update(['is_main' => true]);
        }

        // Garantir qu'une image principale existe
        $this->ensureMainImageExists($product);
    }

    /**
     * Persist des images base64 générées par IA.
     *
     * @param  array<int|string, mixed>  $encodedImages
     * @return array<string, ProductImage>
     */
    public function uploadGeneratedImages(Product $product, array $encodedImages): array
    {
        $createdImages = [];
        $position = (int) ($product->images()->max('position') ?? 0);

        foreach ($encodedImages as $index => $encodedImage) {
            if (! is_string($encodedImage) || trim($encodedImage) === '') {
                continue;
            }

            $decoded = $this->decodeBase64Image($encodedImage);
            if ($decoded === null) {
                continue;
            }

            $path = sprintf('products/ai/%d/%s.%s', $product->id, (string) Str::ulid(), $decoded['extension']);
            Storage::disk('public')->put($path, $decoded['binary']);

            $image = ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'position' => ++$position,
                'is_main' => false,
            ]);

            $createdImages['ai-'.(string) $index] = $image;
        }

        return $createdImages;
    }

    /**
     * @return array{binary: string, extension: string}|null
     */
    private function decodeBase64Image(string $encodedImage): ?array
    {
        if (! preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,([A-Za-z0-9+\/=\r\n]+)$/', $encodedImage, $matches)) {
            return null;
        }

        $base64 = preg_replace('/\s+/', '', $matches[2]);
        if (! is_string($base64) || $base64 === '') {
            return null;
        }

        $binary = base64_decode($base64, true);
        if (! is_string($binary) || $binary === '') {
            return null;
        }

        if (strlen($binary) > 5 * 1024 * 1024) {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        $mime = is_string($mime) ? strtolower($mime) : '';
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            default => null,
        };

        if (! is_string($extension)) {
            return null;
        }

        return [
            'binary' => $binary,
            'extension' => $extension,
        ];
    }

    /**
     * S'assurer qu'au moins une image est marquée comme principale
     */
    private function ensureMainImageExists(Product $product): void
    {
        if (! $product->images()->where('is_main', true)->exists()) {
            $first = $product->images()->orderBy('position')->first();

            if ($first) {
                $first->update(['is_main' => true]);
            }
        }
    }

    /**
     * Changer l'image principale (endpoint dédié)
     */
    public function changeMainImage(Product $product, ProductImage $image): void
    {
        // Vérifier que l'image appartient au produit
        if ($image->product_id !== $product->id) {
            throw new \InvalidArgumentException('Image does not belong to this product');
        }

        // Reset toutes les images
        $product->images()->update(['is_main' => false]);

        // Définir la nouvelle image principale
        $image->update(['is_main' => true]);
    }

    /**
     * Supprimer une image d'un produit.
     */
    public function deleteImage(Product $product, ProductImage $image): void
    {
        if ($image->product_id !== $product->id) {
            throw new \InvalidArgumentException('Image does not belong to this product');
        }

        $path = (string) $image->path;
        $image->delete();

        if (
            $path !== '' &&
            ! str_starts_with($path, 'http://') &&
            ! str_starts_with($path, 'https://')
        ) {
            $disk = Storage::disk('public');
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $this->ensureMainImageExists($product);
    }
}
