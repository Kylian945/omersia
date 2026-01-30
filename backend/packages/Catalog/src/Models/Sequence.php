<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    protected $fillable = [
        'name',
        'prefix',
        'current_value',
        'padding',
    ];

    protected $casts = [
        'current_value' => 'integer',
        'padding' => 'integer',
    ];
}
