@extends('admin::layout')

@section('title', 'Modifier la catégorie')
@section('page-title', 'Modifier la catégorie')

@section('content')
@php $t = $category->translation('fr'); @endphp

<form action="{{ route('categories.update', $category) }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    @csrf
    @method('PUT')

    {{-- Colonne principale --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div>
                <div class="text-xs font-semibold text-gray-800">Informations de la catégorie</div>
                <div class="text-xxxs text-gray-500">
                    Ajustez le nom, le slug et la description.
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Nom</label>
                <input type="text" name="name"
                       value="{{ $t?->name }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs"
                       required>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Slug</label>
                <input type="text" name="slug"
                       value="{{ $t?->slug }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs"
                       required>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Description</label>
                <textarea name="description"
                          class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs h-24 resize-none">{{ $t?->description }}</textarea>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div class="text-xs font-semibold text-gray-800">Référencement</div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Meta title</label>
                <input type="text" name="meta_title"
                       value="{{ $t?->meta_title }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Meta description</label>
                <textarea name="meta_description"
                          class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs h-20 resize-none">{{ $t?->meta_description }}</textarea>
            </div>
        </div>
    </div>

    {{-- Colonne droite --}}
    <div class="space-y-4">
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div class="text-xs font-semibold text-gray-800">Organisation</div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Catégorie parente</label>
                <select name="parent_id"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                    <option value="">Aucune</option>
                    @foreach($parents as $parent)
                        @php $pt = $parent->translation('fr'); @endphp
                        <option value="{{ $parent->id }}" {{ $category->parent_id === $parent->id ? 'selected' : '' }}>
                            {{ $pt?->name ?? 'Catégorie #'.$parent->id }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Position</label>
                <input type="number" name="position"
                       value="{{ $category->position }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" value="1"
                       {{ $category->is_active ? 'checked' : '' }}
                       class="h-3 w-3 rounded border-gray-300">
                <span class="text-xs text-gray-700">Catégorie active</span>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex flex-col gap-2">
            <button
                class="w-full rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                Enregistrer les modifications
            </button>
            <a href="{{ route('categories.index') }}"
               class="w-full text-center rounded-lg border border-gray-200 font-semibold px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                Annuler
            </a>
        </div>
    </div>
</form>
@endsection
