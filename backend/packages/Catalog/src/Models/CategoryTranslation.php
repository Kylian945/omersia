<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $category_id
 * @property mixed $locale
 * @property mixed $name
 * @property mixed $slug
 * @property mixed $description
 * @property mixed $meta_title
 * @property mixed $meta_description
 * @property-read Category|null $category
 */
class CategoryTranslation extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\CategoryTranslationFactory::new();
    }

    protected $fillable = [
        'category_id',
        'locale',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
