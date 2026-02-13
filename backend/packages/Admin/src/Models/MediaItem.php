<?php

declare(strict_types=1);

namespace Omersia\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property mixed $name
 * @property mixed $path
 * @property mixed $mime_type
 * @property mixed $size
 * @property mixed $width
 * @property mixed $height
 * @property mixed $folder_id
 * @property-read MediaFolder|null $folder
 */
class MediaItem extends Model
{
    private const SIZE_OK_LIMIT_BYTES = 102400; // 100 KB

    private const SIZE_WARNING_LIMIT_BYTES = 307200; // 300 KB

    private const OPTIMIZABLE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    protected $table = 'media_library';

    protected $fillable = [
        'name',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'folder_id',
    ];

    protected $appends = [
        'url',
        'size_formatted',
        'size_level',
        'size_level_label',
        'is_optimizable',
        'webp_url',
        'avif_url',
        'optimized_url',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function getUrlAttribute(): string
    {
        if (! $this->path) {
            return '';
        }

        // Si le path commence déjà par http, le retourner tel quel
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }

        // Utiliser la méthode asset() de Laravel pour générer l'URL
        return asset('storage/'.$this->path);
    }

    public function getSizeFormattedAttribute(): string
    {
        if (! $this->size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2).' '.$units[$unitIndex];
    }

    public function getSizeLevelAttribute(): string
    {
        $size = (int) ($this->size ?? 0);
        if ($size <= 0) {
            return 'ok';
        }

        if ($size < self::SIZE_OK_LIMIT_BYTES) {
            return 'ok';
        }

        if ($size < self::SIZE_WARNING_LIMIT_BYTES) {
            return 'warning';
        }

        return 'optimize';
    }

    public function getSizeLevelLabelAttribute(): string
    {
        return match ($this->size_level) {
            'ok' => 'OK',
            'warning' => 'Warning',
            default => 'A optimiser',
        };
    }

    public function getIsOptimizableAttribute(): bool
    {
        return in_array((string) $this->mime_type, self::OPTIMIZABLE_MIME_TYPES, true);
    }

    public function getWebpUrlAttribute(): ?string
    {
        return $this->getVariantUrl('webp');
    }

    public function getAvifUrlAttribute(): ?string
    {
        return $this->getVariantUrl('avif');
    }

    public function getOptimizedUrlAttribute(): string
    {
        // AVIF first (best compression), then WEBP, then original.
        return $this->avif_url ?: ($this->webp_url ?: $this->url);
    }

    public function getWebpPathAttribute(): ?string
    {
        return $this->buildVariantPath('webp');
    }

    public function getAvifPathAttribute(): ?string
    {
        return $this->buildVariantPath('avif');
    }

    protected function buildVariantPath(string $extension): ?string
    {
        if (! $this->path || str_starts_with((string) $this->path, 'http')) {
            return null;
        }

        $currentPath = (string) $this->path;
        $pathWithoutExtension = preg_replace('/\.[^\.\/]+$/', '', $currentPath);
        if (! is_string($pathWithoutExtension) || $pathWithoutExtension === '') {
            return null;
        }

        return $pathWithoutExtension.'.'.$extension;
    }

    protected function getVariantUrl(string $extension): ?string
    {
        $variantPath = $this->buildVariantPath($extension);
        if (! is_string($variantPath) || $variantPath === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($variantPath)) {
            return null;
        }

        return asset('storage/'.$variantPath);
    }
}
