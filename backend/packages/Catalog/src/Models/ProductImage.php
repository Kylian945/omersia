<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $product_id
 * @property mixed $path
 * @property mixed $position
 * @property bool $is_main
 * @property-read Product|null $product
 */
class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'path',
        'position',
        'is_main',
    ];

    protected $casts = [
        'is_main' => 'bool',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        // Si le chemin contient déjà un protocole (http/https), retourner directement
        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://')) {
            return $this->path;
        }

        return asset('storage/'.$this->path);
    }
}
