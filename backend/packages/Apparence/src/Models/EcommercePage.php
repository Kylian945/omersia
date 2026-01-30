<?php

declare(strict_types=1);

namespace Omersia\Apparence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Omersia\Core\Models\Shop;

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

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(EcommercePageTranslation::class);
    }

    public function translation($locale = 'fr')
    {
        return $this->translations()->where('locale', $locale)->first();
    }
}
