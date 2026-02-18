@extends('admin::layout')

@section('title', $pageTitle ?? 'Builder de page')
@section('page-title', $pageTitleHeader ?? 'Builder')

@section('content')
    @php
        $builderWidgets = array_values($widgets ?? \Omersia\Admin\Config\BuilderWidgets::all());
        $builderWidgetCategories = $widgetCategories ?? \Omersia\Admin\Config\BuilderWidgets::grouped();
        $builderCategoryLabels = $categoryLabels ?? \Omersia\Admin\Config\BuilderWidgets::categoryLabels();
    @endphp

    <div x-data='pageBuilder({
            initial: @json($contentJson),
            saveUrl: "{{ $saveUrl }}",
            csrf: "{{ csrf_token() }}",
            categoriesUrl: "{{ route('admin.api.categories') }}",
            productsUrl: "{{ route('admin.api.products') }}",
            serverWidgets: @json($builderWidgets),
        })'
        x-init="init()"
        class="h-[calc(100vh-6rem)] flex flex-col gap-3"
        data-frontend-url="{{ config('app.url', 'http://localhost:8000') }}"
        data-page-slug="{{ $page->slug ?? $page->translations->first()->slug ?? 'default' }}">
        {{-- Barre supérieure type Shopify --}}
        <div class="flex items-center justify-between bg-white border border-black/5 rounded-2xl px-4 py-3">
            <div class="flex items-start gap-3">
                <div>
                    <div class="text-xs font-semibold text-neutral-900 flex items-center gap-2">
                        <span>{{ $page->translations->first()->title ?? 'Page' }}</span>
                    </div>
                    <div class="mt-1 flex items-center gap-2 text-xs text-neutral-500">
                        <span>Type : <span class="font-medium text-neutral-700">{{ $page->type ?? 'page' }}</span></span>
                        <span class="h-1 w-1 rounded-full bg-neutral-300"></span>
                        <span>Slug :
                            <span
                                class="font-mono text-xxs text-neutral-600">{{ $page->slug ?? ($page->translations->first()->slug ?? 'default') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $backUrl }}"
                    class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                    ← Retour à la page
                </a>
                <button type="button" @click="openPreview()"
                    class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                    <x-lucide-eye class="w-3.5 h-3.5" />
                    Aperçu
                </button>
                <button type="button" @click="save()"
                    class="inline-flex items-center gap-1 rounded-lg bg-black text-white px-4 py-1.5 text-xs font-medium hover:bg-neutral-900 disabled:opacity-60"
                    x-text="saving ? 'Enregistrement…' : 'Enregistrer'" :disabled="saving">
                </button>
            </div>
        </div>

        {{-- Zone principale 3 colonnes --}}
        <div class="flex gap-3 flex-1 min-h-0">
            {{-- Sidebar widgets --}}
            <aside class="w-64 bg-white border border-black/5 rounded-2xl p-3 flex flex-col overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xs font-semibold text-neutral-900">Bibliothèque de widgets</h2>
                </div>

                <div class="mb-2">
                    <input type="text" placeholder="Rechercher un widget…"
                        class="w-full rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-xs text-neutral-700 placeholder:text-neutral-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400"
                        x-model="widgetsSearch">
                </div>

                <div class="flex-1 overflow-y-auto mt-1 space-y-2 pr-1">
                    {{-- Mode recherche : afficher tous les widgets à plat --}}
                    <template x-if="widgetsSearch">
                        <div class="space-y-1">
                            @foreach ($builderWidgets as $widget)
                                <button type="button"
                                    x-show="'{{ strtolower($widget['label']) }}'.includes(widgetsSearch.toLowerCase()) || '{{ strtolower($widget['type']) }}'.includes(widgetsSearch.toLowerCase())"
                                    class="w-full px-2.5 py-1.5 rounded-lg border border-neutral-200 bg-neutral-50 text-xs text-neutral-800 cursor-move hover:bg-neutral-100 flex items-center justify-between"
                                    draggable="true" @dragstart="onWidgetDragStart($event, '{{ $widget['type'] }}')">
                                    <span class="flex items-center gap-1.5">
                                        <span
                                            class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-white border border-neutral-200 text-xxs text-neutral-500">
                                            <x-dynamic-component :component="'lucide-' . $widget['icon']" class="w-3 h-3 text-neutral-600" />
                                        </span>
                                        <span>{{ $widget['label'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </template>

                    {{-- Mode normal : afficher par catégories --}}
                    <template x-if="!widgetsSearch">
                        <div class="space-y-2">
                            @foreach ($builderWidgetCategories as $category => $categoryWidgets)
                                <details class="group" open>
                                    <summary class="cursor-pointer list-none flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-neutral-50 text-xs font-semibold text-neutral-700">
                                        <span>{{ $builderCategoryLabels[$category] ?? $category }}</span>
                                        <span class="text-neutral-400 group-open:rotate-90 transition-transform text-sm">›</span>
                                    </summary>
                                    <div class="mt-1 space-y-1 pl-1">
                                        @foreach ($categoryWidgets as $widget)
                                            <button type="button"
                                                class="w-full px-2.5 py-1.5 rounded-lg border border-neutral-200 bg-neutral-50 text-xs text-neutral-800 cursor-move hover:bg-neutral-100 flex items-center justify-between"
                                                draggable="true" @dragstart="onWidgetDragStart($event, '{{ $widget['type'] }}')">
                                                <span class="flex items-center gap-1.5">
                                                    <span
                                                        class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-white border border-neutral-200 text-xxs text-neutral-500">
                                                        <x-dynamic-component :component="'lucide-' . $widget['icon']" class="w-3 h-3 text-neutral-600" />
                                                    </span>
                                                    <span>{{ $widget['label'] }}</span>
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </template>

                    <div x-show="widgetsSearch && filteredWidgets().length === 0" class="text-center py-8">
                        <p class="text-xs text-neutral-400">Aucun widget trouvé</p>
                    </div>
                </div>

                <div class="mt-2 pt-2 border-t border-neutral-100 text-xs text-neutral-400">
                    Glissez les widgets dans les colonnes de la page.
                </div>
            </aside>

            {{-- Canvas central --}}
            <main class="flex-1 bg-neutral-50 border border-black/5 rounded-2xl p-3 overflow-auto">
                {{-- Toolbar canvas --}}
                <div
                    class="flex items-center justify-between mb-3 rounded-lg bg-white px-4 py-2.5 sticky top-0 z-10 shadow-sm border border-gray-200">
                    <div class="flex items-center gap-2">
                        <div>
                            <div class="text-xs font-semibold text-neutral-900">Canvas</div>
                            <div class="text-xxs text-neutral-500">
                                Construction de la page
                                <span
                                    class="font-mono">{{ $page->slug ?? ($page->translations->first()->slug ?? 'default') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="addSection()"
                            class="inline-flex items-center gap-1 rounded-lg bg-white border border-neutral-200 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                            + Ajouter une section
                        </button>

                        <div class="inline-flex items-center gap-1 rounded-full bg-white border border-neutral-200 p-0.5">
                            <button type="button" @click="toggleViewMode('desktop')"
                                class="px-3 py-1.5 text-xxs rounded-full font-medium transition-colors"
                                :class="viewMode === 'desktop' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-50'">
                                Bureau
                            </button>
                            <button type="button" @click="toggleViewMode('mobile')"
                                class="px-3 py-1.5 text-xxs rounded-full font-medium transition-colors"
                                :class="viewMode === 'mobile' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-50'">
                                Mobile
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Sections --}}
                <div class="space-y-3 transition-all duration-300"
                    :class="viewMode === 'mobile' ? 'max-w-sm mx-auto' : 'w-full'"
                    x-ref="sectionsContainer"
                    @dragover.prevent.stop="onSectionsContainerDragOver($event)"
                    @dragleave.prevent.stop="onSectionsContainerDragLeave($event)"
                    @drop.prevent.stop="onSectionDrop($event)">
                    <template x-for="(section, sIndex) in data.sections" :key="section.id">
                        <section
                            class="rounded-2xl border border-neutral-200 bg-white p-3 space-y-2 shadow-[0_1px_0_0_rgba(15,23,42,0.03)]"
                            @click.stop="select('section', section.id)"
                            :class="isSelected('section', section.id) ? 'ring-2 ring-neutral-900/70' : ''" draggable="true"
                            @dragstart="onSectionDragStart($event, section.id)"
                            @dragover.prevent.stop="onSectionDragOver($event, section.id)"
                            @dragleave.prevent.stop="onSectionDragLeave($event)" :data-section-id="section.id">
                            {{-- Header section --}}
                            <div class="flex items-center justify-between text-xxs text-neutral-500">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-neutral-100 text-xs text-neutral-500 cursor-move">⋮⋮</span>
                                    <span class="font-medium text-neutral-700">Section</span>
                                    <span
                                        class="px-1.5 py-0.5 rounded-full bg-neutral-100 text-xxs font-mono text-neutral-600"
                                        x-text="'#' + section.id"></span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click.stop="addColumn(section.id)"
                                        class="inline-flex items-center gap-1 rounded-full bg-white border border-neutral-200 px-2 py-0.5 text-xxs font-medium text-neutral-700 hover:bg-neutral-50">
                                        + Colonne
                                    </button>
                                    <button type="button" @click.stop="removeSection(section.id)"
                                        class="inline-flex items-center justify-center rounded-full bg-white border border-neutral-200 p-1 text-neutral-400 hover:text-neutral-700 hover:bg-neutral-50">
                                        <x-lucide-trash class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>

                            {{-- Colonnes --}}
                            <div class="flex flex-wrap gap-2" x-ref="'section-'+section.id">
                                <template x-for="(column, cIndex) in section.columns" :key="column.id">
                                    <div class="bg-neutral-50 rounded-xl border border-dashed border-neutral-200 p-2 min-h-[90px] space-y-3 transition-all relative"
                                        :style="getColumnWidthStyle(column)"
                                        @click.stop="select('column', column.id, section.id)"
                                        :class="[
                                            isSelected('column', column.id) ?
                                            'ring-1 ring-neutral-900 bg-white border-solid' : '',
                                            dragOverColumn === column.id ? 'border-emerald-500 bg-emerald-50/40' : ''
                                        ]"
                                        draggable="true" @dragstart="onColumnDragStart($event, section.id, column.id)"
                                        @dragover.prevent.stop="onColumnDragOver($event, section.id, column.id)"
                                        @dragleave.prevent.stop="onColumnDragLeave($event)"
                                        @drop.prevent.stop="onColumnDrop($event, section.id, column.id)"
                                        :data-column-id="column.id">

                                        <div class="flex items-center justify-between text-xxs text-neutral-400 mb-1">
                                            <div class="flex items-center gap-1">
                                                <span class="cursor-move text-neutral-400">⋮⋮</span>
                                                <span class="font-medium text-neutral-600">Colonne</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <button type="button" @click.stop="addNestedColumn(section.id, column.id)"
                                                    class="inline-flex items-center gap-1 rounded-full bg-white border border-neutral-200 px-2 py-0.5 text-xxs font-medium text-neutral-700 hover:bg-neutral-50"
                                                    title="Ajouter une sous-colonne">
                                                    + Sous-col
                                                </button>
                                                <span class="font-mono text-neutral-500" x-text="column.id"></span>
                                                <button type="button" @click.stop="removeColumn(section.id, column.id)"
                                                    class="inline-flex items-center justify-center rounded-full bg-white border border-neutral-200 p-1 text-neutral-300 hover:text-neutral-700 hover:bg-neutral-50">
                                                    <x-lucide-trash class="w-3 h-3" />
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Colonnes imbriquées --}}
                                        <div x-show="column.columns && column.columns.length > 0" class="flex flex-wrap gap-2 mb-2">
                                            <template x-for="(nestedCol, nIndex) in column.columns" :key="nestedCol.id">
                                                <div class="bg-neutral-50 rounded-xl border border-dashed border-neutral-200 p-2 min-h-[90px] space-y-3 transition-all relative"
                                                    :style="getColumnWidthStyle(nestedCol)"
                                                    @click.stop="select('column', nestedCol.id, section.id)"
                                                    :class="[
                                                        isSelected('column', nestedCol.id) ?
                                                        'ring-1 ring-neutral-900 bg-white border-solid' : '',
                                                        dragOverColumn === nestedCol.id ?
                                                        'border-emerald-500 bg-emerald-50/40' : ''
                                                    ]"
                                                    draggable="true"
                                                    @dragstart="onColumnDragStart($event, section.id, nestedCol.id)"
                                                    @dragover.prevent.stop="onColumnDragOver($event, section.id, nestedCol.id)"
                                                    @dragleave.prevent.stop="onColumnDragLeave($event)"
                                                    @drop.prevent.stop="onColumnDrop($event, section.id, nestedCol.id)"
                                                    :data-column-id="nestedCol.id">

                                                    <div
                                                        class="flex items-center justify-between text-xxs text-neutral-400 mb-1">
                                                        <div class="flex items-center gap-1">
                                                            <span class="cursor-move text-neutral-400">⋮⋮</span>
                                                            <span class="font-medium text-neutral-600">Colonne</span>
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="font-mono text-neutral-500"
                                                                x-text="nestedCol.width + '%'"></span>
                                                            <button type="button"
                                                                @click.stop="removeNestedColumn(section.id, column.id, nestedCol.id)"
                                                                class="inline-flex items-center justify-center rounded-full bg-white border border-neutral-200 p-1 text-neutral-300 hover:text-neutral-700 hover:bg-neutral-50">
                                                                <x-lucide-trash class="w-3 h-3" />
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="space-y-2" :data-widgets-container="nestedCol.id">
                                                        {{-- Widgets --}}
                                                        <template x-for="(widget, wIndex) in nestedCol.widgets"
                                                            :key="widget.id">
                                                            <div>
                                                                <div class="px-3 py-2 rounded-lg bg-white border border-neutral-200 text-xs text-neutral-800 flex items-center justify-between cursor-move transition-all shadow-[0_1px_0_0_rgba(15,23,42,0.02)]"
                                                                    draggable="true"
                                                                    @dragstart="onExistingWidgetDragStart($event, section.id, nestedCol.id, widget.id, wIndex)"
                                                                    @dragover.prevent.stop="onWidgetDragOver($event, section.id, nestedCol.id, widget.id, wIndex)"
                                                                    @dragleave.prevent.stop="onWidgetDragLeave($event)"
                                                                    @drop.prevent.stop="onWidgetDropOnWidget($event, section.id, nestedCol.id, widget.id, wIndex)"
                                                                    @click.stop="select('widget', widget.id, section.id, nestedCol.id)"
                                                                    :class="[
                                                                        isSelected('widget', widget.id) ?
                                                                        'ring-1 ring-neutral-900' :
                                                                        '',
                                                                        dragOverWidget === widget.id ?
                                                                        'border-t-2 border-t-emerald-500 border-dashed' :
                                                                        ''
                                                                    ]"
                                                                    :data-widget-id="widget.id">
                                                                    <div class="flex flex-col">
                                                                        <span class="font-medium"
                                                                            x-text="widgetLabel(widget)"></span>
                                                                        <span class="text-xxs text-neutral-400"
                                                                            x-text="widget.type"></span>
                                                                    </div>
                                                                    <button type="button"
                                                                        @click.stop="removeWidget(section.id, nestedCol.id, widget.id)"
                                                                        class="text-neutral-300 hover:text-neutral-700">
                                                                        <x-lucide-trash class="w-3 h-3" />
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </template>

                                                        {{-- Drop zone --}}
                                                        <div x-show="nestedCol.widgets.length === 0 || dragOverColumn === nestedCol.id"
                                                            class="h-9 rounded-lg border-2 border-dashed border-neutral-300 flex items-center justify-center text-xxs text-neutral-400 transition-all bg-neutral-50/60"
                                                            :class="dragOverColumn === nestedCol.id && !dragOverWidget ?
                                                                'border-emerald-500 bg-emerald-50/70 text-emerald-700' :
                                                                ''"
                                                            @dragover.prevent.stop="onEmptyColumnDragOver($event, section.id, nestedCol.id)"
                                                            @drop.prevent.stop="onWidgetDrop($event, section.id, nestedCol.id)">
                                                            <span x-show="nestedCol.widgets.length === 0">Déposez un widget
                                                                ici</span>
                                                            <span
                                                                x-show="nestedCol.widgets.length > 0 && dragOverColumn === nestedCol.id && !dragOverWidget">
                                                                Relâchez pour ajouter le widget
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="space-y-2" :data-widgets-container="column.id">
                                            {{-- Widgets --}}
                                            <template x-for="(widget, wIndex) in column.widgets" :key="widget.id">
                                                <div>
                                                    {{-- Widget Container avec colonnes --}}
                                                    <template x-if="widget.type === 'container'">
                                                        <div class="p-2 rounded-xl bg-neutral-100 border border-neutral-300 space-y-2"
                                                            @click.stop="select('widget', widget.id, section.id, column.id)"
                                                            :class="isSelected('widget', widget.id) ?
                                                                'ring-2 ring-neutral-900' : ''">
                                                            <div
                                                                class="flex items-center justify-between text-xs text-neutral-700">

                                                                <span class="font-medium">Container</span>


                                                                <div class="flex items-center gap-2">
                                                                    <button type="button"
                                                                        @click.stop="addColumnToContainer(section.id, column.id, widget.id)"
                                                                        class="inline-flex items-center gap-1 rounded-full bg-white border border-neutral-300 px-2 py-0.5 text-xxs font-medium text-neutral-700 hover:bg-neutral-50"
                                                                        title="Ajouter une colonne">
                                                                        + Colonne
                                                                    </button>
                                                                    <button type="button"
                                                                        @click.stop="removeWidget(section.id, column.id, widget.id)"
                                                                        class="text-neutral-300 hover:text-neutral-700">
                                                                        <x-lucide-trash class="w-3 h-3" />
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            {{-- Colonnes du container --}}
                                                            <div class="flex flex-wrap gap-2"
                                                                x-show="widget.props.columns && widget.props.columns.length > 0">
                                                                <template
                                                                    x-for="(containerCol, cIdx) in widget.props.columns"
                                                                    :key="containerCol.id">
                                                                    <div class="bg-neutral-50 rounded-xl border border-dashed border-neutral-200 p-2 min-h-[90px] space-y-3 transition-all relative"
                                                                        :style="getColumnWidthStyle(containerCol)"
                                                                        @click.stop="select('column', containerCol.id, section.id)"
                                                                        :class="[
                                                                            isSelected('column', containerCol.id) ?
                                                                            'ring-1 ring-neutral-900 bg-white border-solid' :
                                                                            '',
                                                                            dragOverColumn === containerCol.id ?
                                                                            'border-emerald-500 bg-emerald-50/40' : ''
                                                                        ]"
                                                                        draggable="true"
                                                                        @dragstart="onColumnDragStart($event, section.id, containerCol.id)"
                                                                        @dragover.prevent.stop="onColumnDragOver($event, section.id, containerCol.id)"
                                                                        @dragleave.prevent.stop="onColumnDragLeave($event)"
                                                                        @drop.prevent.stop="onColumnDrop($event, section.id, containerCol.id)"
                                                                        :data-column-id="containerCol.id">

                                                                        <div
                                                                            class="flex items-center justify-between text-xxs text-neutral-400 mb-1">
                                                                            <div class="flex items-center gap-1">
                                                                                <span
                                                                                    class="cursor-move text-neutral-400">⋮⋮</span>
                                                                                <span
                                                                                    class="font-medium text-neutral-600">Colonne</span>
                                                                            </div>
                                                                            <div class="flex items-center gap-1.5">
                                                                                <span class="font-mono text-neutral-500"
                                                                                    x-text="containerCol.width + '%'"></span>
                                                                                <button type="button"
                                                                                    @click.stop="removeColumnFromContainer(section.id, column.id, widget.id, containerCol.id)"
                                                                                    class="inline-flex items-center justify-center rounded-full bg-white border border-neutral-200 p-1 text-neutral-300 hover:text-neutral-700 hover:bg-neutral-50">
                                                                                    <x-lucide-trash class="w-3 h-3" />
                                                                                </button>
                                                                            </div>
                                                                        </div>

                                                                        <div class="space-y-2"
                                                                            :data-widgets-container="containerCol.id">
                                                                            {{-- Widgets --}}
                                                                            <template
                                                                                x-for="(w, wIdx) in containerCol.widgets"
                                                                                :key="w.id">
                                                                                <div>
                                                                                    <div class="px-3 py-2 rounded-lg bg-white border border-neutral-200 text-xs text-neutral-800 flex items-center justify-between cursor-move transition-all shadow-[0_1px_0_0_rgba(15,23,42,0.02)]"
                                                                                        draggable="true"
                                                                                        @dragstart="onExistingWidgetDragStart($event, section.id, containerCol.id, w.id, wIdx)"
                                                                                        @dragover.prevent.stop="onWidgetDragOver($event, section.id, containerCol.id, w.id, wIdx)"
                                                                                        @dragleave.prevent.stop="onWidgetDragLeave($event)"
                                                                                        @drop.prevent.stop="onWidgetDropOnWidget($event, section.id, containerCol.id, w.id, wIdx)"
                                                                                        @click.stop="select('widget', w.id, section.id, containerCol.id)"
                                                                                        :class="[
                                                                                            isSelected('widget', w.id) ?
                                                                                            'ring-1 ring-neutral-900' :
                                                                                            '',
                                                                                            dragOverWidget === w.id ?
                                                                                            'border-t-2 border-t-emerald-500 border-dashed' :
                                                                                            ''
                                                                                        ]"
                                                                                        :data-widget-id="w.id">
                                                                                        <div class="flex flex-col">
                                                                                            <span class="font-medium"
                                                                                                x-text="widgetLabel(w)"></span>
                                                                                            <span
                                                                                                class="text-xxs text-neutral-400"
                                                                                                x-text="w.type"></span>
                                                                                        </div>
                                                                                        <button type="button"
                                                                                            @click.stop="removeWidget(section.id, containerCol.id, w.id)"
                                                                                            class="text-neutral-300 hover:text-neutral-700">
                                                                                            <x-lucide-trash
                                                                                                class="w-3 h-3" />
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </template>

                                                                            {{-- Drop zone --}}
                                                                            <div x-show="containerCol.widgets.length === 0 || dragOverColumn === containerCol.id"
                                                                                class="h-9 rounded-lg border-2 border-dashed border-neutral-300 flex items-center justify-center text-xxs text-neutral-400 transition-all bg-neutral-50/60"
                                                                                :class="dragOverColumn === containerCol.id && !
                                                                                    dragOverWidget ?
                                                                                    'border-emerald-500 bg-emerald-50/70 text-emerald-700' :
                                                                                    ''"
                                                                                @dragover.prevent.stop="onEmptyColumnDragOver($event, section.id, containerCol.id)"
                                                                                @drop.prevent.stop="onWidgetDrop($event, section.id, containerCol.id)">
                                                                                <span
                                                                                    x-show="containerCol.widgets.length === 0">Déposez
                                                                                    un widget ici</span>
                                                                                <span
                                                                                    x-show="containerCol.widgets.length > 0 && dragOverColumn === containerCol.id && !dragOverWidget">
                                                                                    Relâchez pour ajouter le widget
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    {{-- Widget normal --}}
                                                    <template x-if="widget.type !== 'container'">
                                                        <div class="px-3 py-2 rounded-lg bg-white border border-neutral-200 text-xs text-neutral-800 flex items-center justify-between cursor-move transition-all shadow-[0_1px_0_0_rgba(15,23,42,0.02)]"
                                                            draggable="true"
                                                            @dragstart="onExistingWidgetDragStart($event, section.id, column.id, widget.id, wIndex)"
                                                            @dragover.prevent.stop="onWidgetDragOver($event, section.id, column.id, widget.id, wIndex)"
                                                            @dragleave.prevent.stop="onWidgetDragLeave($event)"
                                                            @drop.prevent.stop="onWidgetDropOnWidget($event, section.id, column.id, widget.id, wIndex)"
                                                            @click.stop="select('widget', widget.id, section.id, column.id)"
                                                            :class="[
                                                                isSelected('widget', widget.id) ?
                                                                'ring-1 ring-neutral-900' :
                                                                '',
                                                                dragOverWidget === widget.id ?
                                                                'border-t-2 border-t-emerald-500 border-dashed' : ''
                                                            ]"
                                                            :data-widget-id="widget.id">
                                                            <div class="flex flex-col">
                                                                <span class="font-medium"
                                                                    x-text="widgetLabel(widget)"></span>
                                                                <span class="text-xxs text-neutral-400"
                                                                    x-text="widget.type"></span>
                                                            </div>
                                                            <button type="button"
                                                                @click.stop="removeWidget(section.id, column.id, widget.id)"
                                                                class="text-neutral-300 hover:text-neutral-700">
                                                                <x-lucide-trash class="w-3 h-3" />
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            {{-- Drop zone --}}
                                            <div x-show="column.widgets.length === 0 || dragOverColumn === column.id"
                                                class="h-9 rounded-lg border-2 border-dashed border-neutral-300 flex items-center justify-center text-xxs text-neutral-400 transition-all bg-neutral-50/60"
                                                :class="dragOverColumn === column.id && !dragOverWidget ?
                                                    'border-emerald-500 bg-emerald-50/70 text-emerald-700' : ''"
                                                @dragover.prevent.stop="onEmptyColumnDragOver($event, section.id, column.id)"
                                                @drop.prevent.stop="onWidgetDrop($event, section.id, column.id)">
                                                <span x-show="column.widgets.length === 0">Déposez un widget ici</span>
                                                <span
                                                    x-show="column.widgets.length > 0 && dragOverColumn === column.id && !dragOverWidget">
                                                    Relâchez pour ajouter le widget
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </section>
                    </template>

                    {{-- État vide --}}
                    <template x-if="!data.sections.length">
                        <div
                            class="flex flex-col items-center justify-center border border-dashed border-neutral-300 rounded-2xl p-8 bg-white text-center">
                            <div
                                class="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 text-neutral-500 text-lg">
                                +
                            </div>
                            <p class="text-xs font-medium text-neutral-800 mb-1">
                                Aucune section pour le moment
                            </p>
                            <p class="text-xs text-neutral-500 mb-3">
                                Cliquez sur « Ajouter une section » pour commencer à construire votre page.
                            </p>
                            <button type="button" @click="addSection()"
                                class="inline-flex items-center gap-1 rounded-lg bg-neutral-900 text-white px-4 py-1.5 text-xs font-medium hover:bg-neutral-800">
                                Ajouter une section
                            </button>
                        </div>
                    </template>
                </div>
            </main>

            {{-- Panneau de propriétés --}}
            @include('admin::builder.builder-properties')
        </div>

        {{-- Media Picker Modal --}}
        <x-admin::media-picker name="media-picker" />
    </div>
@endsection

@push('scripts')
    @vite(['packages/Admin/src/resources/js/builder/preview-modal.js'])
@endpush
