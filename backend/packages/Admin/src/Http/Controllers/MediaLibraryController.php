<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Omersia\Admin\Models\MediaFolder;
use Omersia\Admin\Models\MediaItem;
use Omersia\Shared\Helpers\FileHelper;

class MediaLibraryController extends Controller
{
    private const OPTIMIZABLE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    public function index(Request $request)
    {
        $this->authorize('media.view');

        $folderId = $request->get('folder_id');
        $folder = $folderId ? MediaFolder::findOrFail($folderId) : null;

        $folders = MediaFolder::where('parent_id', $folderId)->get();
        $items = MediaItem::where('folder_id', $folderId)->latest()->get();

        $breadcrumbs = [];
        if ($folder) {
            $current = $folder;
            while ($current) {
                array_unshift($breadcrumbs, $current);
                $current = $current->parent;
            }
        }

        return view('admin::media.index', compact('folders', 'items', 'folder', 'breadcrumbs'));
    }

    public function store(Request $request)
    {
        $this->authorize('media.upload');

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|file|mimes:jpg,jpeg,png,gif,webp,svg,pdf|max:10240',
            'folder_id' => 'nullable|exists:media_folders,id',
        ]);

        $uploadedItems = [];

        // MIME types autorisés
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
        ];

        foreach ($request->file('images') as $file) {
            // Validation du contenu réel du fichier
            $realPath = $file->getRealPath();
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMimeType = finfo_file($finfo, $realPath);
            finfo_close($finfo);

            // Vérifier que le MIME type détecté est autorisé
            if (! in_array($detectedMimeType, $allowedMimeTypes, true)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Le fichier {$file->getClientOriginalName()} a un type de contenu invalide ({$detectedMimeType}).",
                    ], 422);
                }

                return redirect()->back()->withErrors([
                    'images' => "Le fichier {$file->getClientOriginalName()} a un type de contenu invalide.",
                ]);
            }

            // Vérifier que le MIME type détecté correspond au MIME type déclaré
            $declaredMimeType = $file->getMimeType();
            if ($detectedMimeType !== $declaredMimeType) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Le fichier {$file->getClientOriginalName()} a un type de contenu non conforme (déclaré: {$declaredMimeType}, détecté: {$detectedMimeType}).",
                    ], 422);
                }

                return redirect()->back()->withErrors([
                    'images' => "Le fichier {$file->getClientOriginalName()} a un type de contenu non conforme.",
                ]);
            }

            $path = $file->store('media', 'public');

            $imageSize = @getimagesize($realPath);

            $item = MediaItem::create([
                'name' => FileHelper::sanitizeFilename($file->getClientOriginalName()),
                'path' => $path,
                'mime_type' => $detectedMimeType, // Utiliser le MIME type détecté
                'size' => $file->getSize(),
                'width' => $imageSize ? $imageSize[0] : null,
                'height' => $imageSize ? $imageSize[1] : null,
                'folder_id' => $request->folder_id,
            ]);

            $uploadedItems[] = $item;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'items' => $uploadedItems,
            ]);
        }

        return redirect()->back()->with('success', count($uploadedItems).' image(s) téléchargée(s) avec succès.');
    }

    public function destroy(MediaItem $item)
    {
        $this->authorize('media.delete');

        $this->deleteMediaFiles($item);
        $item->delete();

        return redirect()->back()->with('success', 'Image supprimée avec succès.');
    }

    public function optimize(Request $request, MediaItem $item)
    {
        $this->authorize('media.upload');

        $validated = $request->validate([
            'quality' => 'nullable|integer|min:10|max:100',
            'optimize_original' => 'nullable|boolean',
            'format_webp' => 'nullable|boolean',
            'format_avif' => 'nullable|boolean',
            // Backward compatibility with older UI payload
            'formats' => 'nullable|array',
            'formats.*' => 'nullable|string|in:webp,avif',
        ]);

        if (! $this->isMimeOptimizable((string) $item->mime_type)) {
            return redirect()->back()->with('error', 'Ce type de fichier ne peut pas être optimisé.');
        }

        if (! is_string($item->path) || $item->path === '' || str_starts_with($item->path, 'http')) {
            return redirect()->back()->with('error', 'Le fichier média est invalide ou externe.');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($item->path)) {
            return redirect()->back()->with('error', 'Fichier source introuvable.');
        }

        if (! extension_loaded('gd')) {
            return redirect()->back()->with('error', 'L\'extension GD est requise pour optimiser les images.');
        }

        $quality = (int) ($validated['quality'] ?? 80);
        $optimizeOriginal = filter_var($validated['optimize_original'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $formats = [];

        if (filter_var($validated['format_webp'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $formats[] = 'webp';
        }
        if (filter_var($validated['format_avif'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $formats[] = 'avif';
        }

        // Backward compatibility when old `formats[]` payload is sent.
        if ($formats === [] && isset($validated['formats']) && is_array($validated['formats'])) {
            $legacyFormats = array_map(static fn ($format): string => strtolower((string) $format), $validated['formats']);
            $legacyFormats = array_values(array_intersect($legacyFormats, ['webp', 'avif']));
            $formats = array_values(array_unique($legacyFormats));
        }

        // If no target format is selected, optimize the current image only.
        if ($formats === []) {
            $optimizeOriginal = true;
        }

        $absoluteSourcePath = $disk->path($item->path);
        $sourceImage = $this->createImageResource($absoluteSourcePath, (string) $item->mime_type);
        if ($sourceImage === null) {
            $decodeSupportError = $this->getDecodeSupportError((string) $item->mime_type);
            if ($decodeSupportError !== null) {
                return redirect()->back()->with('error', $decodeSupportError);
            }

            return redirect()->back()->with('error', 'Impossible de lire cette image pour optimisation.');
        }

        $generatedFormats = [];
        $failedFormats = [];
        try {
            $this->prepareImageResourceForEncoding($sourceImage);

            if ($optimizeOriginal) {
                $this->saveOriginalWithQuality($sourceImage, (string) $item->mime_type, $absoluteSourcePath, $quality);
            }

            foreach ($formats as $format) {
                $variantPath = $format === 'avif' ? $item->avif_path : $item->webp_path;
                if (! is_string($variantPath) || $variantPath === '') {
                    continue;
                }
                if ($variantPath === $item->path) {
                    // Same extension as source: handled only by "optimize_original".
                    if (! $optimizeOriginal) {
                        $failedFormats[] = strtoupper($format);
                    }
                    continue;
                }

                $absoluteVariantPath = $disk->path($variantPath);
                File::ensureDirectoryExists(dirname($absoluteVariantPath));

                try {
                    $this->saveImageInFormat($sourceImage, $format, $absoluteVariantPath, $quality);
                    $generatedFormats[] = strtoupper($format);
                } catch (\RuntimeException $e) {
                    $failedFormats[] = strtoupper($format);
                }
            }

            clearstatcache(true, $absoluteSourcePath);
            $sourceSize = @filesize($absoluteSourcePath);
            if (is_int($sourceSize) && $sourceSize > 0) {
                $item->size = $sourceSize;
                $item->save();
            }
        } catch (\RuntimeException $e) {
            imagedestroy($sourceImage);

            return redirect()->back()->with('error', $e->getMessage());
        }

        imagedestroy($sourceImage);

        if ($generatedFormats === [] && ! $optimizeOriginal) {
            $errorMessage = 'Aucun format n\'a ete genere.';
            if ($failedFormats !== []) {
                $errorMessage .= ' Formats non disponibles: '.implode(', ', $failedFormats).'.';
            }

            return redirect()->back()->with('error', $errorMessage);
        }

        $message = 'Image optimisée avec succès.';
        if ($generatedFormats !== []) {
            $message .= ' Formats générés: '.implode(', ', $generatedFormats).'.';
        }
        if ($failedFormats !== []) {
            $message .= ' Formats ignores: '.implode(', ', $failedFormats).'.';
        }
        if ($optimizeOriginal) {
            $message .= ' Original recompressé.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function createFolder(Request $request)
    {
        $this->authorize('media.upload');

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_folders,id',
        ]);

        $folder = MediaFolder::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
        ]);

        return redirect()->back()->with('success', 'Dossier créé avec succès.');
    }

    public function destroyFolder(MediaFolder $folder)
    {
        $this->authorize('media.delete');

        // Supprimer toutes les images du dossier
        foreach ($folder->items as $item) {
            $this->deleteMediaFiles($item);
            $item->delete();
        }

        $folder->delete();

        return redirect()->back()->with('success', 'Dossier supprimé avec succès.');
    }

    public function apiIndex(Request $request)
    {
        $this->authorize('media.view');

        $folderId = $request->get('folder_id');

        $folders = MediaFolder::where('parent_id', $folderId)
            ->get()
            ->map(fn ($folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'type' => 'folder',
            ]);

        $items = MediaItem::where('folder_id', $folderId)
            ->latest()
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'url' => $item->url,
                'thumb' => $item->url,
                'avif_url' => $item->avif_url,
                'webp_url' => $item->webp_url,
                'optimized_url' => $item->optimized_url,
                'width' => $item->width,
                'height' => $item->height,
                'size' => $item->size_formatted,
                'size_level' => $item->size_level,
                'size_level_label' => $item->size_level_label,
                'is_optimizable' => $item->is_optimizable,
                'type' => 'image',
            ]);

        $breadcrumbs = [];
        if ($folderId) {
            $folder = MediaFolder::find($folderId);
            if ($folder) {
                $current = $folder;
                while ($current) {
                    array_unshift($breadcrumbs, [
                        'id' => $current->id,
                        'name' => $current->name,
                    ]);
                    $current = $current->parent;
                }
            }
        }

        return response()->json([
            'folders' => $folders,
            'items' => $items,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    private function deleteMediaFiles(MediaItem $item): void
    {
        $paths = array_filter([
            is_string($item->path) ? $item->path : null,
            $item->webp_path,
            $item->avif_path,
        ]);

        if ($paths === []) {
            return;
        }

        Storage::disk('public')->delete($paths);
    }

    private function isMimeOptimizable(string $mimeType): bool
    {
        return in_array($mimeType, self::OPTIMIZABLE_MIME_TYPES, true);
    }

    /**
     * @return \GdImage|resource|null
     */
    private function createImageResource(string $absolutePath, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($absolutePath) : null,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($absolutePath) : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            default => null,
        };
    }

    /**
     * @param  \GdImage|resource  $image
     */
    private function prepareImageResourceForEncoding($image): void
    {
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($image);
        }
        @imagealphablending($image, true);
        @imagesavealpha($image, true);
    }

    /**
     * @param  \GdImage|resource  $image
     */
    private function saveImageInFormat($image, string $format, string $targetPath, int $quality): void
    {
        $success = match ($format) {
            'webp' => function_exists('imagewebp')
                ? @imagewebp($image, $targetPath, $quality)
                : false,
            'avif' => function_exists('imageavif')
                ? @imageavif($image, $targetPath, $quality)
                : false,
            default => false,
        };

        if (! $success) {
            if ($format === 'avif' && ! function_exists('imageavif')) {
                throw new \RuntimeException('AVIF non supporté sur ce serveur (GD sans AVIF).');
            }
            if ($format === 'webp' && ! function_exists('imagewebp')) {
                throw new \RuntimeException('WEBP non supporté sur ce serveur (GD sans WEBP).');
            }

            throw new \RuntimeException("Echec de génération du format {$format}.");
        }
    }

    /**
     * @param  \GdImage|resource  $image
     */
    private function saveOriginalWithQuality($image, string $mimeType, string $targetPath, int $quality): void
    {
        $success = match ($mimeType) {
            'image/jpeg', 'image/jpg' => function_exists('imagejpeg')
                ? @imagejpeg($image, $targetPath, $quality)
                : false,
            'image/png' => function_exists('imagepng')
                ? @imagepng($image, $targetPath, $this->qualityToPngCompression($quality))
                : false,
            'image/webp' => function_exists('imagewebp') ? @imagewebp($image, $targetPath, $quality) : false,
            default => false,
        };

        if (! $success) {
            $encodeSupportError = $this->getEncodeSupportError($mimeType);
            if ($encodeSupportError !== null) {
                throw new \RuntimeException($encodeSupportError);
            }

            throw new \RuntimeException('Impossible de compresser le fichier original avec cette qualité.');
        }
    }

    private function qualityToPngCompression(int $quality): int
    {
        // GD PNG: 0 (pas de compression) -> 9 (max compression)
        return (int) round((100 - max(min($quality, 100), 0)) / 100 * 9);
    }

    private function getDecodeSupportError(string $mimeType): ?string
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => function_exists('imagecreatefromjpeg')
                ? null
                : 'JPEG non supporte sur ce serveur (GD sans decode JPEG).',
            'image/png' => function_exists('imagecreatefrompng')
                ? null
                : 'PNG non supporte sur ce serveur (GD sans decode PNG).',
            'image/webp' => function_exists('imagecreatefromwebp')
                ? null
                : 'WEBP non supporte sur ce serveur (GD sans decode WEBP).',
            default => null,
        };
    }

    private function getEncodeSupportError(string $mimeType): ?string
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => function_exists('imagejpeg')
                ? null
                : 'JPEG non supporte sur ce serveur (GD sans encodage JPEG).',
            'image/png' => function_exists('imagepng')
                ? null
                : 'PNG non supporte sur ce serveur (GD sans encodage PNG).',
            'image/webp' => function_exists('imagewebp')
                ? null
                : 'WEBP non supporte sur ce serveur (GD sans encodage WEBP).',
            default => null,
        };
    }
}
