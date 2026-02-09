<?php

declare(strict_types=1);

namespace Omersia\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $appends = ['url', 'size_formatted'];

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
}
