<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\CategoryFactory::new();
    }

    protected $fillable = [
        'shop_id',
        'parent_id',
        'is_active',
        'position',
        'image_path',
    ];

    protected $appends = ['image_url'];

    /**
     * Get the full URL for the category image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        // Si le chemin contient déjà un protocole, retourner directement
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return asset('storage/'.$this->image_path);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    public function translation(?string $locale = null): ?CategoryTranslation
    {
        $locale = $locale ?? app()->getLocale();

        return $this->translations
            ->where('locale', $locale)
            ->first();
    }
}
