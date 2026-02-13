@extends('admin::layout')

@section('title', $pageTitle ?? 'Builder de page')
@section('page-title', $pageTitleHeader ?? 'Builder')

@section('content')
    @php
        $builderWidgets = array_values($widgets ?? \Omersia\Admin\Config\BuilderWidgets::all());
    @endphp

    <div x-data='pageBuilderNative({
            initial: @json($contentJson),
            saveUrl: "{{ $saveUrl }}",
            csrf: "{{ csrf_token() }}",
            categoriesUrl: "{{ route('admin.api.categories') }}",
            productsUrl: "{{ route('admin.api.products') }}",
            serverWidgets: @json($builderWidgets),
            pageType: "{{ $pageType ?? 'category' }}",
        })'
        x-init="init()"
        class="h-[calc(100vh-6rem)] flex flex-col gap-3"
        data-frontend-url="{{ config('app.url', 'http://localhost:8000') }}"
        data-page-slug="{{ $page->slug ?? $page->translations->first()->slug ?? 'default' }}"
        data-page-type="{{ $pageType ?? 'page' }}">

        {{-- Barre supérieure --}}
        <div class="flex items-center justify-between bg-white border border-black/5 rounded-2xl px-4 py-3">
            <div class="flex items-start gap-3">
                <div>
                    <div class="text-xs font-semibold text-neutral-900 flex items-center gap-2">
                        <span>{{ $page->translations->first()->title ?? 'Page' }}</span>
                        <span class="px-2 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-xxs text-blue-700 font-medium">
                            Contenu natif
                        </span>
                    </div>
                    <div class="mt-1 flex items-center gap-2 text-xs text-neutral-500">
                        <span>Type : <span class="font-medium text-neutral-700">{{ $page->type ?? 'page' }}</span></span>
                        <span class="h-1 w-1 rounded-full bg-neutral-300"></span>
                        <span>Slug :
                            <span class="font-mono text-xxs text-neutral-600">{{ $page->slug ?? 'default' }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $backUrl }}"
                    class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                    ← Retour
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

                <div class="flex-1 overflow-y-auto mt-1 space-y-1 pr-1">
                    @foreach ($builderWidgets as $widget)
                        <button type="button"
                            x-show="!widgetsSearch || '{{ strtolower($widget['label']) }}'.includes(widgetsSearch.toLowerCase())"
                            class="w-full px-2.5 py-1.5 rounded-lg border border-neutral-200 bg-neutral-50 text-xs text-neutral-800 cursor-move hover:bg-neutral-100 flex items-center justify-between"
                            draggable="true" @dragstart="onWidgetDragStart($event, '{{ $widget['type'] }}')">
                            <span class="flex items-center gap-1.5">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-white border border-neutral-200 text-xxs text-neutral-500">
                                    <x-dynamic-component :component="'lucide-' . $widget['icon']" class="w-3 h-3 text-neutral-600" />
                                </span>
                                <span>{{ $widget['label'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-2 pt-2 border-t border-neutral-100 text-xs text-neutral-400">
                    Glissez les widgets dans les zones modifiables.
                </div>
            </aside>

            {{-- Canvas central avec 3 zones --}}
            <main class="flex-1 bg-neutral-50 border border-black/5 rounded-2xl p-3 overflow-auto">
                {{-- Toolbar canvas --}}
                <div class="flex items-center justify-between mb-3 rounded-lg bg-white px-4 py-2.5 shadow-sm border border-gray-200 sticky top-0 z-10">
                    <div class="flex items-center gap-2">
                        <div>
                            <div class="text-xs font-semibold text-neutral-900">Canvas avec contenu natif</div>
                            <div class="text-xxs text-neutral-500">
                                Zones avant et après le contenu automatique
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
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

                {{-- Conteneur responsive pour les zones --}}
                <div class="transition-all duration-300"
                    :class="viewMode === 'mobile' ? 'max-w-sm mx-auto' : 'w-full'">

                {{-- Zone BEFORE NATIVE --}}
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2 px-1">
                        <div class="flex items-center gap-2">
                            <x-lucide-chevron-up class="w-4 h-4 text-neutral-500" />
                            <h3 class="text-sm font-semibold text-neutral-900">Avant le contenu</h3>
                            <span class="px-2 py-0.5 rounded-full bg-neutral-100 text-xxs font-medium text-neutral-600">
                                Modifiable
                            </span>
                        </div>
                        <button type="button" @click="addSection('beforeNative')"
                            class="inline-flex items-center gap-1 rounded-lg bg-white border border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                            + Ajouter une section
                        </button>
                    </div>

                    <div class="space-y-3 min-h-[100px] rounded-xl border-2 border-dashed border-neutral-200 p-3 bg-white/50"
                         x-ref="beforeNativeContainer"
                         @dragover.prevent.stop="onSectionsContainerDragOver($event, 'beforeNative')"
                         @drop.prevent.stop="onSectionDrop($event, 'beforeNative')">
                        <template x-if="data.beforeNative.sections.length === 0">
                            <div class="text-center py-8 text-xs text-neutral-400">
                                <x-lucide-layout class="w-8 h-8 mx-auto mb-2 text-neutral-300" />
                                <p>Glissez des widgets ou ajoutez une section</p>
                                <p class="mt-1">Ces sections s'afficheront avant la grille de produits</p>
                            </div>
                        </template>

                        {{-- Include section template for beforeNative --}}
                        @include('admin::builder.section-template', ['zone' => 'beforeNative'])
                    </div>
                </div>

                {{-- NATIVE CONTENT BLOCK (Non-modifiable) --}}
                @include('admin::builder.native-content-block', ['pageType' => $pageType ?? 'category'])

                {{-- Zone AFTER NATIVE --}}
                <div class="mt-4">
                    <div class="flex items-center justify-between mb-2 px-1">
                        <div class="flex items-center gap-2">
                            <x-lucide-chevron-down class="w-4 h-4 text-neutral-500" />
                            <h3 class="text-sm font-semibold text-neutral-900">Après le contenu</h3>
                            <span class="px-2 py-0.5 rounded-full bg-neutral-100 text-xxs font-medium text-neutral-600">
                                Modifiable
                            </span>
                        </div>
                        <button type="button" @click="addSection('afterNative')"
                            class="inline-flex items-center gap-1 rounded-lg bg-white border border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                            + Ajouter une section
                        </button>
                    </div>

                    <div class="space-y-3 min-h-[100px] rounded-xl border-2 border-dashed border-neutral-200 p-3 bg-white/50"
                         x-ref="afterNativeContainer"
                         @dragover.prevent.stop="onSectionsContainerDragOver($event, 'afterNative')"
                         @drop.prevent.stop="onSectionDrop($event, 'afterNative')">
                        <template x-if="data.afterNative.sections.length === 0">
                            <div class="text-center py-8 text-xs text-neutral-400">
                                <x-lucide-layout class="w-8 h-8 mx-auto mb-2 text-neutral-300" />
                                <p>Glissez des widgets ou ajoutez une section</p>
                                <p class="mt-1">Ces sections s'afficheront après la grille de produits</p>
                            </div>
                        </template>

                        {{-- Include section template for afterNative --}}
                        @include('admin::builder.section-template', ['zone' => 'afterNative'])
                    </div>
                </div>

                </div>{{-- Fin du conteneur responsive --}}
            </main>

            {{-- Sidebar propriétés --}}
            @include('admin::builder.builder-properties')
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['packages/Admin/src/resources/js/preview-modal.js'])
@endpush
