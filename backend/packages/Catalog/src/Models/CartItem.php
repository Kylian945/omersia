<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\CartItemFactory::new();
    }

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'name',
        'variant_label',
        'unit_price',
        'old_price',
        'qty',
        'image_url',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
}
