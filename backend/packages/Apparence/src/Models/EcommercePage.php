<?php

declare(strict_types=1);

namespace Omersia\Apparence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Omersia\Core\Models\Shop;

/**
 * @property int $id
 * @property mixed $shop_id
 * @property mixed $type
 * @property mixed $home
 * @property mixed $category
 * @property mixed $product
 * @property mixed $slug
 * @property array<string, mixed>|null $config
 * @property bool $is_active
 * @property-read Shop|null $shop
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EcommercePageTranslation> $translations
 */
class EcommercePage extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Apparence\Database\Factories\EcommercePageFactory::new();
    }

    protected $fillable = [
        'shop_id',
        'type', // 'home', 'category', 'product'
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * @return HasMany<EcommercePageTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(EcommercePageTranslation::class);
    }

    public function translation($locale = 'fr')
    {
        return $this->translations()->where('locale', $locale)->first();
    }
}
