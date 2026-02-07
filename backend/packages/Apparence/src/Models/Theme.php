<?php

declare(strict_types=1);

namespace Omersia\Apparence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Omersia\Core\Models\Shop;

/**
 * @property int $id
 * @property mixed $shop_id
 * @property mixed $name
 * @property mixed $slug
 * @property mixed $description
 * @property mixed $version
 * @property mixed $author
 * @property mixed $preview_image
 * @property mixed $zip_path
 * @property mixed $component_path
 * @property mixed $pages_config_path
 * @property array<string, mixed>|null $widgets_config
 * @property array<string, mixed>|null $settings_schema
 * @property bool $is_active
 * @property bool $is_default
 * @property array<string, mixed>|null $metadata
 * @property-read Shop|null $shop
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ThemeSetting> $settings
 */
class Theme extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Apparence\Database\Factories\ThemeFactory::new();
    }

    protected $fillable = [
        'shop_id',
        'name',
        'slug',
        'description',
        'version',
        'author',
        'preview_image',
        'zip_path',
        'component_path',
        'pages_config_path',
        'widgets_config',
        'settings_schema',
        'is_active',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'metadata' => 'array',
        'widgets_config' => 'array',
        'settings_schema' => 'array',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ThemeSetting::class);
    }

    /**
     * Get all settings as a grouped array
     */
    public function getSettingsArray(): array
    {
        $settings = [];
        foreach ($this->settings as $setting) {
            $group = $setting->group ?: 'general';
            $key = $setting->key;
            $settings[$group][$key] = $setting->getDecodedValue();
        }

        return $settings;
    }

    /**
     * Get a specific setting value
     */
    public function getSetting(string $key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();

        return $setting ? $setting->getDecodedValue() : $default;
    }

    /**
     * Get widgets configuration for this theme
     */
    public function getWidgets(): array
    {
        return $this->widgets_config ?? [];
    }

    /**
     * Get widget types available in this theme
     */
    public function getWidgetTypes(): array
    {
        return array_column($this->getWidgets(), 'type');
    }

    /**
     * Check if a widget type is supported by this theme
     */
    public function hasWidget(string $type): bool
    {
        return in_array($type, $this->getWidgetTypes());
    }

    /**
     * Mutator to map 'widgets' attribute to 'widgets_config'
     * This is for backward compatibility with tests
     */
    public function setWidgetsAttribute($value): void
    {
        $this->attributes['widgets_config'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Accessor to map 'widgets_config' to 'widgets' attribute
     * This is for backward compatibility with tests
     */
    public function getWidgetsAttribute()
    {
        return $this->widgets_config;
    }

    /**
     * Get the settings schema for this theme
     */
    public function getSettingsSchema(): ?array
    {
        return $this->settings_schema;
    }

    /**
     * Check if theme has a custom settings schema
     */
    public function hasSettingsSchema(): bool
    {
        return ! empty($this->settings_schema);
    }

    /**
     * Get default settings values from the schema
     */
    public function getDefaultSettingsFromSchema(): array
    {
        if (! $this->hasSettingsSchema()) {
            return [];
        }

        $defaults = [];
        foreach ($this->settings_schema as $group => $settings) {
            foreach ($settings as $key => $config) {
                $defaults[$group][$key] = $config['default'] ?? null;
            }
        }

        return $defaults;
    }
}
