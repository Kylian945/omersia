<?php

declare(strict_types=1);

namespace Omersia\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $page_id
 * @property mixed $locale
 * @property mixed $title
 * @property mixed $slug
 * @property mixed $content
 * @property array<string, mixed>|null $content_json
 * @property mixed $meta_title
 * @property mixed $meta_description
 * @property bool $noindex
 * @property-read Page|null $page
 */
class PageTranslation extends Model
{
    protected $table = 'cms_page_translations';

    protected $fillable = [
        'page_id',
        'locale',
        'title',
        'slug',
        'content',
        'content_json',
        'meta_title',
        'meta_description',
        'noindex',
    ];

    protected $casts = [
        'noindex' => 'bool',
        'content_json' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
