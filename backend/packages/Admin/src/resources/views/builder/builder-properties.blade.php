<aside class="w-80 bg-white border border-black/5 rounded-2xl p-3 flex flex-col overflow-hidden">
    <div class="flex items-center justify-between mb-2">
        <div>
            <div class="text-xs font-semibold text-neutral-900">Propriétés</div>
            <div class="text-xxs text-neutral-500" x-show="selected">
                <span x-show="selected?.type === 'section'">Section sélectionnée</span>
                <span x-show="selected?.type === 'column'">Colonne sélectionnée</span>
                <span x-show="selected?.type === 'widget'">Widget sélectionné</span>
            </div>
            <div class="text-xxs text-neutral-400" x-show="!selected">
                Aucun élément sélectionné
            </div>
        </div>
    </div>

    <hr class="border-neutral-100" />

    <div class="flex-1 overflow-y-auto text-xs">
        {{-- Aucun élément sélectionné --}}
        <template x-if="!selected">
            <div class="text-xs text-neutral-400">
                Cliquez sur une section, une colonne ou un widget dans le canvas pour modifier ses paramètres.
            </div>
        </template>

        {{-- Section settings --}}
        <template x-if="typeof selected !== 'undefined' && selected && selected.type === 'section'">
            <div x-data="{ activeSectionTab: 'content' }" class="space-y-3 mt-0">
                @include('admin::builder.partials.section-tabs-nav')

                {{-- Content tab --}}
                <div x-show="activeSectionTab === 'content'" class="space-y-3">
                <label class="block">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">Fond</span>
                    <div class="flex items-center gap-2 mt-1">
                        <input type="color"
                            :value="/^#([0-9A-Fa-f]{3}){1,2}$/.test(currentSection().settings.background || '') ? currentSection().settings.background : '#ffffff'"
                            @input="currentSection().settings.background = $event.target.value; sync()"
                            class="h-7 w-14 p-0 border border-neutral-200 rounded-md bg-white">
                        <button type="button"
                            @click="currentSection().settings.background = ''; sync()"
                            class="inline-flex items-center rounded-md border border-neutral-200 bg-white px-2 py-1 text-xxs font-medium text-neutral-700 hover:bg-neutral-50">
                            Aucun fond
                        </button>
                    </div>
                    <span x-show="!currentSection().settings.background"
                        class="text-xxs text-neutral-500 mt-1 block">
                        Fond transparent (valeur vide)
                    </span>
                </label>

                {{-- Gap entre colonnes --}}
                <label class="block">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                        <x-lucide-columns class="w-3.5 h-3.5 inline-block mr-1" />
                        Espacement entre colonnes
                    </span>
                    <select x-model="currentSection().settings.gap" @change="sync()"
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
                    <select x-model="currentSection().settings.alignment" @change="sync()"
                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
                        <option value="">Étirer (défaut)</option>
                        <option value="start">Haut</option>
                        <option value="center">Centre</option>
                        <option value="end">Bas</option>
                        <option value="baseline">Baseline</option>
                    </select>
                </label>

                <label class="flex items-center gap-2">
                    <input type="checkbox" x-model="currentSection().settings.fullWidth"
                        @input="sync()"
                        class="rounded border-neutral-300 text-black focus:ring-black focus:ring-offset-0">
                    <span class="text-xs font-medium text-neutral-700">Pleine largeur</span>
                </label>
                </div>

                {{-- Settings tab --}}
                <div x-show="activeSectionTab === 'settings'" class="space-y-3">
                    @include('admin::builder.partials.section-settings-tab')
                </div>
            </div>
        </template>

        {{-- Column settings --}}
        <template x-if="typeof selected !== 'undefined' && selected && selected.type === 'column'">
            <div x-data="{ activeColumnTab: 'content' }" class="space-y-3 mt-0">
                @include('admin::builder.partials.column-tabs-nav')

                {{-- Content tab --}}
                <div x-show="activeColumnTab === 'content'" class="space-y-3">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-2">
                    <p class="text-xxs font-medium text-blue-900 mb-1">💡 Largeurs responsive</p>
                    <p class="text-xxs text-blue-700">Définissez des largeurs différentes pour desktop et mobile.</p>
                </div>

                <label class="block">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                        <x-lucide-monitor class="w-3 h-3 inline-block mr-1" />
                        Largeur Bureau (%)
                    </span>
                    <input type="number"
                        x-model.number="currentColumn().desktopWidth"
                        @input="currentColumn().width = currentColumn().desktopWidth; sync()"
                        class="w-full mt-1 border border-neutral-200 rounded-md px-2 py-1.5 text-xs"
                        min="10" max="100">
                </label>

                <label class="block">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                        <x-lucide-smartphone class="w-3 h-3 inline-block mr-1" />
                        Largeur Mobile (%)
                    </span>
                    <input type="number"
                        x-model.number="currentColumn().mobileWidth"
                        @input="sync()"
                        class="w-full mt-1 border border-neutral-200 rounded-md px-2 py-1.5 text-xs"
                        min="10" max="100">
                </label>

                <div class="text-xs text-neutral-500 bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                    <p class="font-medium text-neutral-700 mb-1">Aperçu actuel</p>
                    <p x-show="viewMode === 'desktop'">
                        Vue Bureau : <span class="font-mono font-semibold text-neutral-900" x-text="currentColumn().desktopWidth + '%'"></span>
                    </p>
                    <p x-show="viewMode === 'mobile'">
                        Vue Mobile : <span class="font-mono font-semibold text-neutral-900" x-text="currentColumn().mobileWidth + '%'"></span>
                    </p>
                </div>
                </div>

                {{-- Settings tab --}}
                <div x-show="activeColumnTab === 'settings'" class="space-y-3">
                    @include('admin::builder.partials.column-settings-tab')
                </div>
            </div>
        </template>

        {{-- Widget settings --}}
        <template x-if="typeof selected !== 'undefined' && selected && selected.type === 'widget'">
            <div :key="selected.widgetId">
                @php
                    $builderWidgets = array_values($widgets ?? \Omersia\Admin\Config\BuilderWidgets::all());
                @endphp
                @foreach($builderWidgets as $widget)
                    @include(\Omersia\Apparence\Helpers\ThemeViewHelper::getWidgetView($widget['type'], $themeSlug ?? null))
                @endforeach
            </div>
        </template>
    </div>
</aside>
