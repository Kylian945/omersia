{{-- Template de section réutilisable pour beforeNative et afterNative --}}
<template x-for="(section, sIndex) in data.{{ $zone }}.sections" :key="section.id">
    <section class="rounded-2xl border border-neutral-200 bg-white p-3 space-y-2 shadow-[0_1px_0_0_rgba(15,23,42,0.03)]"
        @click.stop="select('section', section.id, '{{ $zone }}')"
        :class="isSelected('section', section.id) ? 'ring-2 ring-neutral-900/70' : ''"
        draggable="true"
        @dragstart="onSectionDragStart($event, section.id, '{{ $zone }}')"
        @dragover.prevent.stop="onSectionDragOver($event, section.id)"
        :data-section-id="section.id">

        {{-- Header section --}}
        <div class="flex items-center justify-between text-xxs text-neutral-500">
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-neutral-100 text-xs text-neutral-500 cursor-move">⋮⋮</span>
                <span class="font-medium text-neutral-700">Section</span>
                <span class="px-1.5 py-0.5 rounded-full bg-neutral-100 text-xxs font-mono text-neutral-600"
                    x-text="'#' + section.id"></span>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button"
                    @click.stop="addColumn(section.id, '{{ $zone }}')"
                    class="inline-flex items-center gap-1 rounded-full bg-white border border-neutral-200 px-2 py-0.5 text-xxs font-medium text-neutral-700 hover:bg-neutral-50">
                    + Colonne
                </button>
                <button type="button"
                    @click.stop="removeSection(section.id, '{{ $zone }}')"
                    class="inline-flex items-center justify-center rounded-full bg-white border border-neutral-200 p-1 text-neutral-400 hover:text-neutral-700 hover:bg-neutral-50">
                    <x-lucide-trash class="w-3 h-3" />
                </button>
            </div>
        </div>

        {{-- Colonnes --}}
        <div class="flex flex-wrap gap-2">
            <template x-for="(column, cIndex) in section.columns" :key="column.id">
                <div class="bg-neutral-50 rounded-xl border border-dashed border-neutral-200 p-2 min-h-[90px] space-y-3 transition-all relative"
                    :style="getColumnWidthStyle(column)"
                    @click.stop="select('column', column.id, '{{ $zone }}', section.id)"
                    :class="[
                        isSelected('column', column.id) ? 'ring-1 ring-neutral-900 bg-white border-solid' : '',
                        dragOverColumn === column.id ? 'border-emerald-500 bg-emerald-50/40' : ''
                    ]"
                    draggable="true"
                    @dragstart="onColumnDragStart($event, section.id, column.id, '{{ $zone }}')"
                    @dragover.prevent.stop="onColumnDragOver($event, section.id, column.id)"
                    @drop.prevent.stop="onColumnDrop($event, section.id, column.id, '{{ $zone }}')"
                    :data-column-id="column.id">

                    <div class="flex items-center justify-between text-xxs text-neutral-400 mb-1">
                        <div class="flex items-center gap-1">
                            <span class="cursor-move text-neutral-400">⋮⋮</span>
                            <span class="font-medium text-neutral-600">Colonne</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                @click.stop="removeColumn(section.id, column.id, '{{ $zone }}')"
                                class="inline-flex items-center justify-center rounded-full bg-white border border-neutral-200 p-0.5 text-neutral-400 hover:text-neutral-700 hover:bg-neutral-50">
                                <x-lucide-x class="w-2.5 h-2.5" />
                            </button>
                        </div>
                    </div>

                    {{-- Widgets dans la colonne --}}
                    <template x-for="(widget, wIndex) in column.widgets" :key="widget.id">
                        <div>
                            {{-- Widget Container avec colonnes --}}
                            <template x-if="widget.type === 'container'">
                                <div class="p-2 rounded-xl bg-neutral-100 border border-neutral-300 space-y-2"
                                    @click.stop="select('widget', widget.id, '{{ $zone }}', section.id, column.id)"
                                    :class="isSelected('widget', widget.id) ? 'ring-2 ring-neutral-900' : ''">
                                    <div class="flex items-center justify-between text-xs text-neutral-700">
                                        <span class="font-medium">Container</span>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                @click.stop="addColumnToContainer(section.id, column.id, widget.id, '{{ $zone }}')"
                                                class="inline-flex items-center gap-1 rounded-full bg-white border border-neutral-300 px-2 py-0.5 text-xxs font-medium text-neutral-700 hover:bg-neutral-50"
                                                title="Ajouter une colonne">
                                                + Colonne
                                            </button>
                                            <button type="button"
                                                @click.stop="removeWidget(section.id, column.id, widget.id, '{{ $zone }}')"
                                                class="text-neutral-300 hover:text-neutral-700">
                                                <x-lucide-trash class="w-3 h-3" />
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Colonnes du container --}}
                                    <div class="flex flex-wrap gap-2"
                                        x-show="widget.props.columns && widget.props.columns.length > 0">
                                        <template x-for="(containerCol, cIdx) in widget.props.columns" :key="containerCol.id">
                                            <div class="bg-neutral-50 rounded-xl border border-dashed border-neutral-200 p-2 min-h-[90px] space-y-3 transition-all relative"
                                                :style="getColumnWidthStyle(containerCol)"
                                                @click.stop="select('column', containerCol.id, '{{ $zone }}', section.id)"
                                                :class="[
                                                    isSelected('column', containerCol.id)
                                                        ? 'ring-1 ring-neutral-900 bg-white border-solid'
                                                        : '',
                                                    dragOverColumn === containerCol.id
                                                        ? 'border-emerald-500 bg-emerald-50/40'
                                                        : ''
                                                ]"
                                                draggable="true"
                                                @dragstart="onColumnDragStart($event, section.id, containerCol.id, '{{ $zone }}')"
                                                @dragover.prevent.stop="onColumnDragOver($event, section.id, containerCol.id)"
                                                @dragleave.prevent.stop="onColumnDragLeave($event)"
                                                @drop.prevent.stop="onColumnDrop($event, section.id, containerCol.id, '{{ $zone }}')"
                                                :data-column-id="containerCol.id">

                                                <div class="flex items-center justify-between text-xxs text-neutral-400 mb-1">
                                                    <div class="flex items-center gap-1">
                                                        <span class="cursor-move text-neutral-400">⋮⋮</span>
                                                        <span class="font-medium text-neutral-600">Colonne</span>
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="font-mono text-neutral-500"
                                                            x-text="containerCol.width + '%'"></span>
                                                        <button type="button"
                                                            @click.stop="removeColumnFromContainer(section.id, column.id, widget.id, containerCol.id, '{{ $zone }}')"
                                                            class="inline-flex items-center justify-center rounded-full bg-white border border-neutral-200 p-1 text-neutral-300 hover:text-neutral-700 hover:bg-neutral-50">
                                                            <x-lucide-trash class="w-3 h-3" />
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="space-y-2" :data-widgets-container="containerCol.id">
                                                    {{-- Widgets dans les colonnes du container --}}
                                                    <template x-for="(w, wIdx) in containerCol.widgets" :key="w.id">
                                                        <div>
                                                            <div class="px-3 py-2 rounded-lg bg-white border border-neutral-200 text-xs text-neutral-800 flex items-center justify-between cursor-move transition-all shadow-[0_1px_0_0_rgba(15,23,42,0.02)]"
                                                                draggable="true"
                                                                @dragstart="onExistingWidgetDragStart($event, section.id, containerCol.id, w.id, wIdx, '{{ $zone }}')"
                                                                @dragover.prevent.stop="onWidgetDragOver($event, section.id, containerCol.id, w.id, wIdx)"
                                                                @dragleave.prevent.stop="onWidgetDragLeave($event)"
                                                                @drop.prevent.stop="onWidgetDropOnWidget($event, section.id, containerCol.id, w.id, wIdx, '{{ $zone }}')"
                                                                @click.stop="select('widget', w.id, '{{ $zone }}', section.id, containerCol.id)"
                                                                :class="[
                                                                    isSelected('widget', w.id)
                                                                        ? 'ring-1 ring-neutral-900'
                                                                        : '',
                                                                    dragOverWidget === w.id
                                                                        ? 'border-t-2 border-t-emerald-500 border-dashed'
                                                                        : ''
                                                                ]"
                                                                :data-widget-id="w.id">
                                                                <div class="flex flex-col">
                                                                    <span class="font-medium" x-text="widgetLabel(w)"></span>
                                                                    <span class="text-xxs text-neutral-400" x-text="w.type"></span>
                                                                </div>
                                                                <button type="button"
                                                                    @click.stop="removeWidget(section.id, containerCol.id, w.id, '{{ $zone }}')"
                                                                    class="text-neutral-300 hover:text-neutral-700">
                                                                    <x-lucide-trash class="w-3 h-3" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    {{-- Drop zone pour les colonnes du container --}}
                                                    <div x-show="containerCol.widgets.length === 0 || dragOverColumn === containerCol.id"
                                                        class="h-9 rounded-lg border-2 border-dashed border-neutral-300 flex items-center justify-center text-xxs text-neutral-400 transition-all bg-neutral-50/60"
                                                        :class="dragOverColumn === containerCol.id && !dragOverWidget
                                                            ? 'border-emerald-500 bg-emerald-50/70 text-emerald-700'
                                                            : ''"
                                                        @dragover.prevent.stop="onEmptyColumnDragOver($event, section.id, containerCol.id)"
                                                        @drop.prevent.stop="onWidgetDrop($event, section.id, containerCol.id, '{{ $zone }}')">
                                                        <span x-show="containerCol.widgets.length === 0">
                                                            Déposez un widget ici
                                                        </span>
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
                                    @dragstart="onExistingWidgetDragStart($event, section.id, column.id, widget.id, wIndex, '{{ $zone }}')"
                                    @dragover.prevent.stop="onWidgetDragOver($event, section.id, column.id, widget.id, wIndex)"
                                    @dragleave.prevent.stop="onWidgetDragLeave($event)"
                                    @drop.prevent.stop="onWidgetDropOnWidget($event, section.id, column.id, widget.id, wIndex, '{{ $zone }}')"
                                    @click.stop="select('widget', widget.id, '{{ $zone }}', section.id, column.id)"
                                    :class="[
                                        isSelected('widget', widget.id)
                                            ? 'ring-1 ring-neutral-900'
                                            : '',
                                        dragOverWidget === widget.id
                                            ? 'border-t-2 border-t-emerald-500 border-dashed'
                                            : ''
                                    ]"
                                    :data-widget-id="widget.id">
                                    <div class="flex flex-col">
                                        <span class="font-medium" x-text="widgetLabel(widget)"></span>
                                        <span class="text-xxs text-neutral-400" x-text="widget.type"></span>
                                    </div>
                                    <button type="button"
                                        @click.stop="removeWidget(section.id, column.id, widget.id, '{{ $zone }}')"
                                        class="text-neutral-300 hover:text-neutral-700">
                                        <x-lucide-trash class="w-3 h-3" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Dropzone de colonne (hors container) --}}
                    <div x-show="column.widgets.length === 0 || dragOverColumn === column.id"
                        class="h-9 rounded-lg border-2 border-dashed border-neutral-300 flex items-center justify-center text-xxs text-neutral-400 transition-all bg-neutral-50/60"
                        :class="dragOverColumn === column.id && !dragOverWidget
                            ? 'border-emerald-500 bg-emerald-50/70 text-emerald-700'
                            : ''"
                        @dragover.prevent.stop="onEmptyColumnDragOver($event, section.id, column.id)"
                        @drop.prevent.stop="onWidgetDrop($event, section.id, column.id, '{{ $zone }}')">
                        <span x-show="column.widgets.length === 0">
                            Déposez un widget ici
                        </span>
                        <span x-show="column.widgets.length > 0 && dragOverColumn === column.id && !dragOverWidget">
                            Relâchez pour ajouter le widget
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </section>
</template>
