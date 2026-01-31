<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Omersia\Admin\Models\MediaFolder;
use Omersia\Admin\Models\MediaItem;
use Omersia\Shared\Helpers\FileHelper;

class MediaLibraryController extends Controller
{
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
            if (!in_array($detectedMimeType, $allowedMimeTypes, true)) {
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

        Storage::disk('public')->delete($item->path);
        $item->delete();

        return redirect()->back()->with('success', 'Image supprimée avec succès.');
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
            Storage::disk('public')->delete($item->path);
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
                'width' => $item->width,
                'height' => $item->height,
                'size' => $item->size_formatted,
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
}
