@extends('admin::layout')

@section('title', 'Modifier la catégorie')
@section('page-title', 'Modifier la catégorie')

@section('content')
@php $t = $category->translation('fr'); @endphp

<form action="{{ route('categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
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
                <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                    <input type="text" name="name"
                           value="{{ $t?->name }}"
                           class="flex-1 border-0 px-3 py-1.5 text-xs focus:ring-0"
                           required>
                    <button type="button" data-ai-content-open-modal
                        data-ai-content-context="category"
                        data-ai-content-target="name"
                        data-ai-content-target-label="Nom de catégorie"
                        data-ai-content-generate-url="{{ route('admin.ai.generate-content') }}"
                        class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                        aria-label="Générer le nom de catégorie avec l'IA">
                        <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                    </button>
                </div>
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
                <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                    <textarea name="description"
                              class="flex-1 border-0 px-3 py-1.5 text-xs h-24 resize-none focus:ring-0">{{ $t?->description }}</textarea>
                    <button type="button" data-ai-content-open-modal
                        data-ai-content-context="category"
                        data-ai-content-target="description"
                        data-ai-content-target-label="Description de catégorie"
                        data-ai-content-generate-url="{{ route('admin.ai.generate-content') }}"
                        class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                        aria-label="Générer la description de catégorie avec l'IA">
                        <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div class="text-xs font-semibold text-gray-800">Référencement</div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Meta title</label>
                <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                    <input type="text" name="meta_title"
                           value="{{ $t?->meta_title }}"
                           class="flex-1 border-0 px-3 py-1.5 text-xs focus:ring-0">
                    <button type="button" data-ai-content-open-modal
                        data-ai-content-context="category"
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
                        data-ai-content-context="category"
                        data-ai-content-target="meta_description"
                        data-ai-content-target-label="Meta description"
                        data-ai-content-generate-url="{{ route('admin.ai.generate-content') }}"
                        class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                        aria-label="Générer la meta description avec l'IA">
                        <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                    </button>
                </div>
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

            @if($category->image_url)
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Image actuelle</label>
                    <div class="relative inline-block">
                        <img src="{{ $category->image_url }}" alt="Image de la catégorie"
                             class="w-32 h-32 object-cover rounded-lg border border-gray-200">
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="delete_image" value="1" id="delete_image"
                               class="h-3 w-3 rounded border-gray-300">
                        <label for="delete_image" class="text-xs text-red-600">Supprimer l'image</label>
                    </div>
                </div>
            @endif

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">
                    {{ $category->image_url ? 'Remplacer l\'image' : 'Choisir une image' }}
                </label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                @error('image')
                    <p class="text-xxxs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

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

    @include('admin::components.ai-content-modal')
</form>
@endsection
