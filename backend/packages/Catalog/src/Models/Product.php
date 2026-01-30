<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory;
    use Searchable;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\ProductFactory::new();
    }

    protected $fillable = [
        'shop_id',
        'sku',
        'type',
        'is_active',
        'manage_stock',
        'stock_qty',
        'price',
        'compare_at_price',

    ];

    protected $casts = [
        'is_active' => 'bool',
        'manage_stock' => 'bool',
        'price' => 'float',
        'compare_at_price' => 'float',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\Omersia\Core\Models\Shop::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function translation(?string $locale = null): ?ProductTranslation
    {
        $locale = $locale ?? app()->getLocale();

        return $this->translations
            ->where('locale', $locale)
            ->first();
    }

    // images
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    public function getMainImageUrlAttribute(): ?string
    {
        if ($this->mainImage) {
            return $this->mainImage->url;
        }

        $first = $this->images->first();

        return $first ? $first->url : null;
    }

    // related products
    public function relatedProducts()
    {
        return $this->belongsToMany(
            self::class,
            'product_related',
            'product_id',
            'related_product_id',
        );
    }

    // variants
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function hasVariants(): bool
    {
        return $this->type === 'variant';
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        $translation = $this->translation();

        // For variant products, calculate total stock from all active variants
        $stockQty = $this->stock_qty;
        if ($this->hasVariants()) {
            $stockQty = $this->variants()
                ->where('is_active', true)
                ->sum('stock_qty');
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $translation?->name ?? '',
            'description' => $translation?->description ?? '',
            'short_description' => $translation?->short_description ?? '',
            'slug' => $translation?->slug ?? '',
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'is_active' => $this->is_active,
            'manage_stock' => $this->manage_stock,
            'stock_qty' => $stockQty,
            'type' => $this->type,
            'shop_id' => $this->shop_id,
            'image_url' => $this->main_image_url,
            'categories' => $this->categories->pluck('id')->toArray(),
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }
}
