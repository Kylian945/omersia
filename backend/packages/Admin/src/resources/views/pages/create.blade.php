@extends('admin::layout')

@section('title', 'Nouvelle page')
@section('page-title', 'Créer une page')

@section('content')
    <form action="{{ route('pages.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @csrf

        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div>
                    <div class="text-xs font-semibold text-gray-800">Contenu de la page</div>
                    <div class="text-xxxs text-gray-500">
                        Créez une page de contenu pour votre boutique (À propos, FAQ, CGV...).
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Titre</label>
                    <input type="text" name="title"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Slug</label>
                    <input type="text" name="slug"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs" placeholder="ex: a-propos"
                        required>
                </div>

                {{-- Contenu (mode builder externe) --}}
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Contenu</label>

                    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex items-center justify-between">
                        <div class="text-xxxs text-gray-500">
                            Enregistrez la page pour activer le <strong>builder visuel</strong>.
                        </div>

                        <button type="button" disabled
                            class="rounded-lg bg-gray-200 text-gray-400 px-4 font-semibold py-1.5 text-xxxs cursor-not-allowed">
                            🧱 Ouvrir le Builder
                        </button>
                    </div>

                    {{-- Champ caché pour compat / old input --}}
                    @php
                        $initialContentJson = old('content_json', '[]');
                    @endphp

                    <input type="hidden" name="content_json" value="{{ $initialContentJson }}">
                </div>

            </div>

            {{-- SEO --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div class="text-xs font-semibold text-gray-800">Référencement</div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Meta title</label>
                    <input type="text" name="meta_title"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Meta description</label>
                    <textarea name="meta_description"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs h-20 resize-none"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="noindex" value="1" class="h-3 w-3 rounded border-gray-300">
                    <span class="text-xs text-gray-700">Demander aux moteurs de recherche d’ignorer cette page</span>
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
                        <option value="page">Page standard</option>
                        <option value="legal">Page légale</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" checked class="h-3 w-3 rounded border-gray-300">
                    <span class="text-xs text-gray-700">Page visible</span>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_home" value="1" class="h-3 w-3 rounded border-gray-300">
                    <span class="text-xs text-gray-700">Utiliser comme page d’accueil</span>
                </div>
            </div>

            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex flex-col gap-2">
                <button
                    class="w-full rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                    Créer la page
                </button>
                <a href="{{ route('pages.index') }}"
                    class="w-full text-center rounded-lg font-semibold border border-gray-200 px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </div>
    </form>
@endsection
