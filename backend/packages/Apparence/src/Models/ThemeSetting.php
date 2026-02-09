<?php

declare(strict_types=1);

namespace Omersia\Apparence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $theme_id
 * @property mixed $key
 * @property string $value
 * @property mixed $type
 * @property mixed $group
 * @property-read Theme|null $theme
 */
class ThemeSetting extends Model
{
    protected $fillable = [
        'theme_id',
        'key',
        'value',
        'type',
        'group',
    ];

    protected $casts = [
        'value' => 'string', // On gère la sérialisation manuellement selon le type
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Get the decoded value based on type
     */
    public function getDecodedValue()
    {
        return match ($this->type) {
            'json' => json_decode($this->value, true),
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->value) ? (float) $this->value : 0,
            default => $this->value,
        };
    }

    /**
     * Set value with automatic encoding based on type
     */
    public function setEncodedValue($value): void
    {
        $this->value = match ($this->type) {
            'json' => is_string($value) ? $value : json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
