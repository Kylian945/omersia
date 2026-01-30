<?php

declare(strict_types=1);

namespace Omersia\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['slug', 'name', 'version', 'enabled', 'manifest'];

    protected $casts = ['enabled' => 'boolean', 'manifest' => 'array'];
}
