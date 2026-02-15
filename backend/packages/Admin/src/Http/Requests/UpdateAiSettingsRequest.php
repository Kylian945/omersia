<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Omersia\Ai\Models\AiProvider;
use Omersia\Ai\Models\AiSetting;

final class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.update');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'default_provider' => ['nullable', 'string', 'max:80'],

            'business_context' => ['nullable', 'string', 'max:5000'],
            'seo_objectives' => ['nullable', 'string', 'max:5000'],
            'forbidden_terms' => ['nullable', 'string', 'max:2000'],
            'writing_tone' => ['nullable', 'string', 'max:80'],
            'content_locale' => ['nullable', 'string', 'max:10'],
            'title_max_length' => ['nullable', 'integer', 'min:10', 'max:255'],
            'meta_description_max_length' => ['nullable', 'integer', 'min:50', 'max:500'],
            'additional_instructions' => ['nullable', 'string', 'max:5000'],

            'providers' => ['nullable', 'array'],
            'providers.*.id' => ['nullable', 'integer', 'exists:ai_providers,id'],
            'providers.*.name' => ['nullable', 'string', 'max:120'],
            'providers.*.enabled' => ['nullable', 'boolean'],
            'providers.*.driver' => ['nullable', 'string', Rule::in(AiProvider::getSupportedDrivers())],
            'providers.*.model' => ['nullable', 'string', 'max:120'],
            'providers.*.base_url' => ['nullable', 'url', 'max:255'],
            'providers.*.organization' => ['nullable', 'string', 'max:255'],
            'providers.*.api_version' => ['nullable', 'string', 'max:80'],
            'providers.*.api_key' => ['nullable', 'string', 'max:2048'],

            'contexts' => ['required', 'array', 'min:1'],
            'contexts.*.id' => ['nullable', 'integer', 'exists:ai_settings,id'],
            'contexts.*.scope' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_-]+$/'],
            'contexts.*.usage_scopes' => ['nullable', 'array'],
            'contexts.*.usage_scopes.*' => ['nullable', 'string', Rule::in(array_keys(AiSetting::getUsageOptions()))],
            'contexts.*.business_context' => ['nullable', 'string', 'max:5000'],
            'contexts.*.seo_objectives' => ['nullable', 'string', 'max:5000'],
            'contexts.*.forbidden_terms' => ['nullable', 'string', 'max:2000'],
            'contexts.*.writing_tone' => ['nullable', 'string', 'max:80'],
            'contexts.*.content_locale' => ['nullable', 'string', 'max:10'],
            'contexts.*.title_max_length' => ['nullable', 'integer', 'min:0', 'max:255'],
            'contexts.*.meta_description_max_length' => ['nullable', 'integer', 'min:0', 'max:500'],
            'contexts.*.additional_instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $providers = $this->input('providers', []);

            if (! is_array($providers)) {
                $providers = [];
            }

            $hasEnabledProvider = false;

            foreach ($providers as $rowKey => $providerData) {
                if (! is_array($providerData)) {
                    continue;
                }

                $isEnabled = filter_var($providerData['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $driver = trim((string) ($providerData['driver'] ?? ''));
                $model = trim((string) ($providerData['model'] ?? ''));
                $baseUrl = trim((string) ($providerData['base_url'] ?? ''));

                if ($isEnabled) {
                    $hasEnabledProvider = true;
                }

                if ($isEnabled && $driver === '') {
                    $validator->errors()->add("providers.{$rowKey}.driver", 'Le driver est obligatoire quand le provider est actif.');
                }

                if ($isEnabled && $model === '') {
                    $validator->errors()->add("providers.{$rowKey}.model", 'Le modèle est obligatoire quand le provider est actif.');
                }

                if ($baseUrl !== '' && ! str_starts_with(strtolower($baseUrl), 'https://')) {
                    $validator->errors()->add("providers.{$rowKey}.base_url", 'La base URL doit utiliser HTTPS.');
                }
            }

            $defaultProvider = $this->input('default_provider');
            if (! is_string($defaultProvider) || $defaultProvider === '') {
                $defaultProvider = null;
            }

            if ($defaultProvider !== null && ! array_key_exists($defaultProvider, $providers)) {
                $validator->errors()->add('default_provider', 'Le provider par défaut sélectionné est introuvable.');
            }

            if ($defaultProvider !== null) {
                $isDefaultEnabled = filter_var(
                    data_get($providers, "{$defaultProvider}.enabled", false),
                    FILTER_VALIDATE_BOOLEAN
                );

                if (! $isDefaultEnabled && $hasEnabledProvider) {
                    $validator->errors()->add('default_provider', 'Le provider par défaut doit être activé.');
                }
            }

            $contexts = $this->input('contexts', []);
            if (! is_array($contexts) || $contexts === []) {
                return;
            }

            $seenScopes = [];
            $hasGlobalScope = false;

            foreach ($contexts as $rowKey => $contextData) {
                if (! is_array($contextData)) {
                    continue;
                }

                $scope = strtolower(trim((string) ($contextData['scope'] ?? '')));
                if ($scope === '') {
                    continue;
                }

                if (in_array($scope, $seenScopes, true)) {
                    $validator->errors()->add("contexts.{$rowKey}.scope", 'Le scope de contexte doit être unique.');
                }

                $seenScopes[] = $scope;
                if ($scope === AiSetting::GLOBAL_SCOPE) {
                    $hasGlobalScope = true;
                }

                $usageScopes = AiSetting::normalizeUsageScopesInput($contextData['usage_scopes'] ?? []);
                if ($scope !== AiSetting::GLOBAL_SCOPE && $usageScopes === []) {
                    $validator->errors()->add(
                        "contexts.{$rowKey}.usage_scopes",
                        'Sélectionne au moins un usage pour ce contexte.'
                    );
                }

                $titleMaxLength = is_numeric($contextData['title_max_length'] ?? null)
                    ? (int) $contextData['title_max_length']
                    : 0;
                $metaDescriptionMaxLength = is_numeric($contextData['meta_description_max_length'] ?? null)
                    ? (int) $contextData['meta_description_max_length']
                    : 0;

                if ($scope === AiSetting::GLOBAL_SCOPE && $titleMaxLength < 10) {
                    $validator->errors()->add(
                        "contexts.{$rowKey}.title_max_length",
                        'La longueur maximale du titre SEO (global) doit être d\'au moins 10 caractères.'
                    );
                }

                if ($scope === AiSetting::GLOBAL_SCOPE && $metaDescriptionMaxLength < 50) {
                    $validator->errors()->add(
                        "contexts.{$rowKey}.meta_description_max_length",
                        'La longueur maximale de la meta description (globale) doit être d\'au moins 50 caractères.'
                    );
                }

                if ($scope !== AiSetting::GLOBAL_SCOPE && $titleMaxLength > 0 && $titleMaxLength < 10) {
                    $validator->errors()->add(
                        "contexts.{$rowKey}.title_max_length",
                        'Si renseignée, la longueur du titre SEO doit être d\'au moins 10 caractères.'
                    );
                }

                if ($scope !== AiSetting::GLOBAL_SCOPE && $metaDescriptionMaxLength > 0 && $metaDescriptionMaxLength < 50) {
                    $validator->errors()->add(
                        "contexts.{$rowKey}.meta_description_max_length",
                        'Si renseignée, la longueur de la meta description doit être d\'au moins 50 caractères.'
                    );
                }
            }

            if (! $hasGlobalScope) {
                $validator->errors()->add('contexts', 'Le contexte global est obligatoire.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title_max_length.integer' => 'La longueur maximale du titre SEO doit être un nombre entier.',
            'title_max_length.min' => 'La longueur maximale du titre SEO doit être d\'au moins 10 caractères.',
            'title_max_length.max' => 'La longueur maximale du titre SEO ne peut pas dépasser 255 caractères.',
            'meta_description_max_length.integer' => 'La longueur maximale de la meta description doit être un nombre entier.',
            'meta_description_max_length.min' => 'La longueur maximale de la meta description doit être d\'au moins 50 caractères.',
            'meta_description_max_length.max' => 'La longueur maximale de la meta description ne peut pas dépasser 500 caractères.',
            'providers.array' => 'La configuration des providers IA doit être un tableau.',
            'providers.*.base_url.url' => 'L\'URL de base du provider doit être une URL valide.',
            'providers.*.driver.in' => 'Le driver IA sélectionné est invalide.',
            'providers.*.api_key.max' => 'La clé API est trop longue.',
            'contexts.required' => 'La configuration des contextes IA est obligatoire.',
            'contexts.array' => 'La configuration des contextes IA doit être un tableau.',
            'contexts.*.scope.required' => 'Le scope du contexte est obligatoire.',
            'contexts.*.scope.regex' => 'Le scope du contexte ne doit contenir que des lettres minuscules, chiffres, "_" ou "-".',
        ];
    }
}
