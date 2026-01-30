<?php

declare(strict_types=1);

namespace Omersia\Apparence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommercePageTranslation extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Apparence\Database\Factories\EcommercePageTranslationFactory::new();
    }

    protected $fillable = [
        'ecommerce_page_id',
        'locale',
        'title',
        'content_json',
        'meta_title',
        'meta_description',
        'noindex',
    ];

    protected $casts = [
        'content_json' => 'array',
        'noindex' => 'boolean',
    ];

    public function page()
    {
        return $this->belongsTo(EcommercePage::class, 'ecommerce_page_id');
    }
}
