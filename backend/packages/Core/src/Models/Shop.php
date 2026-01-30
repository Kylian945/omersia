<?php

declare(strict_types=1);

namespace Omersia\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Omersia\Apparence\Models\Theme;

class Shop extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Core\Database\Factories\ShopFactory::new();
    }

    protected $fillable = [
        'name',
        'code',
        'default_locale',
        'default_currency_id',
        'logo_path',
        'display_name',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(ShopDomain::class);
    }

    public function themes(): HasMany
    {
        return $this->hasMany(Theme::class);
    }
}
