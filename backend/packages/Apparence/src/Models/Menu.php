<?php

declare(strict_types=1);

namespace Omersia\Apparence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property mixed $shop_id
 * @property mixed $name
 * @property mixed $slug
 * @property mixed $location
 * @property mixed $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MenuItem> $items
 */
class Menu extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'location',
        'is_active',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->orderBy('position')
            ->orderBy('id');
    }
}
