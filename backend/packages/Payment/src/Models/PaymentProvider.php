<?php

declare(strict_types=1);

namespace Omersia\Payment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentProvider extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Payment\Database\Factories\PaymentProviderFactory::new();
    }

    protected $fillable = ['name', 'code', 'enabled', 'config'];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'encrypted:array',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
