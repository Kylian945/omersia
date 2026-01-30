<?php

declare(strict_types=1);

namespace Omersia\Apparence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
