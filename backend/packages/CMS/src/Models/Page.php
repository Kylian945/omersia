<?php

declare(strict_types=1);

namespace Omersia\CMS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property mixed $shop_id
 * @property mixed $type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property int|null $published_by
 * @property bool $is_active
 * @property bool $is_home
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PageTranslation> $translations
 * @property-read User|null $publisher
 */
class Page extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected static function newFactory(): \Omersia\CMS\Database\Factories\PageFactory
    {
        return \Omersia\CMS\Database\Factories\PageFactory::new();
    }

    protected $table = 'cms_pages';

    protected $fillable = [
        'shop_id',
        'type',
        'status',
        'published_at',
        'published_by',
        'is_active',
        'is_home',
    ];

    protected $casts = [
        'published_at' => 'datetime',
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function translation(?string $locale = null): ?PageTranslation
    {
        $locale = $locale ?? app()->getLocale();

        return $this->translations
            ->where('locale', $locale)
            ->first();
    }
}
