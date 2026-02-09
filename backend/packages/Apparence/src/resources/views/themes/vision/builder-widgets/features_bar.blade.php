{{-- Widget: Features Bar --}}
<template x-if="currentWidget().type === 'features_bar'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0" x-init="initFeaturesBarProps()">
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

            <template x-for="(feature, index) in currentWidget().props.features" :key="index">
                <div class="p-3 border border-neutral-200 rounded-lg bg-neutral-50 space-y-2">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-neutral-700">
                            Feature #<span x-text="index + 1"></span>
                        </span>
                        <button type="button" @click="removeFeature(index)"
                            class="text-xs text-neutral-400 hover:text-neutral-700">
                            <x-lucide-trash class="w-3 h-3" />
                        </button>
                    </div>

                    <label class="block">
                        <span class="text-xs text-neutral-600 block mb-1.5">Icône (nom)</span>
                        <input type="text" x-model="currentWidget().props.features[index].icon" @input="sync()"
                            class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
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
            </template>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
