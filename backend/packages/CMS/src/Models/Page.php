<?php

declare(strict_types=1);

namespace Omersia\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property mixed $shop_id
 * @property mixed $type
 * @property bool $is_active
 * @property bool $is_home
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PageTranslation> $translations
 */
class Page extends Model
{
    protected $table = 'cms_pages';

    protected $fillable = [
        'shop_id',
        'type',
        'is_active',
        'is_home',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'is_home' => 'bool',
    ];

    /**
     * @return HasMany<PageTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class, 'page_id');
    }

    public function translation(?string $locale = null): ?PageTranslation
    {
        $locale = $locale ?? app()->getLocale();

        return $this->translations
            ->where('locale', $locale)
            ->first();
    }
}
