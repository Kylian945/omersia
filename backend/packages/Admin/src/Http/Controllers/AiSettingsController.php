<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Omersia\Admin\Http\Requests\UpdateAiSettingsRequest;
use Omersia\Ai\Models\AiProvider;
use Omersia\Ai\Models\AiSetting;

class AiSettingsController extends Controller
{
    public function index()
    {
        $this->authorize('settings.view');

        $providers = AiProvider::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $globalContext = AiSetting::query()->firstOrCreate(
            ['scope' => AiSetting::GLOBAL_SCOPE],
            AiSetting::defaultValues()
        );

        $contexts = AiSetting::query()
            ->orderByRaw("CASE WHEN scope = '".AiSetting::GLOBAL_SCOPE."' THEN 0 ELSE 1 END")
            ->orderBy('scope')
            ->get();

        return view('admin::settings.ai.index', [
            'providers' => $providers,
            'settings' => $globalContext,
            'contexts' => $contexts,
            'usageOptions' => AiSetting::getUsageOptions(),
            'modelSuggestions' => AiProvider::getModelSuggestions(),
            'driverOptions' => AiProvider::getSupportedDrivers(),
        ]);
    }

    public function update(UpdateAiSettingsRequest $request)
    {
        $this->authorize('settings.update');

        $validated = $request->validated();
        $providersInput = Arr::wrap($validated['providers'] ?? []);
        $contextsInput = Arr::wrap($validated['contexts'] ?? []);
        $defaultProviderKey = trim((string) ($validated['default_provider'] ?? ''));
        if ($defaultProviderKey === '') {
            $defaultProviderKey = null;
        }

        DB::transaction(function () use ($providersInput, $defaultProviderKey, $contextsInput): void {
            $providerIdsByRowKey = $this->upsertProviders($providersInput);

            AiProvider::query()
                ->where('is_enabled', false)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            if (is_string($defaultProviderKey) && array_key_exists($defaultProviderKey, $providerIdsByRowKey)) {
                $defaultProviderId = (int) $providerIdsByRowKey[$defaultProviderKey];
                $defaultProvider = AiProvider::query()->find($defaultProviderId);

                if ($defaultProvider instanceof AiProvider && $defaultProvider->is_enabled) {
                    AiProvider::query()
                        ->where('id', '!=', $defaultProviderId)
                        ->update(['is_default' => false]);

                    if (! $defaultProvider->is_default) {
                        $defaultProvider->is_default = true;
                        $defaultProvider->save();
                    }
                }
            }

            $this->ensureSingleDefaultProvider();
            $this->upsertContexts($contextsInput);
        });

        return redirect()
            ->route('admin.settings.ai.index')
            ->with('success', 'Configuration IA mise à jour avec succès.');
    }

    /**
     * @param  array<int|string, mixed>  $providersInput
     * @return array<string, int>
     */
    private function upsertProviders(array $providersInput): array
    {
        $providersById = AiProvider::query()->get()->keyBy('id');
        $providerIdsByRowKey = [];

        foreach ($providersInput as $rowKey => $providerInput) {
            if (! is_array($providerInput)) {
                continue;
            }

            $providerId = is_numeric($providerInput['id'] ?? null) ? (int) $providerInput['id'] : null;
            $provider = $providerId !== null ? $providersById->get($providerId) : null;

            if (! $provider instanceof AiProvider) {
                $provider = new AiProvider;
            }

            $currentConfig = is_array($provider->config) ? $provider->config : [];
            $inputName = $this->normalizeNullableString($providerInput['name'] ?? null, 120);
            $inputDriver = strtolower(trim((string) ($providerInput['driver'] ?? '')));
            $inputModel = $this->normalizeNullableString($providerInput['model'] ?? null, 120);
            $inputBaseUrl = $this->normalizeNullableString($providerInput['base_url'] ?? null, 255);
            $inputOrganization = $this->normalizeNullableString($providerInput['organization'] ?? null, 255);
            $inputApiVersion = $this->normalizeNullableString($providerInput['api_version'] ?? null, 80);
            $newApiKey = trim((string) ($providerInput['api_key'] ?? ''));
            $isEnabled = filter_var($providerInput['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $isNew = ! $provider->exists;
            $hasMeaningfulInput = $isEnabled
                || $inputName !== null
                || $inputDriver !== ''
                || $inputModel !== null
                || $inputBaseUrl !== null
                || $inputOrganization !== null
                || $inputApiVersion !== null
                || $newApiKey !== '';

            if ($isNew && ! $hasMeaningfulInput) {
                continue;
            }

            if (! in_array($inputDriver, AiProvider::getSupportedDrivers(), true)) {
                $inputDriver = $provider->getDriver();
            }

            if ($inputDriver === '') {
                $inputDriver = 'openai';
            }

            $resolvedName = $inputName ?? trim((string) $provider->name);
            if ($resolvedName === '') {
                $resolvedName = strtoupper($inputDriver).' Provider';
            }

            if (! $provider->exists || trim((string) $provider->code) === '') {
                $provider->code = $this->generateUniqueProviderCode($resolvedName, $provider->id);
            }

            $resolvedModel = $inputModel ?? $this->normalizeNullableString($currentConfig['model'] ?? null, 120);
            if ($resolvedModel === null) {
                $resolvedModel = AiProvider::getDefaultModelForDriver($inputDriver);
            }

            $provider->name = $resolvedName;
            $provider->is_enabled = $isEnabled;
            $provider->is_default = $isEnabled ? (bool) $provider->is_default : false;
            $provider->config = [
                'driver' => $inputDriver,
                'model' => $resolvedModel,
                'base_url' => $inputBaseUrl ?? $this->normalizeNullableString($currentConfig['base_url'] ?? null, 255),
                'organization' => $inputOrganization ?? $this->normalizeNullableString($currentConfig['organization'] ?? null, 255),
                'api_version' => $inputApiVersion ?? $this->normalizeNullableString($currentConfig['api_version'] ?? null, 80),
                'api_key' => $newApiKey !== '' ? $newApiKey : ($currentConfig['api_key'] ?? null),
            ];
            $provider->save();

            $providerIdsByRowKey[(string) $rowKey] = (int) $provider->id;
        }

        return $providerIdsByRowKey;
    }

    private function ensureSingleDefaultProvider(): void
    {
        $defaultProviders = AiProvider::query()
            ->where('is_default', true)
            ->orderByDesc('updated_at')
            ->pluck('id');

        if ($defaultProviders->count() <= 1) {
            return;
        }

        $keepId = (int) $defaultProviders->first();
        AiProvider::query()
            ->where('is_default', true)
            ->where('id', '!=', $keepId)
            ->update(['is_default' => false]);
    }

    /**
     * @param  array<int|string, mixed>  $contextsInput
     */
    private function upsertContexts(array $contextsInput): void
    {
        $contextsById = AiSetting::query()->get()->keyBy('id');
        $defaults = AiSetting::defaultValues();

        foreach ($contextsInput as $contextInput) {
            if (! is_array($contextInput)) {
                continue;
            }

            $scope = strtolower(trim((string) ($contextInput['scope'] ?? '')));
            if ($scope === '') {
                continue;
            }

            $contextId = is_numeric($contextInput['id'] ?? null) ? (int) $contextInput['id'] : null;
            $context = $contextId !== null ? $contextsById->get($contextId) : null;

            if (! $context instanceof AiSetting) {
                $context = AiSetting::query()->firstWhere('scope', $scope) ?? new AiSetting;
            }

            $isGlobal = $scope === AiSetting::GLOBAL_SCOPE;
            $usageScopes = AiSetting::normalizeUsageScopesInput($contextInput['usage_scopes'] ?? []);
            if ($isGlobal) {
                $usageScopes = [AiSetting::USAGE_ALL];
            }

            $context->fill([
                'scope' => $scope,
                'usage_scopes' => $usageScopes,
                'business_context' => $this->normalizeNullableString($contextInput['business_context'] ?? null, 5000),
                'seo_objectives' => $this->normalizeNullableString($contextInput['seo_objectives'] ?? null, 5000),
                'forbidden_terms' => $this->normalizeNullableString($contextInput['forbidden_terms'] ?? null, 2000),
                'writing_tone' => $this->normalizeNullableString($contextInput['writing_tone'] ?? null, 80)
                    ?? ($isGlobal ? (string) ($defaults['writing_tone'] ?? 'professionnel') : ''),
                'content_locale' => $this->normalizeNullableString($contextInput['content_locale'] ?? null, 10)
                    ?? ($isGlobal ? (string) ($defaults['content_locale'] ?? 'fr') : ''),
                'title_max_length' => $this->normalizeContextLength(
                    $contextInput['title_max_length'] ?? null,
                    $isGlobal ? (int) ($defaults['title_max_length'] ?? 70) : 0
                ),
                'meta_description_max_length' => $this->normalizeContextLength(
                    $contextInput['meta_description_max_length'] ?? null,
                    $isGlobal ? (int) ($defaults['meta_description_max_length'] ?? 160) : 0
                ),
                'additional_instructions' => $this->normalizeNullableString($contextInput['additional_instructions'] ?? null, 5000),
            ]);
            $context->save();
        }

        AiSetting::query()->firstOrCreate(
            ['scope' => AiSetting::GLOBAL_SCOPE],
            AiSetting::defaultValues()
        );
    }

    private function generateUniqueProviderCode(string $label, ?int $ignoreId = null): string
    {
        $base = Str::slug($label, '-');
        if ($base === '') {
            $base = 'provider';
        }

        $candidate = $base;
        $index = 2;

        while (true) {
            $query = AiProvider::query()->where('code', $candidate);

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if (! $query->exists()) {
                break;
            }

            $candidate = $base.'-'.$index;
            $index++;
        }

        return $candidate;
    }

    private function normalizeContextLength(mixed $value, int $fallback): int
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        $normalized = (int) $value;

        return $normalized >= 0 ? $normalized : $fallback;
    }

    private function normalizeNullableString(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        return mb_substr($trimmed, 0, $maxLength);
    }
}
