@extends('admin::layout')

@section('title', 'Personnaliser le thème')
@section('page-title', 'Personnaliser le thème')

@push('styles')
    @vite('packages/Admin/src/resources/css/apparence/theme-customize.css')
@endpush

@section('content')
    @php
        $groupLabels = [
            'colors' => 'Couleurs',
            'backgrounds' => 'Fonds',
            'texts' => 'Textes',
            'borders' => 'Bordures',
            'states' => 'États & Badges',
            'typography' => 'Typographie',
            'layout' => 'Mise en page',
            'header' => 'Header',
            'buttons' => 'Boutons',
            'cart' => 'Panier',
            'products' => 'Produits',
        ];

        $groupIcons = [
            'colors' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
            'backgrounds' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
            'texts' => 'M4 6h16M4 12h16M4 18h7',
            'borders' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'states' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
            'typography' => 'M4 5h16M4 12h16M4 19h16',
            'layout' => 'M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3z',
            'header' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z',
            'buttons' => 'M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z',
            'cart' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            'products' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        ];

        // Exclude backgrounds, texts, borders, states as they are sub-sections of colors
        $groups = array_filter(array_keys($config), function($group) {
            return !in_array($group, ['backgrounds', 'texts', 'borders', 'states']);
        });

        // Default active tab (colors if available, otherwise first group)
        $defaultTab = in_array('colors', $groups) ? 'colors' : reset($groups);
    @endphp

    <div x-data="{ activeTab: '{{ $defaultTab }}' }">
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('admin.apparence.theme.index') }}"
                        class="text-xs text-gray-500 hover:text-gray-700">
                        ← Retour aux thèmes
                    </a>
                </div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $theme->name }}</h1>
                <p class="text-xs text-gray-500 mt-0.5">Personnalisez l'apparence de votre boutique</p>
            </div>
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.apparence.theme.customize.reset', $theme) }}" method="POST"
                    onsubmit="return confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres ?')">
                    @csrf
                    <button type="submit"
                        class="px-4 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-600 hover:bg-gray-50">
                        Réinitialiser
                    </button>
                </form>
            </div>
        </div>

        <form action="{{ route('admin.apparence.theme.customize.update', $theme) }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Sidebar avec les onglets (généré dynamiquement) --}}
                <div class="lg:col-span-1 space-y-2 self-start sticky top-20">
                    @foreach($groups as $group)
                        @php
                            $label = $groupLabels[$group] ?? ucfirst($group);
                            $icon = $groupIcons[$group] ?? $groupIcons['colors']; // Fallback icon
                            $description = match($group) {
                                'colors' => 'Palette de couleurs',
                                'backgrounds' => 'Arrière-plans',
                                'texts' => 'Couleurs de texte',
                                'borders' => 'Bordures et séparations',
                                'states' => 'Badges et états',
                                'typography' => 'Polices et tailles',
                                'layout' => 'Structure et styles',
                                'header' => 'En-tête du site',
                                'buttons' => 'Style des boutons CTA',
                                'cart' => 'Configuration du panier',
                                'products' => 'Affichage des produits',
                                default => ''
                            };
                        @endphp

                        <button
                            type="button"
                            @click="activeTab = '{{ $group }}'"
                            :class="activeTab === '{{ $group }}' ? 'bg-white border-gray-300 shadow-sm' : 'bg-white border-transparent hover:bg-white'"
                            class="w-full text-left px-4 py-3 rounded-xl border text-xs font-medium text-gray-900 transition"
                        >
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                                </svg>
                                <span>{{ $label }}</span>
                            </div>
                            @if($description)
                                <p class="text-xxxs text-gray-500 mt-0.5">{{ $description }}</p>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Panneau de personnalisation --}}
                <div class="lg:col-span-2">
                    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-6">
                        {{-- Colors Section --}}
                        <div x-show="activeTab === 'colors'" class="space-y-6">
                            <h3 class="text-sm font-semibold text-gray-900">Palette de couleurs</h3>

                            {{-- Couleurs principales --}}
                            <div class="space-y-3">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Couleurs principales</h4>
                                @foreach ($config['colors'] as $key => $setting)
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <input type="color" name="settings[{{ $key }}]"
                                                value="{{ $currentSettings['colors'][$key] ?? $setting['default'] }}"
                                                class="w-8 h-8 rounded-full border border-gray-200 cursor-pointer appearance-none"
                                                style="padding: 0;"
                                                id="color-{{ $key }}">
                                        </div>
                                        <div class="flex-1">
                                            <label for="color-{{ $key }}" class="block text-xs font-medium text-gray-700 cursor-pointer">
                                                {{ $setting['label'] }}
                                            </label>
                                            @if (isset($setting['description']))
                                                <p class="text-xxxs text-gray-500 mt-0.5">{{ $setting['description'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Fonds --}}
                            <div class="space-y-3 pt-3 border-t border-gray-100">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Fonds</h4>
                                @foreach ($config['backgrounds'] as $key => $setting)
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <input type="color" name="settings[{{ $key }}]"
                                                value="{{ $currentSettings['backgrounds'][$key] ?? $setting['default'] }}"
                                                class="w-8 h-8 rounded-full border border-gray-200 cursor-pointer appearance-none"
                                                style="padding: 0;"
                                                id="bg-{{ $key }}">
                                        </div>
                                        <div class="flex-1">
                                            <label for="bg-{{ $key }}" class="block text-xs font-medium text-gray-700 cursor-pointer">
                                                {{ $setting['label'] }}
                                            </label>
                                            @if (isset($setting['description']))
                                                <p class="text-xxxs text-gray-500 mt-0.5">{{ $setting['description'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Textes --}}
                            <div class="space-y-3 pt-3 border-t border-gray-100">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Textes</h4>
                                @foreach ($config['texts'] as $key => $setting)
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <input type="color" name="settings[{{ $key }}]"
                                                value="{{ $currentSettings['texts'][$key] ?? $setting['default'] }}"
                                                class="w-8 h-8 rounded-full border border-gray-200 cursor-pointer appearance-none"
                                                style="padding: 0;"
                                                id="text-{{ $key }}">
                                        </div>
                                        <div class="flex-1">
                                            <label for="text-{{ $key }}" class="block text-xs font-medium text-gray-700 cursor-pointer">
                                                {{ $setting['label'] }}
                                            </label>
                                            @if (isset($setting['description']))
                                                <p class="text-xxxs text-gray-500 mt-0.5">{{ $setting['description'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Bordures --}}
                            <div class="space-y-3 pt-3 border-t border-gray-100">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Bordures</h4>
                                @foreach ($config['borders'] as $key => $setting)
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <input type="color" name="settings[{{ $key }}]"
                                                value="{{ $currentSettings['borders'][$key] ?? $setting['default'] }}"
                                                class="w-8 h-8 rounded-full border border-gray-200 cursor-pointer appearance-none"
                                                style="padding: 0;"
                                                id="border-{{ $key }}">
                                        </div>
                                        <div class="flex-1">
                                            <label for="border-{{ $key }}" class="block text-xs font-medium text-gray-700 cursor-pointer">
                                                {{ $setting['label'] }}
                                            </label>
                                            @if (isset($setting['description']))
                                                <p class="text-xxxs text-gray-500 mt-0.5">{{ $setting['description'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- États & Badges --}}
                            <div class="space-y-3 pt-3 border-t border-gray-100">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">États & Badges</h4>
                                @foreach ($config['states'] as $key => $setting)
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <input type="color" name="settings[{{ $key }}]"
                                                value="{{ $currentSettings['states'][$key] ?? $setting['default'] }}"
                                                class="w-8 h-8 rounded-full border border-gray-200 cursor-pointer appearance-none"
                                                style="padding: 0;"
                                                id="state-{{ $key }}">
                                        </div>
                                        <div class="flex-1">
                                            <label for="state-{{ $key }}" class="block text-xs font-medium text-gray-700 cursor-pointer">
                                                {{ $setting['label'] }}
                                            </label>
                                            @if (isset($setting['description']))
                                                <p class="text-xxxs text-gray-500 mt-0.5">{{ $setting['description'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Typography Section --}}
                        <div x-show="activeTab === 'typography'" class="space-y-6">
                            <h3 class="text-sm font-semibold text-gray-900">Typographie</h3>

                            {{-- Polices --}}
                            <div class="space-y-3">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Polices</h4>
                                @foreach (['heading_font', 'body_font', 'heading_weight'] as $key)
                                    @if (isset($config['typography'][$key]))
                                        @php $setting = $config['typography'][$key]; @endphp
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                {{ $setting['label'] }}
                                            </label>
                                            @if (isset($setting['description']))
                                                <p class="text-xxxs text-gray-500 mb-1">{{ $setting['description'] }}</p>
                                            @endif
                                            <select name="settings[{{ $key }}]"
                                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                                                @foreach ($setting['options'] as $optionKey => $optionLabel)
                                                    @php
                                                        $value = is_numeric($optionKey) ? $optionLabel : $optionKey;
                                                        $label = is_numeric($optionKey) ? $optionLabel : $optionLabel;
                                                        $currentValue = $currentSettings['typography'][$key] ?? $setting['default'];
                                                    @endphp
                                                    <option value="{{ $value }}" {{ $currentValue == $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Tailles des titres --}}
                            <div class="space-y-3 pt-3 border-t border-gray-100">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tailles des titres</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach (['h1_size', 'h2_size', 'h3_size', 'h4_size', 'h5_size', 'h6_size'] as $key)
                                        @if (isset($config['typography'][$key]))
                                            @php $setting = $config['typography'][$key]; @endphp
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                                    {{ $setting['label'] }}
                                                </label>
                                                @if (isset($setting['description']))
                                                    <p class="text-xxxs text-gray-500 mb-1">{{ $setting['description'] }}</p>
                                                @endif
                                                <div class="flex items-center gap-2">
                                                    <input type="number" name="settings[{{ $key }}]"
                                                        value="{{ $currentSettings['typography'][$key] ?? $setting['default'] }}"
                                                        min="{{ $setting['min'] ?? '' }}" max="{{ $setting['max'] ?? '' }}"
                                                        class="flex-1 rounded-xl border border-gray-200 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                                                    <span class="text-xs text-gray-400">px</span>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            {{-- Taille du texte --}}
                            <div class="space-y-3 pt-3 border-t border-gray-100">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Texte de base</h4>
                                @if (isset($config['typography']['body_size']))
                                    @php $setting = $config['typography']['body_size']; @endphp
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">
                                            {{ $setting['label'] }}
                                        </label>
                                        @if (isset($setting['description']))
                                            <p class="text-xxxs text-gray-500 mb-1">{{ $setting['description'] }}</p>
                                        @endif
                                        <div class="flex items-center gap-2">
                                            <input type="number" name="settings[body_size]"
                                                value="{{ $currentSettings['typography']['body_size'] ?? $setting['default'] }}"
                                                min="{{ $setting['min'] ?? '' }}" max="{{ $setting['max'] ?? '' }}"
                                                class="w-32 rounded-xl border border-gray-200 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                                            <span class="text-xs text-gray-400">px</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Layout Section --}}
                        <div x-show="activeTab === 'layout'" class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Mise en page</h3>

                            @foreach ($config['layout'] as $key => $setting)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        {{ $setting['label'] }}
                                    </label>

                                    @if ($setting['type'] === 'select')
                                        <select name="settings[{{ $key }}]"
                                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                                            @foreach ($setting['options'] as $optionKey => $optionLabel)
                                                @php
                                                    $value = is_numeric($optionKey) ? $optionLabel : $optionKey;
                                                    $label = is_numeric($optionKey) ? $optionLabel : $optionLabel;
                                                    $currentValue = $currentSettings['layout'][$key] ?? $setting['default'];
                                                @endphp
                                                <option value="{{ $value }}" {{ $currentValue == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Header Section --}}
                        <div x-show="activeTab === 'header'" class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Header</h3>

                            @foreach ($config['header'] as $key => $setting)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        {{ $setting['label'] }}
                                    </label>

                                    @if ($setting['type'] === 'select')
                                        <select name="settings[{{ $key }}]"
                                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                                            @foreach ($setting['options'] as $optionKey => $optionLabel)
                                                @php
                                                    $value = is_numeric($optionKey) ? $optionLabel : $optionKey;
                                                    $label = is_numeric($optionKey) ? $optionLabel : $optionLabel;
                                                    $currentValue = $currentSettings['header'][$key] ?? $setting['default'];
                                                @endphp
                                                <option value="{{ $value }}" {{ $currentValue == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Buttons Section --}}
                        <div x-show="activeTab === 'buttons'" class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Boutons</h3>

                            @foreach ($config['buttons'] as $key => $setting)
                                <div>
                                    @if ($setting['type'] === 'select')
                                        <label class="block text-xs font-medium text-gray-700 mb-1">
                                            {{ $setting['label'] }}
                                        </label>
                                        <select name="settings[{{ $key }}]"
                                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                                            @foreach ($setting['options'] as $optionKey => $optionLabel)
                                                @php
                                                    $value = is_numeric($optionKey) ? $optionLabel : $optionKey;
                                                    $label = is_numeric($optionKey) ? $optionLabel : $optionLabel;
                                                    $currentValue = $currentSettings['buttons'][$key] ?? $setting['default'];
                                                @endphp
                                                <option value="{{ $value }}" {{ $currentValue == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($setting['type'] === 'color')
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <input type="color" name="settings[{{ $key }}]"
                                                    value="{{ $currentSettings['buttons'][$key] ?? $setting['default'] }}"
                                                    class="w-8 h-8 rounded-full border border-gray-200 cursor-pointer appearance-none"
                                                    style="padding: 0;"
                                                    id="button-{{ $key }}">
                                            </div>
                                            <div class="flex-1">
                                                <label for="button-{{ $key }}" class="block text-xs font-medium text-gray-700 cursor-pointer">
                                                    {{ $setting['label'] }}
                                                </label>
                                                @if (isset($setting['description']))
                                                    <p class="text-xxxs text-gray-500 mt-0.5">{{ $setting['description'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Cart Section --}}
                        <div x-show="activeTab === 'cart'" class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Panier</h3>

                            @foreach ($config['cart'] as $key => $setting)
                                <div>
                                    @if ($setting['type'] === 'select')
                                        <label class="block text-xs font-medium text-gray-700 mb-1">
                                            {{ $setting['label'] }}
                                        </label>
                                        @if (isset($setting['description']))
                                            <p class="text-xxxs text-gray-500 mb-2">{{ $setting['description'] }}</p>
                                        @endif
                                        <select name="settings[{{ $key }}]"
                                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                                            @foreach ($setting['options'] as $optionKey => $optionLabel)
                                                @php
                                                    $value = is_numeric($optionKey) ? $optionLabel : $optionKey;
                                                    $label = is_numeric($optionKey) ? $optionLabel : $optionLabel;
                                                    $currentValue = $currentSettings['cart'][$key] ?? $setting['default'];
                                                @endphp
                                                <option value="{{ $value }}" {{ $currentValue == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        {{-- Explications pour chaque option --}}
                                        <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                            <p class="text-xxxs text-gray-600 mb-2"><strong>Tiroir (drawer) :</strong></p>
                                            <ul class="text-xxxs text-gray-500 space-y-1 ml-3">
                                                <li>• Le panier s'ouvre en panneau latéral</li>
                                                <li>• Ajout au panier → ouverture automatique du tiroir</li>
                                                <li>• Expérience rapide sans changement de page</li>
                                            </ul>

                                            <p class="text-xxxs text-gray-600 mb-2 mt-3"><strong>Page panier :</strong></p>
                                            <ul class="text-xxxs text-gray-500 space-y-1 ml-3">
                                                <li>• Clic sur l'icône panier → redirection vers /cart</li>
                                                <li>• Ajout au panier → modal de confirmation avec infos produit</li>
                                                <li>• Vue complète du panier en page dédiée</li>
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Products Section --}}
                        @if(isset($config['products']))
                        <div x-show="activeTab === 'products'" class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Produits</h3>

                            @foreach ($config['products'] as $key => $setting)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        {{ $setting['label'] }}
                                        @if(isset($setting['description']))
                                            <span class="text-xs text-gray-500 font-normal block mt-0.5">
                                                {{ $setting['description'] }}
                                            </span>
                                        @endif
                                    </label>

                                    @if($setting['type'] === 'select')
                                        <select
                                            name="settings[{{ $key }}]"
                                            class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900"
                                        >
                                            @foreach($setting['options'] as $optionKey => $optionLabel)
                                                @php
                                                    $value = is_numeric($optionKey) ? $optionLabel : $optionKey;
                                                    $label = is_numeric($optionKey) ? $optionLabel : $optionLabel;
                                                    $currentValue = $currentSettings['products'][$key] ?? $setting['default'];
                                                @endphp
                                                <option value="{{ $value }}" {{ $currentValue == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif($setting['type'] === 'number')
                                        <input
                                            type="number"
                                            name="settings[{{ $key }}]"
                                            value="{{ $currentSettings['products'][$key] ?? $setting['default'] }}"
                                            min="{{ $setting['min'] ?? '' }}"
                                            max="{{ $setting['max'] ?? '' }}"
                                            class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900"
                                        >
                                    @elseif($setting['type'] === 'color')
                                        <input
                                            type="color"
                                            name="settings[{{ $key }}]"
                                            value="{{ $currentSettings['products'][$key] ?? $setting['default'] }}"
                                            class="mt-1 w-20 h-10 rounded-lg border border-gray-200 cursor-pointer"
                                        >
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Submit Button --}}
                        <div class="mt-6 pt-4 border-t border-gray-100 flex justify-end gap-2">
                            <a href="{{ route('admin.apparence.theme.index') }}"
                                class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-600 hover:bg-gray-50">
                                Annuler
                            </a>
                            <button type="submit"
                                class="px-4 py-2 rounded-lg font-semibold bg-[#111827] text-xs text-white hover:bg-black">
                                Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
