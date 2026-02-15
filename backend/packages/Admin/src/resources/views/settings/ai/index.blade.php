@extends('admin::settings.layout')

@section('title', 'Paramètres IA')
@section('page-title', 'Paramètres IA')

@section('settings-content')
    @php
        $providerRows = old('providers');
        if (!is_array($providerRows)) {
            $providerRows = $providers
                ->map(function ($provider) {
                    $config = is_array($provider->config) ? $provider->config : [];

                    return [
                        'id' => $provider->id,
                        'name' => $provider->name,
                        'enabled' => $provider->is_enabled ? '1' : '0',
                        'is_default' => $provider->is_default,
                        'driver' => $config['driver'] ?? $provider->getDriver(),
                        'model' => $config['model'] ?? '',
                        'base_url' => $config['base_url'] ?? '',
                        'organization' => $config['organization'] ?? '',
                        'api_version' => $config['api_version'] ?? '',
                        'api_key' => '',
                        '_has_stored_api_key' => $provider->hasApiKey(),
                        '_api_key_last_four' => $provider->hasApiKey()
                            ? mb_substr((string) ($config['api_key'] ?? ''), -4)
                            : null,
                    ];
                })
                ->values()
                ->all();
        }

        $selectedDefaultProvider = old('default_provider');
        if (!is_string($selectedDefaultProvider) || $selectedDefaultProvider === '') {
            $selectedDefaultProvider = null;
            foreach ($providerRows as $providerIndex => $providerRow) {
                $rowIsDefault = filter_var(data_get($providerRow, 'is_default', false), FILTER_VALIDATE_BOOLEAN);
                $rowProviderId = (int) data_get($providerRow, 'id', 0);
                $providerRecord = $providers->firstWhere('id', $rowProviderId);

                if ($rowIsDefault || ($providerRecord && $providerRecord->is_default)) {
                    $selectedDefaultProvider = (string) $providerIndex;
                    break;
                }
            }
        }

        $providerKeys = array_values(
            array_filter(array_keys($providerRows), static fn ($value): bool => is_numeric($value)),
        );
        $providerNextIndex = empty($providerKeys)
            ? count($providerRows)
            : (max(array_map('intval', $providerKeys)) + 1);

        $contextRows = old('contexts');
        if (!is_array($contextRows)) {
            $contextRows = $contexts
                ->map(function ($context) {
                    return [
                        'id' => $context->id,
                        'scope' => $context->scope,
                        'usage_scopes' => $context->getUsageScopes(),
                        'business_context' => $context->business_context,
                        'seo_objectives' => $context->seo_objectives,
                        'forbidden_terms' => $context->forbidden_terms,
                        'writing_tone' => $context->writing_tone,
                        'content_locale' => $context->content_locale,
                        'title_max_length' => (int) $context->title_max_length,
                        'meta_description_max_length' => (int) $context->meta_description_max_length,
                        'additional_instructions' => $context->additional_instructions,
                    ];
                })
                ->values()
                ->all();
        }

        $hasGlobalContext = false;
        foreach ($contextRows as $contextRow) {
            if (strtolower(trim((string) data_get($contextRow, 'scope', ''))) === \Omersia\Ai\Models\AiSetting::GLOBAL_SCOPE) {
                $hasGlobalContext = true;
                break;
            }
        }

        if (!$hasGlobalContext) {
            array_unshift($contextRows, [
                'scope' => \Omersia\Ai\Models\AiSetting::GLOBAL_SCOPE,
                'usage_scopes' => [\Omersia\Ai\Models\AiSetting::USAGE_ALL],
                'business_context' => $settings->business_context,
                'seo_objectives' => $settings->seo_objectives,
                'forbidden_terms' => $settings->forbidden_terms,
                'writing_tone' => $settings->writing_tone,
                'content_locale' => $settings->content_locale,
                'title_max_length' => $settings->title_max_length,
                'meta_description_max_length' => $settings->meta_description_max_length,
                'additional_instructions' => $settings->additional_instructions,
            ]);
        }

        usort($contextRows, static function ($left, $right): int {
            $leftScope = strtolower(trim((string) data_get($left, 'scope', '')));
            $rightScope = strtolower(trim((string) data_get($right, 'scope', '')));

            if ($leftScope === \Omersia\Ai\Models\AiSetting::GLOBAL_SCOPE && $rightScope !== \Omersia\Ai\Models\AiSetting::GLOBAL_SCOPE) {
                return -1;
            }

            if ($rightScope === \Omersia\Ai\Models\AiSetting::GLOBAL_SCOPE && $leftScope !== \Omersia\Ai\Models\AiSetting::GLOBAL_SCOPE) {
                return 1;
            }

            return strcmp($leftScope, $rightScope);
        });

        $contextKeys = array_values(
            array_filter(array_keys($contextRows), static fn ($value): bool => is_numeric($value)),
        );
        $contextNextIndex = empty($contextKeys)
            ? count($contextRows)
            : (max(array_map('intval', $contextKeys)) + 1);
    @endphp

    <form method="POST" action="{{ route('admin.settings.ai.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-4">
            <div class="space-y-1">
                <div class="text-sm font-semibold text-gray-900">Providers IA</div>
                <p class="text-xs text-gray-500">
                    Ajoute plusieurs providers si besoin. Aucun provider n'est créé automatiquement.
                    Tu peux enregistrer sans provider actif: les actions IA afficheront un rappel de configuration.
                </p>
                
            </div>

            <div id="providers-rows" data-next-index="{{ $providerNextIndex }}" class="space-y-3">
                @foreach ($providerRows as $index => $providerRow)
                    @php
                        $providerId = data_get($providerRow, 'id');
                        $providerNameValue = trim((string) data_get($providerRow, 'name', ''));
                        $enabledRaw = data_get($providerRow, 'enabled', '0');
                        $isEnabled = in_array((string) $enabledRaw, ['1', 'true', 'on'], true);
                        $driverValue = strtolower(trim((string) data_get($providerRow, 'driver', 'openai')));
                        $driverValue = in_array($driverValue, $driverOptions, true) ? $driverValue : 'openai';
                        $modelValue = trim((string) data_get($providerRow, 'model', ''));
                        $baseUrlValue = trim((string) data_get($providerRow, 'base_url', ''));
                        $organizationValue = trim((string) data_get($providerRow, 'organization', ''));
                        $apiVersionValue = trim((string) data_get($providerRow, 'api_version', ''));
                        $hasStoredApiKey = filter_var(data_get($providerRow, '_has_stored_api_key', false), FILTER_VALIDATE_BOOLEAN);
                        $apiKeyLastFour = data_get($providerRow, '_api_key_last_four');
                        $isDefaultProvider = $selectedDefaultProvider !== null && (string) $selectedDefaultProvider === (string) $index;
                        $hasProviderErrors =
                            $errors->has("providers.{$index}.name") ||
                            $errors->has("providers.{$index}.enabled") ||
                            $errors->has("providers.{$index}.driver") ||
                            $errors->has("providers.{$index}.model") ||
                            $errors->has("providers.{$index}.base_url") ||
                            $errors->has("providers.{$index}.organization") ||
                            $errors->has("providers.{$index}.api_version") ||
                            $errors->has("providers.{$index}.api_key");
                        $isOpen = $isDefaultProvider || $isEnabled || $hasProviderErrors;
                    @endphp

                    <details class="group rounded-xl border border-gray-100 bg-gray-50/40 overflow-hidden" data-provider-row
                        {{ $isOpen ? 'open' : '' }} x-data="{ driver: @js($driverValue), model: @js($modelValue) }">
                        <summary class="list-none cursor-pointer px-3 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-800"
                                            x-text="(driver ? driver.toUpperCase() : 'DRIVER') + ' · ' + (model ? model : 'Sans modèle')"></span>
                                        @if ($isDefaultProvider)
                                            <span
                                                class="inline-flex items-center rounded-full bg-neutral-900 px-2 py-0.5 text-xxxs font-semibold text-white">
                                                Par défaut
                                            </span>
                                        @endif
                                        @if ($isEnabled)
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xxxs font-semibold text-emerald-700">
                                                Activé
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xxs text-gray-500">
                                        {{ $providerNameValue !== '' ? $providerNameValue : 'Provider personnalisé' }}
                                    </div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180"
                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </summary>

                        <div class="px-3 pb-3 space-y-3 border-t border-gray-100">
                            @if (is_numeric($providerId))
                                <input type="hidden" name="providers[{{ $index }}][id]" value="{{ $providerId }}">
                            @endif

                            <div class="pt-3 flex items-center justify-between gap-3">
                                <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                    <input type="hidden" name="providers[{{ $index }}][enabled]" value="0">
                                    <input type="checkbox" name="providers[{{ $index }}][enabled]" value="1"
                                        {{ $isEnabled ? 'checked' : '' }} class="h-3 w-3 rounded border-gray-300">
                                    <span>Provider activé</span>
                                </label>

                                <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                    <input type="radio" name="default_provider" value="{{ $index }}"
                                        {{ $isDefaultProvider ? 'checked' : '' }} class="h-3 w-3 border-gray-300">
                                    <span>Provider par défaut</span>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Nom du provider</label>
                                    <input type="text" name="providers[{{ $index }}][name]"
                                        value="{{ $providerNameValue }}"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                        placeholder="Ex: OpenAI principal">
                                    @error("providers.{$index}.name")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Driver</label>
                                    <select name="providers[{{ $index }}][driver]" x-model="driver"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                        @foreach ($driverOptions as $driverOption)
                                            <option value="{{ $driverOption }}"
                                                {{ $driverValue === $driverOption ? 'selected' : '' }}>
                                                {{ strtoupper($driverOption) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("providers.{$index}.driver")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Modèle</label>
                                    <input type="text" name="providers[{{ $index }}][model]"
                                        value="{{ $modelValue }}" x-model="model"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                        placeholder="Ex: gpt-4.1-mini">
                                    @error("providers.{$index}.model")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Base URL (optionnel)</label>
                                    <input type="url" name="providers[{{ $index }}][base_url]"
                                        value="{{ $baseUrlValue }}"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                        placeholder="https://api.openai.com/v1">
                                    @error("providers.{$index}.base_url")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Organization / Project
                                        (optionnel)</label>
                                    <input type="text" name="providers[{{ $index }}][organization]"
                                        value="{{ $organizationValue }}"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                    @error("providers.{$index}.organization")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">API version (optionnel)</label>
                                    <input type="text" name="providers[{{ $index }}][api_version]"
                                        value="{{ $apiVersionValue }}"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                    @error("providers.{$index}.api_version")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1 md:col-span-2" x-data="{ showApiKey: false }">
                                    <div class="flex items-center justify-between gap-2">
                                        <label class="block text-xs font-medium text-gray-700">Clé API</label>
                                        @if ($hasStoredApiKey)
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xxxs font-semibold text-emerald-700">
                                                Clé enregistrée
                                                @if (is_string($apiKeyLastFour) && $apiKeyLastFour !== '')
                                                    (••••{{ $apiKeyLastFour }})
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                    <div
                                        class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                                        <input x-bind:type="showApiKey ? 'text' : 'password'"
                                            name="providers[{{ $index }}][api_key]"
                                            class="flex-1 border-0 px-3 py-2 text-xs focus:ring-0"
                                            placeholder="{{ $hasStoredApiKey ? 'Clé enregistrée (masquée). Saisis une nouvelle clé pour la remplacer.' : 'Renseigne la clé API de ce provider.' }}"
                                            autocomplete="new-password">
                                        <button type="button" @click="showApiKey = !showApiKey"
                                            class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50"
                                            :aria-label="showApiKey ? 'Masquer la clé API' : 'Afficher la clé API'">
                                            <x-lucide-eye x-show="!showApiKey" class="h-3.5 w-3.5" />
                                            <x-lucide-eye-off x-show="showApiKey" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                    @error("providers.{$index}.api_key")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="button" data-remove-provider-row
                                    class="inline-flex items-center gap-1 rounded-md border border-gray-200 px-2.5 py-1.5 text-xxs text-gray-600 hover:bg-gray-50">
                                    Retirer du formulaire
                                </button>
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>

            <div>
                <button type="button" onclick="window.aiSettingsAddProvider?.()"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50">
                    Ajouter un provider
                </button>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-4">
            <div class="space-y-1">
                <div class="text-sm font-semibold text-gray-900">Contextes IA</div>
                <p class="text-xs text-gray-500">
                    Définis plusieurs contextes (global, seo, contenu, etc.) et choisis précisément où les appliquer.
                </p>
                @error('contexts')
                    <p class="text-xxs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div id="contexts-rows" data-next-index="{{ $contextNextIndex }}" class="space-y-3">
                @foreach ($contextRows as $index => $contextRow)
                    @php
                        $contextId = data_get($contextRow, 'id');
                        $scopeValue = strtolower(trim((string) data_get($contextRow, 'scope', '')));
                        $isGlobalScope = $scopeValue === \Omersia\Ai\Models\AiSetting::GLOBAL_SCOPE;
                        $usageValues = data_get($contextRow, 'usage_scopes', []);
                        if (!is_array($usageValues)) {
                            $usageValues = [];
                        }

                        $contextBusinessValue = data_get($contextRow, 'business_context', '');
                        $contextSeoObjectivesValue = data_get($contextRow, 'seo_objectives', '');
                        $contextForbiddenTermsValue = data_get($contextRow, 'forbidden_terms', '');
                        $contextWritingToneValue = data_get($contextRow, 'writing_tone', $isGlobalScope ? 'professionnel' : '');
                        $contextLocaleValue = data_get($contextRow, 'content_locale', $isGlobalScope ? 'fr' : '');
                        $contextTitleLengthValue = (string) data_get(
                            $contextRow,
                            'title_max_length',
                            $isGlobalScope ? '70' : '0',
                        );
                        $contextMetaLengthValue = (string) data_get(
                            $contextRow,
                            'meta_description_max_length',
                            $isGlobalScope ? '160' : '0',
                        );
                        $contextAdditionalInstructionsValue = data_get($contextRow, 'additional_instructions', '');

                        $hasContextErrors =
                            $errors->has("contexts.{$index}.scope") ||
                            $errors->has("contexts.{$index}.usage_scopes") ||
                            $errors->has("contexts.{$index}.business_context") ||
                            $errors->has("contexts.{$index}.seo_objectives") ||
                            $errors->has("contexts.{$index}.forbidden_terms") ||
                            $errors->has("contexts.{$index}.writing_tone") ||
                            $errors->has("contexts.{$index}.content_locale") ||
                            $errors->has("contexts.{$index}.title_max_length") ||
                            $errors->has("contexts.{$index}.meta_description_max_length") ||
                            $errors->has("contexts.{$index}.additional_instructions");
                        $isContextOpen = $isGlobalScope || $hasContextErrors;
                    @endphp

                    <details class="group rounded-xl border border-gray-100 bg-gray-50/40 overflow-hidden" data-context-row
                        {{ $isContextOpen ? 'open' : '' }} x-data="{ scope: @js($scopeValue) }">
                        <summary class="list-none cursor-pointer px-3 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-gray-800"
                                        x-text="scope ? scope : 'nouveau-contexte'"></span>
                                    @if ($isGlobalScope)
                                        <span
                                            class="inline-flex items-center rounded-full bg-neutral-900 px-2 py-0.5 text-xxxs font-semibold text-white">
                                            Global
                                        </span>
                                    @endif
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180"
                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </summary>

                        <div class="px-3 pb-3 space-y-3 border-t border-gray-100">
                            @if (is_numeric($contextId))
                                <input type="hidden" name="contexts[{{ $index }}][id]" value="{{ $contextId }}">
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-3">
                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Scope</label>
                                    <input type="text" name="contexts[{{ $index }}][scope]"
                                        value="{{ $scopeValue }}" x-model="scope"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                        placeholder="Ex: seo, contenu, categorie-seo"
                                        {{ $isGlobalScope ? 'readonly' : '' }}>
                                    @error("contexts.{$index}.scope")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                    @if ($isGlobalScope)
                                        <p class="text-xxs text-gray-500">Le contexte global est obligatoire.</p>
                                    @endif
                                </div>

                                <div class="space-y-1 md:col-span-1">
                                    <label class="block text-xs font-medium text-gray-700">Usages</label>
                                    <div class="grid grid-cols-1 gap-1.5 rounded-lg border border-gray-200 bg-white p-2">
                                        @foreach ($usageOptions as $usageValue => $usageLabel)
                                            @php
                                                $checked = in_array($usageValue, $usageValues, true);
                                            @endphp
                                            <label class="inline-flex items-start gap-2 text-xxs text-gray-700">
                                                <input type="checkbox" name="contexts[{{ $index }}][usage_scopes][]"
                                                    value="{{ $usageValue }}" {{ $checked ? 'checked' : '' }}
                                                    class="mt-0.5 h-3 w-3 rounded border-gray-300"
                                                    {{ $isGlobalScope ? 'disabled' : '' }}>
                                                <span>{{ $usageLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($isGlobalScope)
                                        <p class="text-xxs text-gray-500">Le contexte global s'applique partout.</p>
                                        <input type="hidden" name="contexts[{{ $index }}][usage_scopes][]"
                                            value="{{ \Omersia\Ai\Models\AiSetting::USAGE_ALL }}">
                                    @endif
                                    @error("contexts.{$index}.usage_scopes")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1 md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-700">Contexte business / marque</label>
                                    <textarea name="contexts[{{ $index }}][business_context]" rows="3"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y">{{ $contextBusinessValue }}</textarea>
                                    @error("contexts.{$index}.business_context")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Objectifs SEO</label>
                                    <textarea name="contexts[{{ $index }}][seo_objectives]" rows="3"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y">{{ $contextSeoObjectivesValue }}</textarea>
                                    @error("contexts.{$index}.seo_objectives")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Termes interdits / à éviter</label>
                                    <textarea name="contexts[{{ $index }}][forbidden_terms]" rows="3"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y">{{ $contextForbiddenTermsValue }}</textarea>
                                    @error("contexts.{$index}.forbidden_terms")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Ton rédactionnel</label>
                                    <input type="text" name="contexts[{{ $index }}][writing_tone]"
                                        value="{{ $contextWritingToneValue }}"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                        placeholder="{{ $isGlobalScope ? 'Ex: professionnel' : 'Optionnel (override)' }}">
                                    @error("contexts.{$index}.writing_tone")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Langue de génération</label>
                                    <input type="text" name="contexts[{{ $index }}][content_locale]"
                                        value="{{ $contextLocaleValue }}"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                        placeholder="{{ $isGlobalScope ? 'fr' : 'Optionnel (override)' }}">
                                    @error("contexts.{$index}.content_locale")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Longueur max titre SEO</label>
                                    <input type="number" name="contexts[{{ $index }}][title_max_length]"
                                        value="{{ $contextTitleLengthValue }}" min="0" max="255"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                    @error("contexts.{$index}.title_max_length")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-gray-700">Longueur max meta description</label>
                                    <input type="number"
                                        name="contexts[{{ $index }}][meta_description_max_length]"
                                        value="{{ $contextMetaLengthValue }}" min="0" max="500"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                    @error("contexts.{$index}.meta_description_max_length")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1 md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-700">Instructions complémentaires</label>
                                    <textarea name="contexts[{{ $index }}][additional_instructions]" rows="3"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y">{{ $contextAdditionalInstructionsValue }}</textarea>
                                    @error("contexts.{$index}.additional_instructions")
                                        <p class="text-xxs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            @if (!$isGlobalScope)
                                <div class="flex justify-end">
                                    <button type="button" data-remove-context-row
                                        class="inline-flex items-center gap-1 rounded-md border border-gray-200 px-2.5 py-1.5 text-xxs text-gray-600 hover:bg-gray-50">
                                        Retirer du formulaire
                                    </button>
                                </div>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>

            <div>
                <button type="button" onclick="window.aiSettingsAddContext?.()"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50">
                    Ajouter un contexte
                </button>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs hover:bg-black transition">
                Enregistrer la configuration IA
            </button>
        </div>
    </form>

    <template id="provider-row-template">
        <details class="group rounded-xl border border-gray-100 bg-gray-50/40 overflow-hidden" data-provider-row open
            x-data="{ driver: 'openai', model: '' }">
            <summary class="list-none cursor-pointer px-3 py-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-gray-800"
                                x-text="(driver ? driver.toUpperCase() : 'DRIVER') + ' · ' + (model ? model : 'Sans modèle')"></span>
                        </div>
                        <div class="text-xxs text-gray-500">Nouveau provider</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
            </summary>

            <div class="px-3 pb-3 space-y-3 border-t border-gray-100">
                <div class="pt-3 flex items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                        <input type="hidden" name="providers[__INDEX__][enabled]" value="0">
                        <input type="checkbox" name="providers[__INDEX__][enabled]" value="1"
                            class="h-3 w-3 rounded border-gray-300">
                        <span>Provider activé</span>
                    </label>

                    <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                        <input type="radio" name="default_provider" value="__INDEX__" class="h-3 w-3 border-gray-300">
                        <span>Provider par défaut</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Nom du provider</label>
                        <input type="text" name="providers[__INDEX__][name]"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="Ex: OpenAI principal">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Driver</label>
                        <select name="providers[__INDEX__][driver]" x-model="driver"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                            @foreach ($driverOptions as $driverOption)
                                <option value="{{ $driverOption }}">{{ strtoupper($driverOption) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Modèle</label>
                        <input type="text" name="providers[__INDEX__][model]" x-model="model"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="Ex: gpt-4.1-mini">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Base URL (optionnel)</label>
                        <input type="url" name="providers[__INDEX__][base_url]"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="https://api.openai.com/v1">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Organization / Project (optionnel)</label>
                        <input type="text" name="providers[__INDEX__][organization]"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">API version (optionnel)</label>
                        <input type="text" name="providers[__INDEX__][api_version]"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                    </div>

                    <div class="space-y-1 md:col-span-2" x-data="{ showApiKey: false }">
                        <label class="block text-xs font-medium text-gray-700">Clé API</label>
                        <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                            <input x-bind:type="showApiKey ? 'text' : 'password'" name="providers[__INDEX__][api_key]"
                                class="flex-1 border-0 px-3 py-2 text-xs focus:ring-0"
                                placeholder="Renseigne la clé API de ce provider." autocomplete="new-password">
                            <button type="button" @click="showApiKey = !showApiKey"
                                class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50"
                                :aria-label="showApiKey ? 'Masquer la clé API' : 'Afficher la clé API'">
                                <x-lucide-eye x-show="!showApiKey" class="h-3.5 w-3.5" />
                                <x-lucide-eye-off x-show="showApiKey" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="button" data-remove-provider-row
                        class="inline-flex items-center gap-1 rounded-md border border-gray-200 px-2.5 py-1.5 text-xxs text-gray-600 hover:bg-gray-50">
                        Retirer du formulaire
                    </button>
                </div>
            </div>
        </details>
    </template>

    <template id="context-row-template">
        <details class="group rounded-xl border border-gray-100 bg-gray-50/40 overflow-hidden" data-context-row open
            x-data="{ scope: '' }">
            <summary class="list-none cursor-pointer px-3 py-3">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-semibold text-gray-800" x-text="scope ? scope : 'nouveau-contexte'"></span>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
            </summary>

            <div class="px-3 pb-3 space-y-3 border-t border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Scope</label>
                        <input type="text" name="contexts[__INDEX__][scope]" x-model="scope"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="Ex: seo, contenu, categorie-seo">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Usages</label>
                        <div class="grid grid-cols-1 gap-1.5 rounded-lg border border-gray-200 bg-white p-2">
                            @foreach ($usageOptions as $usageValue => $usageLabel)
                                <label class="inline-flex items-start gap-2 text-xxs text-gray-700">
                                    <input type="checkbox" name="contexts[__INDEX__][usage_scopes][]"
                                        value="{{ $usageValue }}" class="mt-0.5 h-3 w-3 rounded border-gray-300">
                                    <span>{{ $usageLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-1 md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700">Contexte business / marque</label>
                        <textarea name="contexts[__INDEX__][business_context]" rows="3"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Objectifs SEO</label>
                        <textarea name="contexts[__INDEX__][seo_objectives]" rows="3"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Termes interdits / à éviter</label>
                        <textarea name="contexts[__INDEX__][forbidden_terms]" rows="3"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Ton rédactionnel</label>
                        <input type="text" name="contexts[__INDEX__][writing_tone]"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="Optionnel (override)">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Langue de génération</label>
                        <input type="text" name="contexts[__INDEX__][content_locale]"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="Optionnel (override)">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Longueur max titre SEO</label>
                        <input type="number" name="contexts[__INDEX__][title_max_length]" value="0" min="0"
                            max="255" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">Longueur max meta description</label>
                        <input type="number" name="contexts[__INDEX__][meta_description_max_length]" value="0"
                            min="0" max="500" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                    </div>

                    <div class="space-y-1 md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700">Instructions complémentaires</label>
                        <textarea name="contexts[__INDEX__][additional_instructions]" rows="3"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y"></textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="button" data-remove-context-row
                        class="inline-flex items-center gap-1 rounded-md border border-gray-200 px-2.5 py-1.5 text-xxs text-gray-600 hover:bg-gray-50">
                        Retirer du formulaire
                    </button>
                </div>
            </div>
        </details>
    </template>

    <script>
        (() => {
            const providerContainer = document.getElementById('providers-rows');
            const providerTemplate = document.getElementById('provider-row-template');
            const contextContainer = document.getElementById('contexts-rows');
            const contextTemplate = document.getElementById('context-row-template');

            const appendFromTemplate = (container, template) => {
                if (!container || !template) {
                    return null;
                }

                const nextIndex = Number(container.dataset.nextIndex || '0');
                const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();

                const element = wrapper.firstElementChild;
                if (!element) {
                    return null;
                }

                container.appendChild(element);
                container.dataset.nextIndex = String(nextIndex + 1);

                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    window.Alpine.initTree(element);
                }

                if (element instanceof HTMLDetailsElement) {
                    element.open = true;
                }

                return element;
            };

            window.aiSettingsAddProvider = () => {
                appendFromTemplate(providerContainer, providerTemplate);
            };

            window.aiSettingsAddContext = () => {
                appendFromTemplate(contextContainer, contextTemplate);
            };

            document.addEventListener('click', (event) => {
                const providerButton = event.target instanceof Element
                    ? event.target.closest('[data-remove-provider-row]')
                    : null;
                if (providerButton) {
                    const providerRow = providerButton.closest('[data-provider-row]');
                    if (providerRow) {
                        providerRow.remove();
                    }
                }

                const contextButton = event.target instanceof Element
                    ? event.target.closest('[data-remove-context-row]')
                    : null;
                if (contextButton) {
                    const contextRow = contextButton.closest('[data-context-row]');
                    if (contextRow) {
                        contextRow.remove();
                    }
                }
            });
        })();
    </script>
@endsection
