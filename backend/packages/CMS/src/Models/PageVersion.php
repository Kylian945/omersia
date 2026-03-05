<?php

declare(strict_types=1);

namespace Omersia\CMS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $page_translation_id
 * @property array<string, mixed>|null $content_json
 * @property int|null $created_by
 * @property string|null $label
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read PageTranslation|null $translation
 * @property-read User|null $creator
 */
class PageVersion extends Model
{
    use HasFactory;

    protected static function newFactory(): \Omersia\CMS\Database\Factories\PageVersionFactory
    {
        return \Omersia\CMS\Database\Factories\PageVersionFactory::new();
    }

    public const UPDATED_AT = null;

    protected $table = 'cms_page_versions';

    protected $fillable = [
        'page_translation_id',
        'content_json',
        'created_by',
        'label',
    ];

    protected $casts = [
        'content_json' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PageTranslation, $this>
     */
    public function translation(): BelongsTo
    {
        return $this->belongsTo(PageTranslation::class, 'page_translation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

