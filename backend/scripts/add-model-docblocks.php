<?php

declare(strict_types=1);

/**
 * Add @property / @property-read docblocks to Eloquent models that don't have them.
 * The generated types are inferred from $casts and common relation declarations.
 */
$root = dirname(__DIR__);
$modelDirs = [
    $root.'/app/Models',
    $root.'/packages',
];

$modelFiles = [];
foreach ($modelDirs as $dir) {
    if (! is_dir($dir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $fileInfo) {
        if (! $fileInfo->isFile()) {
            continue;
        }

        $path = $fileInfo->getPathname();
        if (! str_ends_with($path, '.php')) {
            continue;
        }

        if (str_contains($path, '/app/Models/') || str_contains($path, '/src/Models/')) {
            $modelFiles[] = $path;
        }
    }
}

sort($modelFiles);

$updated = 0;
$skipped = 0;

foreach ($modelFiles as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        fwrite(STDERR, "Cannot read {$file}\n");

        continue;
    }

    if (! preg_match('/\nclass\s+([A-Za-z0-9_]+)\s+extends\s+Model\b/', $content, $classMatch)) {
        $skipped++;

        continue;
    }

    if (str_contains($content, '@property')) {
        $skipped++;

        continue;
    }

    $casts = [];
    if (preg_match('/protected\s+\$casts\s*=\s*\[(.*?)\];/s', $content, $castsMatch)) {
        if (preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/m", $castsMatch[1], $castEntries, PREG_SET_ORDER)) {
            foreach ($castEntries as $entry) {
                $casts[$entry[1]] = strtolower($entry[2]);
            }
        }
    }

    $fillableFields = [];
    if (preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\];/s', $content, $fillableMatch)) {
        if (preg_match_all("/'([^']+)'/m", $fillableMatch[1], $fillableEntries)) {
            $fillableFields = $fillableEntries[1];
        }
    }

    $propertyLines = [
        ' * @property int $id',
    ];

    foreach ($fillableFields as $field) {
        if ($field === 'id') {
            continue;
        }

        $type = mapCastToPhpDocType($casts[$field] ?? null);
        $propertyLines[] = " * @property {$type} \${$field}";
    }

    $relations = [];
    if (preg_match_all(
        '/public function\s+([A-Za-z0-9_]+)\s*\([^)]*\)[^{]*\{[\s\S]*?return\s+\$this->(hasMany|belongsToMany|morphMany|hasManyThrough|belongsTo|hasOne|morphOne)\(\s*([A-Za-z0-9_\\\\]+)::class/s',
        $content,
        $relationMatches,
        PREG_SET_ORDER
    )) {
        foreach ($relationMatches as $match) {
            $method = $match[1];
            $relationType = $match[2];
            $relatedModel = ltrim($match[3], '\\');

            if (isset($relations[$method])) {
                continue;
            }

            if (in_array($relationType, ['hasMany', 'belongsToMany', 'morphMany', 'hasManyThrough'], true)) {
                $relations[$method] = " * @property-read \\Illuminate\\Database\\Eloquent\\Collection<int, {$relatedModel}> \${$method}";
            } else {
                $relations[$method] = " * @property-read {$relatedModel}|null \${$method}";
            }
        }
    }

    foreach ($relations as $line) {
        $propertyLines[] = $line;
    }

    $docblock = "/**\n".implode("\n", $propertyLines)."\n */\n";

    $newContent = preg_replace('/\nclass\s+[A-Za-z0-9_]+\s+extends\s+Model\b/', "\n{$docblock}class {$classMatch[1]} extends Model", $content, 1);
    if ($newContent === null || $newContent === $content) {
        fwrite(STDERR, "No change for {$file}\n");

        continue;
    }

    file_put_contents($file, $newContent);
    $updated++;
}

echo "Updated models: {$updated}\n";
echo "Skipped models: {$skipped}\n";

function mapCastToPhpDocType(?string $cast): string
{
    if ($cast === null) {
        return 'mixed';
    }

    if (str_starts_with($cast, 'encrypted:')) {
        $cast = substr($cast, strlen('encrypted:'));
    }

    if (str_starts_with($cast, 'decimal:')) {
        return 'float|int|string|null';
    }

    return match ($cast) {
        'bool', 'boolean' => 'bool',
        'int', 'integer' => 'int',
        'real', 'float', 'double' => 'float',
        'array', 'json' => 'array<string, mixed>|null',
        'collection' => '\\Illuminate\\Support\\Collection<int, mixed>',
        'datetime', 'immutable_datetime', 'date', 'immutable_date', 'timestamp' => '\\Illuminate\\Support\\Carbon|null',
        'string', 'hashed' => 'string',
        default => 'mixed',
    };
}
