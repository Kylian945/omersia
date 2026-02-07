<?php

declare(strict_types=1);

namespace Omersia\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property mixed $slug
 * @property mixed $name
 * @property mixed $version
 * @property bool $enabled
 * @property array<string, mixed>|null $manifest
 */
class Module extends Model
{
    protected $fillable = ['slug', 'name', 'version', 'enabled', 'manifest'];

    protected $casts = ['enabled' => 'boolean', 'manifest' => 'array'];
}
