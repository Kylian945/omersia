<?php

declare(strict_types=1);

namespace Omersia\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Omersia\Apparence\Models\Theme;

/**
 * @property int $id
 * @property mixed $name
 * @property mixed $code
 * @property mixed $default_locale
 * @property mixed $default_currency_id
 * @property mixed $logo_path
 * @property mixed $display_name
 * @property bool $is_active
 * @property bool $is_default
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ShopDomain> $domains
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Theme> $themes
 */
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
