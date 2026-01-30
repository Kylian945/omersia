<?php

declare(strict_types=1);

namespace Omersia\Apparence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'type',
        'label',
        'category_id',
        'url',
        'is_active',
        'position',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function category(): BelongsTo
    {
        // adapte le namespace à ton projet
        return $this->belongsTo(\Omersia\Catalog\Models\Category::class, 'category_id');
    }
}
