@php
    $lucideIcons = [
        'Truck', 'Package', 'ShoppingCart', 'ShoppingBag', 'Gift', 'Tag', 'Percent',
        'CreditCard', 'ShieldCheck', 'Lock', 'DollarSign', 'Wallet',
        'Phone', 'Mail', 'MessageCircle', 'MessageSquare', 'Send', 'Headphones',
        'Star', 'Heart', 'ThumbsUp', 'Award', 'Trophy', 'Smile', 'Users',
        'Clock', 'Calendar', 'MapPin', 'Map', 'Navigation',
        'Check', 'CheckCircle', 'Zap', 'Sparkles', 'TrendingUp', 'ArrowRight',
        'Undo2', 'RotateCcw', 'RefreshCw', 'HelpCircle', 'Info',
        'Home', 'Bell', 'Settings', 'Eye', 'Download', 'Upload', 'Search', 'Filter',
    ];
@endphp

{{-- Widget: Features Bar --}}
<template x-if="currentWidget().type === 'features_bar'">
    <div x-data="{ activeWidgetTab: 'content', iconSearch: {}, openFeatures: {} }" class="space-y-3 mt-0" x-init="initFeaturesBarProps()">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-neutral-700">Fonctionnalités</span>
                <button type="button" @click="addFeature()"
                    class="px-3 py-1.5 text-xs bg-neutral-900 text-white rounded-lg hover:bg-neutral-800">
                    + Ajouter
                </button>
            </div>

            <label class="block">
                <span class="text-xs text-neutral-600 block mb-1.5">Type de titre des features</span>
                <select x-model="currentWidget().props.featureTitleTag" @change="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
                    <option value="h1">H1</option>
                    <option value="h2">H2</option>
                    <option value="h3">H3</option>
                    <option value="h4">H4</option>
                    <option value="h5">H5</option>
                    <option value="h6">H6</option>
                </select>
            </label>

            <template x-for="(feature, index) in currentWidget().props.features" :key="index">
                <div class="border border-neutral-200 rounded-lg bg-neutral-50">
                    <div class="flex items-center justify-between px-3 py-2">
                        <button type="button"
                            @click="openFeatures[index] = !openFeatures[index]"
                            class="flex-1 min-w-0 flex items-center gap-2 text-left">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded border border-neutral-200 bg-white shrink-0">
                                @foreach ($lucideIcons as $iconName)
                                    <x-dynamic-component
                                        x-show="currentWidget().props.features[index].icon === '{{ $iconName }}'"
                                        :component="'lucide-' . preg_replace('/(?<=\\D)(\\d+)/', '-$1', \Illuminate\Support\Str::kebab($iconName))"
                                        class="w-3.5 h-3.5 text-neutral-800"
                                    />
                                @endforeach
                                <x-lucide-circle
                                    x-show="!availableIcons().includes(currentWidget().props.features[index].icon || '')"
                                    class="w-3.5 h-3.5 text-neutral-400"
                                />
                            </span>

                            <div class="min-w-0">
                                <div class="text-xs font-medium text-neutral-800 truncate">
                                    <span x-text="currentWidget().props.features[index].title || `Feature #${index + 1}`"></span>
                                </div>
                                <div class="text-[11px] text-neutral-500 truncate">
                                    <span x-text="currentWidget().props.features[index].icon || 'Icône non définie'"></span>
                                </div>
                            </div>
                        </button>

                        <div class="ml-2 flex items-center gap-1 shrink-0">
                            <button type="button"
                                @click="openFeatures[index] = !openFeatures[index]"
                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-neutral-200 bg-white text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-700"
                                title="Ouvrir/Fermer">
                                <x-lucide-chevron-down
                                    class="w-3.5 h-3.5 transition-transform"
                                    x-bind:class="openFeatures[index] ? 'rotate-180' : ''"
                                />
                            </button>

                            <button type="button" @click="removeFeature(index)"
                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-neutral-200 bg-white text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700"
                                title="Supprimer">
                                <x-lucide-trash class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div x-show="openFeatures[index]" class="px-3 pb-3 space-y-2">
                        <label class="block">
                            <span class="text-xs text-neutral-600 block mb-1.5">Icône</span>
                            <input type="text" x-model="iconSearch[index]" placeholder="Rechercher une icône..."
                                class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">

                            <div class="mt-2 max-h-40 overflow-y-auto bg-white border border-neutral-200 rounded-lg">
                                @foreach ($lucideIcons as $iconName)
                                    <button type="button"
                                        @click="currentWidget().props.features[index].icon = '{{ $iconName }}'; sync()"
                                        x-show="'{{ strtolower($iconName) }}'.includes((iconSearch[index] || '').toLowerCase())"
                                        :class="currentWidget().props.features[index].icon === '{{ $iconName }}' ? 'bg-neutral-100' : 'hover:bg-neutral-50'"
                                        class="w-full px-3 py-2 flex items-center gap-2 text-left text-xs text-neutral-700 border-b border-neutral-100 last:border-b-0">
                                        <x-dynamic-component :component="'lucide-' . preg_replace('/(?<=\\D)(\\d+)/', '-$1', \Illuminate\Support\Str::kebab($iconName))" class="w-3.5 h-3.5 text-neutral-800" />
                                        <span>{{ $iconName }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-2">
                                <span class="text-[11px] text-neutral-500">Aperçu sélectionné</span>
                                <div class="mt-1">
                                    @foreach ($lucideIcons as $iconName)
                                        <span x-show="currentWidget().props.features[index].icon === '{{ $iconName }}'"
                                            class="inline-flex items-center gap-2 px-2 py-1 bg-white border border-neutral-200 rounded text-xs text-neutral-700">
                                            <x-dynamic-component :component="'lucide-' . preg_replace('/(?<=\\D)(\\d+)/', '-$1', \Illuminate\Support\Str::kebab($iconName))" class="w-3.5 h-3.5 text-neutral-800" />
                                            <span>{{ $iconName }}</span>
                                        </span>
                                    @endforeach
                                    <span x-show="!availableIcons().includes(currentWidget().props.features[index].icon || '')"
                                        class="inline-flex items-center gap-2 px-2 py-1 bg-white border border-neutral-200 rounded text-xs text-neutral-700">
                                        <span x-text="currentWidget().props.features[index].icon || 'Icône non définie'"></span>
                                    </span>
                                </div>
                            </div>
                        </label>

                        <label class="block">
                            <span class="text-xs text-neutral-600 block mb-1.5">Titre</span>
                            <input type="text" x-model="currentWidget().props.features[index].title" @input="sync()"
                                class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
                        </label>

                        <label class="block">
                            <span class="text-xs text-neutral-600 block mb-1.5">Description</span>
                            <input type="text" x-model="currentWidget().props.features[index].description" @input="sync()"
                                class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
                        </label>
                    </div>
                </div>
            </template>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
