{{-- Widget: Categories Grid/Slider --}}
<template x-if="currentWidget().type === 'categories_grid'" x-init="initCategoriesGridProps(); sync()">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-4">

        {{-- Display Mode: Slider or Grid --}}
        <div class="block">
            <span class="text-xs font-medium text-neutral-700 block mb-1.5">Type d'affichage</span>
            <div class="flex gap-2">
                <button type="button" @click="currentWidget().props.displayMode = 'slider'; sync()"
                    class="flex-1 px-3 py-2 text-xs rounded-lg border transition-all font-medium"
                    :class="currentWidget().props.displayMode === 'slider' ?
                        'bg-neutral-900 text-white border-neutral-900 shadow-sm' :
                        'bg-white text-neutral-700 border-neutral-300 hover:border-neutral-400'">
                    Slider
                </button>
                <button type="button" @click="currentWidget().props.displayMode = 'grid'; sync()"
                    class="flex-1 px-3 py-2 text-xs rounded-lg border transition-all font-medium"
                    :class="currentWidget().props.displayMode === 'grid' ?
                        'bg-neutral-900 text-white border-neutral-900 shadow-sm' :
                        'bg-white text-neutral-700 border-neutral-300 hover:border-neutral-400'">
                    Grille
                </button>
            </div>
        </div>

        {{-- Slider Configuration --}}
        <template x-if="currentWidget().props.displayMode === 'slider'">
            <div class="border border-neutral-200 rounded-lg p-3 space-y-3 bg-neutral-50">
                <div class="text-xs font-semibold text-neutral-800 mb-2">⚙️ Configuration Slider</div>

                {{-- Slides per view --}}
                <div class="grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="text-xs font-medium text-neutral-700 block mb-1">Slides visibles (Desktop)</span>
                        <input type="number" min="1" max="6" x-model.number="currentWidget().props.slidesPerView.desktop" @input="sync()"
                            class="w-full px-2 py-1.5 bg-white border border-neutral-200 rounded text-xs">
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium text-neutral-700 block mb-1">Slides visibles (Mobile)</span>
                        <input type="number" min="1" max="4" x-model.number="currentWidget().props.slidesPerView.mobile" @input="sync()"
                            class="w-full px-2 py-1.5 bg-white border border-neutral-200 rounded text-xs">
                    </label>
                </div>

                {{-- Slides to scroll --}}
                <div class="grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="text-xs font-medium text-neutral-700 block mb-1">Slides à défiler (Desktop)</span>
                        <input type="number" min="1" max="6" x-model.number="currentWidget().props.slidesToScroll.desktop" @input="sync()"
                            class="w-full px-2 py-1.5 bg-white border border-neutral-200 rounded text-xs">
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium text-neutral-700 block mb-1">Slides à défiler (Mobile)</span>
                        <input type="number" min="1" max="4" x-model.number="currentWidget().props.slidesToScroll.mobile" @input="sync()"
                            class="w-full px-2 py-1.5 bg-white border border-neutral-200 rounded text-xs">
                    </label>
                </div>

                {{-- Options --}}
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="currentWidget().props.showArrows" @change="sync()"
                            class="rounded border-neutral-300 text-neutral-900">
                        <span class="text-xs text-neutral-700">Afficher les flèches</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="currentWidget().props.showDots" @change="sync()"
                            class="rounded border-neutral-300 text-neutral-900">
                        <span class="text-xs text-neutral-700">Afficher les points</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="currentWidget().props.autoplay" @change="sync()"
                            class="rounded border-neutral-300 text-neutral-900">
                        <span class="text-xs text-neutral-700">Lecture automatique</span>
                    </label>
                </div>
            </div>
        </template>

        {{-- Grid Configuration --}}
        <template x-if="currentWidget().props.displayMode === 'grid'">
            <div class="border border-neutral-200 rounded-lg p-3 space-y-3 bg-neutral-50">
                <div class="text-xs font-semibold text-neutral-800 mb-2">⚙️ Configuration Grille</div>

                {{-- Columns --}}
                <div class="grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="text-xs font-medium text-neutral-700 block mb-1">Colonnes (Desktop)</span>
                        <select x-model.number="currentWidget().props.columns.desktop" @change="sync()"
                            class="w-full px-2 py-1.5 bg-white border border-neutral-200 rounded text-xs">
                            <option :value="2">2 colonnes</option>
                            <option :value="3">3 colonnes</option>
                            <option :value="4" selected>4 colonnes</option>
                            <option :value="5">5 colonnes</option>
                            <option :value="6">6 colonnes</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium text-neutral-700 block mb-1">Colonnes (Mobile)</span>
                        <select x-model.number="currentWidget().props.columns.mobile" @change="sync()"
                            class="w-full px-2 py-1.5 bg-white border border-neutral-200 rounded text-xs">
                            <option :value="1">1 colonne</option>
                            <option :value="2" selected>2 colonnes</option>
                            <option :value="3">3 colonnes</option>
                        </select>
                    </label>
                </div>
            </div>
        </template>

        {{-- Gap/Spacing --}}
        <label class="block">
            <span class="text-xs font-medium text-neutral-700 block mb-1.5">Espacement (px)</span>
            <input type="number" min="0" max="48" x-model.number="currentWidget().props.gap" @input="sync()"
                class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
        </label>

        <hr class="border-neutral-200">

        {{-- Categories Selection --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-neutral-700">Catégories à afficher</span>
                <button type="button" @click="toggleAllCategories()"
                    class="text-xxs text-neutral-600 hover:text-neutral-900">
                    <span x-text="isAllCategoriesSelected() ? 'Tout désélectionner' : 'Tout sélectionner'"></span>
                </button>
            </div>

            <div
                class="max-h-48 overflow-y-auto border border-neutral-200 rounded-lg px-2 py-2 space-y-2 bg-neutral-50">
                <template x-for="category in availableCategories" :key="category.slug">
                    <label class="flex items-center gap-2 text-xs cursor-pointer hover:bg-white px-2 py-1 rounded">
                        <input type="checkbox" :checked="isCategorySelected(category.slug)"
                            @change="toggleCategory(category.slug)"
                            class="rounded border-neutral-300 text-neutral-900">
                        <span class="flex-1" x-text="category.name"></span>
                    </label>
                </template>
            </div>

            <p class="text-xxs text-neutral-400 mt-1">
                <span x-text="getSelectedCategoriesCount()"></span> catégorie(s) sélectionnée(s)
            </p>
        </div>

        {{-- Max categories (shown when no categories selected) --}}
        <div x-show="getSelectedCategoriesCount() === 0" class="mt-3">
            <label class="block text-xs font-medium text-neutral-700 mb-1">
                Nombre max de catégories
            </label>
            <input type="number" min="1" max="50"
                x-model.number="currentWidget().props.maxCategories"
                @input="sync()"
                class="w-full text-xs border-neutral-200 rounded-lg px-3 py-2"
                placeholder="Toutes">
            <p class="text-xxs text-neutral-400 mt-1">
                Laissez vide pour afficher toutes les catégories
            </p>
        </div>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
