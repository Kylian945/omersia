<?php

declare(strict_types=1);

namespace Omersia\Catalog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Omersia\Catalog\Models\Category;

/**
 * Service pour la gestion des images de catégories
 */
class CategoryImageService
{
    /**
     * Upload et sauvegarde de l'image pour une catégorie
     * Structure: categories/{id_digit_1}/{id_digit_2}/.../{filename}
     * Ex: ID 23 → categories/2/3/{filename}
     */
    public function uploadImage(Category $category, UploadedFile $file): string
    {
        // Supprimer l'ancienne image si elle existe
        $this->deleteImage($category);

        // Générer le chemin avec les digits de l'ID
        $idPath = $this->getIdPath($category->id);

        // Stocker dans categories/{id_path}/
        $path = $file->store("categories/{$idPath}", 'public');

        // Mettre à jour le modèle
        $category->update(['image_path' => $path]);

        return $path;
    }

    /**
     * Supprimer l'image d'une catégorie
     */
    public function deleteImage(Category $category): bool
    {
        if (! $category->image_path) {
            return false;
        }

        // Supprimer le fichier du storage
        if (Storage::disk('public')->exists($category->image_path)) {
            Storage::disk('public')->delete($category->image_path);
        }

        // Réinitialiser le chemin
        $category->update(['image_path' => null]);

        return true;
    }

    /**
     * Obtenir l'URL complète de l'image
     */
    public function getImageUrl(Category $category): ?string
    {
        if (! $category->image_path) {
            return null;
        }

        return asset('storage/'.$category->image_path);
    }

    /**
     * Convertir un ID en chemin de dossier
     * Ex: 23 → "2/3", 123 → "1/2/3", 1 → "1"
     */
    private function getIdPath(int $id): string
    {
        return implode('/', str_split((string) $id));
    }
}
