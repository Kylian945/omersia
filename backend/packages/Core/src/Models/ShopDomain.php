<?php

declare(strict_types=1);

namespace Omersia\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $shop_id
 * @property mixed $domain
 * @property mixed $is_primary
 * @property-read Shop|null $shop
 */
class ShopDomain extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Core\Database\Factories\ShopDomainFactory::new();
    }

    protected $fillable = [
        'shop_id',
        'domain',
        'is_primary',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
