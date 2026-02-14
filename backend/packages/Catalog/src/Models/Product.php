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

/**
 * @property int $id
 * @property mixed $shop_id
 * @property mixed $sku
 * @property mixed $type
 * @property bool $is_active
 * @property bool $manage_stock
 * @property mixed $stock_qty
 * @property float $price
 * @property float|null $compare_at_price
 * @property string|null $main_image_url
 * @property-read \Omersia\Core\Models\Shop|null $shop
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductTranslation> $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $categories
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductImage> $images
 * @property-read ProductImage|null $mainImage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $relatedProducts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductOption> $options
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariant> $variants
 */
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

    /**
     * @return BelongsTo<\Omersia\Core\Models\Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(\Omersia\Core\Models\Shop::class);
    }

    /**
     * @return HasMany<ProductTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
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
    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
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
    /**
     * @return BelongsToMany<Product, $this>
     */
    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'product_related',
            'product_id',
            'related_product_id',
        );
    }

    // variants
    /**
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
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
     * LAR-001: Fix N+1 - Eager load relations before accessing them
     */
    public function toSearchableArray(): array
    {
        $translation = $this->translation();

        // LAR-001: Eager load categories if not already loaded
        if (! $this->relationLoaded('categories')) {
            $this->load('categories');
        }

        // For variant products, calculate total stock from all active variants
        $stockQty = $this->stock_qty;
        if ($this->hasVariants()) {
            // LAR-001: Eager load variants if not already loaded
            if (! $this->relationLoaded('variants')) {
                $this->load('variants');
            }
            $stockQty = $this->variants
                ->where('is_active', true)
                ->sum('stock_qty');
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $translation ? ($translation->name ?? '') : '',
            'description' => $translation ? ($translation->description ?? '') : '',
            'short_description' => $translation ? ($translation->short_description ?? '') : '',
            'slug' => $translation ? ($translation->slug ?? '') : '',
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'is_active' => $this->is_active,
            'manage_stock' => $this->manage_stock,
            'stock_qty' => $stockQty,
            'type' => $this->type,
            'shop_id' => $this->shop_id,
            'image_url' => $this->main_image_url,
            'categories' => $this->categories->pluck('id')->all(),
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
