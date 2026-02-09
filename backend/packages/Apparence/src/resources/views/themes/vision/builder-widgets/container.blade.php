{{-- Widget: Container --}}
<template x-if="currentWidget().type === 'container'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Fond</span>
                <input type="color"
                    x-model="currentWidget().props.background"
                    @input="sync()"
                    class="mt-1 h-7 w-14 p-0 border border-neutral-200 rounded-md bg-white">
            </label>

            {{-- Gap entre colonnes du container --}}
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                    <x-lucide-columns class="w-3.5 h-3.5 inline-block mr-1" />
                    Espacement entre colonnes
                </span>
                <select x-model="currentWidget().props.gap" @change="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
                    <option value="">Défaut (16px)</option>
                    <option value="none">Aucun (0px)</option>
                    <option value="xs">Très petit (4px)</option>
                    <option value="sm">Petit (8px)</option>
                    <option value="md">Moyen (16px)</option>
                    <option value="lg">Grand (24px)</option>
                    <option value="xl">Très grand (32px)</option>
                </select>
            </label>

            {{-- Alignement des colonnes --}}
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                    <x-lucide-align-vertical-distribute-center class="w-3.5 h-3.5 inline-block mr-1" />
                    Alignement vertical des colonnes
                </span>
                <select x-model="currentWidget().props.alignment" @change="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
                    <option value="">Étirer (défaut)</option>
                    <option value="start">Haut</option>
                    <option value="center">Centre</option>
                    <option value="end">Bas</option>
                    <option value="baseline">Baseline</option>
                </select>
            </label>

            <div class="text-xs text-neutral-500 bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                Container avec colonnes. Glissez des widgets dans les colonnes du container.
            </div>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
