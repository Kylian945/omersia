@extends('admin::layout')

@section('title', 'Nouvelle catégorie')
@section('page-title', 'Créer une catégorie')

@section('content')
<form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    @csrf

    {{-- Colonne principale --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Infos de base --}}
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div>
                <div class="text-xs font-semibold text-gray-800">Informations de la catégorie</div>
                <div class="text-xxxs text-gray-500">
                    Donnez un nom clair et un slug optimisé pour le SEO.
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Nom</label>
                <input type="text" name="name"
                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
                       required>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Slug</label>
                <input type="text" name="slug"
                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs"
                       placeholder="ex: smartphones"
                       required>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Description</label>
                <textarea name="description"
                          class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs h-24 resize-none"></textarea>
            </div>
        </div>

        {{-- SEO --}}
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div>
                <div class="text-xs font-semibold text-gray-800">Référencement</div>
                <div class="text-xxxs text-gray-500">
                    Personnalisez les métadonnées pour améliorer la visibilité de cette catégorie.
                </div>
            </div>

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
        </div>
    </div>

    {{-- Colonne droite --}}
    <div class="space-y-4">
        {{-- Image --}}
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div>
                <div class="text-xs font-semibold text-gray-800">Image</div>
                <div class="text-xxxs text-gray-500">
                    Image de la catégorie (jpeg, png, jpg, gif, webp - max 2 Mo)
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Choisir une image</label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                @error('image')
                    <p class="text-xxxs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Organisation --}}
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div class="text-xs font-semibold text-gray-800">Organisation</div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Catégorie parente</label>
                <select name="parent_id"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                    <option value="">Aucune</option>
                    @foreach($parents as $parent)
                        @php $pt = $parent->translation('fr'); @endphp
                        <option value="{{ $parent->id }}">
                            {{ $pt?->name ?? 'Catégorie #'.$parent->id }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Position</label>
                <input type="number" name="position" value="0"
                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" value="1" checked
                       class="h-3 w-3 rounded border-gray-300">
                <span class="text-xs text-gray-700">Catégorie active</span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex flex-col gap-2">
            <button
                class="w-full rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                Créer la catégorie
            </button>
            <a href="{{ route('categories.index') }}"
               class="w-full text-center rounded-lg font-semibold border border-gray-200 px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                Annuler
            </a>
        </div>
    </div>
</form>
@endsection
