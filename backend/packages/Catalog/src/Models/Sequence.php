<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property mixed $name
 * @property mixed $prefix
 * @property int $current_value
 * @property int $padding
 */
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
