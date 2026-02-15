@extends('admin::layout')

@section('title', 'Modifier la page')
@section('page-title', 'Modifier la page')

@section('content')
    @php $t = $page->translation('fr'); @endphp

    <form action="{{ route('pages.update', $page) }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @csrf
        @method('PUT')

        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div>
                    <div class="text-xs font-semibold text-gray-800">Contenu de la page</div>
                    <div class="text-xxxs text-gray-500">
                        Modifiez le contenu affiché sur le storefront.
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Titre</label>
                    <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <input type="text" name="title" value="{{ $t?->title }}"
                            class="flex-1 border-0 px-3 py-1.5 text-xs focus:ring-0" required>
                        <button type="button" data-ai-content-open-modal
                            data-ai-content-context="cms_page"
                            data-ai-content-target="title"
                            data-ai-content-target-label="Titre de page"
                            data-ai-content-generate-url="{{ route('admin.ai.generate-content') }}"
                            class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                            aria-label="Générer le titre avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Slug</label>
                    <input type="text" name="slug" value="{{ $t?->slug }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs" required>
                </div>

                @php
                    $t = $page->translation('fr');
                    $initialContentJson = old('content_json', $t?->content_json ? json_encode($t->content_json) : '[]');
                @endphp

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Contenu</label>

                    <div
                        class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex items-center justify-between gap-3">
                        <div class="text-xxxs text-gray-500">
                            Gérez le contenu de cette page avec le <strong>builder visuel</strong> dédié.
                            <div class="text-xxxs text-gray-400">
                                Le contenu actuel est stocké dans le builder (content_json).
                            </div>
                        </div>

                        <a href="{{ route('pages.builder', ['page' => $page->id, 'locale' => 'fr']) }}"
                            class="rounded-lg bg-gray-900 text-white px-4 py-1.5 font-semibold text-xs hover:bg-black flex items-center gap-1.5">
                            <x-lucide-blocks class="w-4 h-4"/> Ouvrir le Builder
                        </a>
                    </div>

                    {{-- Champ caché pour rester compatible avec la logique existante --}}
                    <input type="hidden" name="content_json" value="{{ $initialContentJson }}">
                </div>


            </div>

            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div class="text-xs font-semibold text-gray-800">Référencement</div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Meta title</label>
                    <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <input type="text" name="meta_title" value="{{ $t?->meta_title }}"
                            class="flex-1 border-0 px-3 py-1.5 text-xs focus:ring-0">
                        <button type="button" data-ai-content-open-modal
                            data-ai-content-context="cms_page"
                            data-ai-content-target="meta_title"
                            data-ai-content-target-label="Meta title"
                            data-ai-content-generate-url="{{ route('admin.ai.generate-content') }}"
                            class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                            aria-label="Générer le meta title avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Meta description</label>
                    <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <textarea name="meta_description"
                            class="flex-1 border-0 px-3 py-1.5 text-xs h-20 resize-none focus:ring-0">{{ $t?->meta_description }}</textarea>
                        <button type="button" data-ai-content-open-modal
                            data-ai-content-context="cms_page"
                            data-ai-content-target="meta_description"
                            data-ai-content-target-label="Meta description"
                            data-ai-content-generate-url="{{ route('admin.ai.generate-content') }}"
                            class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                            aria-label="Générer la meta description avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="noindex" value="1" {{ $t?->noindex ? 'checked' : '' }}
                        class="h-3 w-3 rounded border-gray-300">
                    <span class="text-xs text-gray-700">Noindex</span>
                </div>
            </div>
        </div>

        {{-- Colonne droite --}}
        <div class="space-y-4">
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div class="text-xs font-semibold text-gray-800">Paramètres</div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Type</label>
                    <select name="type" class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                        <option value="page" {{ $page->type === 'page' ? 'selected' : '' }}>Page standard</option>
                        <option value="legal" {{ $page->type === 'legal' ? 'selected' : '' }}>Page légale</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" {{ $page->is_active ? 'checked' : '' }}
                        class="h-3 w-3 rounded border-gray-300">
                    <span class="text-xs text-gray-700">Page visible</span>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_home" value="1" {{ $page->is_home ? 'checked' : '' }}
                        class="h-3 w-3 rounded border-gray-300">
                    <span class="text-xs text-gray-700">Utiliser comme page d’accueil</span>
                </div>
            </div>

            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex flex-col gap-2">
                <button
                    class="w-full rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                    Enregistrer les modifications
                </button>
                <a href="{{ route('pages.index') }}"
                    class="w-full text-center rounded-lg border border-gray-200 font-semibold px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </div>

        @include('admin::components.ai-content-modal')
    </form>
@endsection
