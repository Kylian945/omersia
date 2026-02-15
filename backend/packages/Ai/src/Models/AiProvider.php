<?php

declare(strict_types=1);

namespace Omersia\Ai\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_enabled
 * @property bool $is_default
 * @property array<string, mixed>|null $config
 */
class AiProvider extends Model
{
    /**
     * @var array<string, array<int, string>>
     */
    private const DRIVER_MODEL_SUGGESTIONS = [
        'openai' => [
            'gpt-4.1-mini',
            'gpt-4.1',
            'gpt-4o-mini',
        ],
        'anthropic' => [
            'claude-3-5-sonnet-latest',
            'claude-3-7-sonnet-latest',
        ],
        'gemini' => [
            'gemini-2.0-flash',
            'gemini-1.5-pro',
        ],
        'groq' => [
            'llama-3.3-70b-versatile',
        ],
        'xai' => [
            'grok-2-latest',
        ],
        'deepseek' => [
            'deepseek-chat',
        ],
        'mistral' => [
            'mistral-large-latest',
        ],
        'ollama' => [
            'llama3.1',
        ],
        'openrouter' => [
            'openai/gpt-4o-mini',
        ],
    ];

    /**
     * @var array<int, string>
     */
    private const SUPPORTED_DRIVERS = [
        'openai',
        'anthropic',
        'gemini',
        'groq',
        'xai',
        'deepseek',
        'mistral',
        'ollama',
        'openrouter',
    ];

    protected $fillable = [
        'code',
        'name',
        'is_enabled',
        'is_default',
        'config',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'config' => 'encrypted:array',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function getSupportedProviders(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public static function getSupportedCodes(): array
    {
        return self::query()->pluck('code')->filter(static fn ($value): bool => is_string($value))->values()->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function getModelSuggestions(): array
    {
        return self::DRIVER_MODEL_SUGGESTIONS;
    }

    /**
     * @return array<int, string>
     */
    public static function getSupportedDrivers(): array
    {
        return self::SUPPORTED_DRIVERS;
    }

    public static function ensureCoreProviders(): void
    {
        $records = self::query()->get();

        foreach ($records as $record) {
            $currentConfig = is_array($record->config) ? $record->config : [];
            $driver = strtolower(trim((string) ($currentConfig['driver'] ?? 'openai')));
            if (! in_array($driver, self::SUPPORTED_DRIVERS, true)) {
                $driver = 'openai';
            }

            $record->config = [
                'driver' => $driver,
                'model' => trim((string) ($currentConfig['model'] ?? '')) ?: null,
                'base_url' => trim((string) ($currentConfig['base_url'] ?? '')) ?: null,
                'organization' => trim((string) ($currentConfig['organization'] ?? '')) ?: null,
                'api_version' => trim((string) ($currentConfig['api_version'] ?? '')) ?: null,
                'api_key' => $currentConfig['api_key'] ?? null,
            ];

            $record->save();
        }

        self::query()
            ->where('is_enabled', false)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        $config = is_array($this->config) ? $this->config : [];

        return $config[$key] ?? $default;
    }

    public function hasApiKey(): bool
    {
        $apiKey = $this->getConfigValue('api_key');

        return is_string($apiKey) && trim($apiKey) !== '';
    }

    public function getDriver(): string
    {
        $defaultDriver = 'openai';
        $driver = strtolower(trim((string) $this->getConfigValue('driver', $defaultDriver)));

        if ($driver === '' || ! in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            return $defaultDriver;
        }

        return $driver;
    }

    public function getModel(): ?string
    {
        $model = trim((string) $this->getConfigValue('model', ''));

        return $model === '' ? null : $model;
    }

    public static function getDefaultModelForDriver(string $driver): ?string
    {
        $normalizedDriver = strtolower(trim($driver));
        $models = self::DRIVER_MODEL_SUGGESTIONS[$normalizedDriver] ?? [];

        return $models[0] ?? null;
    }
}
