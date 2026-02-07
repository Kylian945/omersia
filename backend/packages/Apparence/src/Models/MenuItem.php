<?php

declare(strict_types=1);

namespace Omersia\Apparence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $menu_id
 * @property mixed $parent_id
 * @property mixed $type
 * @property mixed $label
 * @property mixed $category_id
 * @property mixed $url
 * @property bool $is_active
 * @property mixed $position
 * @property-read Menu|null $menu
 * @property-read MenuItem|null $parent
 * @property-read \Omersia\Catalog\Models\Category|null $category
 */
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
