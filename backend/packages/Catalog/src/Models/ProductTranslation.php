<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $product_id
 * @property mixed $locale
 * @property mixed $name
 * @property mixed $slug
 * @property mixed $short_description
 * @property mixed $description
 * @property mixed $meta_title
 * @property mixed $meta_description
 * @property-read Product|null $product
 */
class ProductTranslation extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\ProductTranslationFactory::new();
    }

    protected $fillable = [
        'product_id',
        'locale',
        'name',
        'slug',
        'short_description',
        'description',
        'meta_title',
        'meta_description',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
