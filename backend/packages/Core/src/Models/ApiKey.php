<?php

declare(strict_types=1);

namespace Omersia\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Génère et assigne une clé API unique.
     */
    public static function generate(string $name): self
    {
        $key = Str::random(64);

        return self::create([
            'name' => $name,
            'key' => hash('sha256', $key), // stockée hashée pour sécurité
            'active' => true,
        ]);
    }

    /**
     * Vérifie si une clé donnée est valide.
     */
    public static function isValid(string $providedKey): bool
    {
        $hashedKey = hash('sha256', $providedKey);

        return self::where('key', $hashedKey)->where('active', true)->exists();
    }

    /**
     * Regénère une nouvelle clé API pour cet enregistrement.
     */
    public function regenerateKey(): string
    {
        $newKey = Str::random(64);
        $this->update(['key' => hash('sha256', $newKey)]);

        return $newKey;
    }
}
