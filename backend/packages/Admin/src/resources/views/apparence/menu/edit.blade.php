@extends('admin::layout')

@section('title', 'Modifier le menu')
@section('page-title', 'Modifier le menu')

@section('content')
    <div class="mb-3">
        <div class="text-xs font-semibold text-gray-800">
            Modifier l’élément : {{ $menuItem->label }}
        </div>
        <div class="text-xxxs text-gray-500">
            Ajustez le label, le type et la cible de cet élément de navigation.
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4">
        <form action="{{ route('admin.apparence.menus.update', $menuItem) }}" method="POST" class="space-y-3"
            x-data="{ type: '{{ old('type', $menuItem->type) }}' }">
            @csrf
            @method('PUT')

            {{-- Label --}}
            <div>
                <label class="block text-xxxs font-medium text-gray-700 mb-1">
                    Label affiché
                </label>
                <input type="text" name="label" value="{{ old('label', $menuItem->label) }}"
                    class="w-full rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                @error('label')
                    <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                @enderror
            </div>

            {{-- Type + Position --}}
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xxxs font-medium text-gray-700 mb-1">
                        Type de lien
                    </label>
                    <select name="type" x-model="type"
                        class="w-full rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                        <option value="category">Catégorie</option>
                        <option value="cms_page">Page CMS</option>
                        <option value="link">Lien spécifique</option>
                    </select>
                    @error('type')
                        <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-xxxs font-medium text-gray-700 mb-1">
                        Position
                    </label>
                    <input type="number" name="position" min="1" value="{{ old('position', $menuItem->position) }}"
                        class="w-full rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                    @error('position')
                        <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Catégorie --}}
            <div x-show="type === 'category'">
                <label class="block text-xxxs font-medium text-gray-700 mb-1">
                    Catégorie liée
                </label>
                <select name="category_id"
                    class="w-full rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                    <option value="">Choisir une catégorie</option>
                    @foreach ($categories as $category)
                        @php
                            $ct = $category->translation('fr');
                        @endphp
                        <option value="{{ $category->id }}" @selected(old('category_id', $menuItem->category_id) == $category->id)>
                            {{ $ct?->name ?? 'Sans nom (ID ' . $category->id . ')' }}
                        </option>
                    @endforeach


                </select>
                @error('category_id')
                    <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                @enderror
            </div>

            {{-- Page CMS --}}
            <div x-show="type === 'cms_page'">
                <label class="block text-xxxs font-medium text-gray-700 mb-1">
                    Page CMS liée
                </label>
                <select name="cms_page_id"
                    class="w-full rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                    <option value="">Choisir une page CMS</option>
                    @foreach ($cmsPages as $page)
                        @php
                            $translation = $page->translation('fr') ?? $page->translations->first();
                            $title = $translation?->title ?? ('Page ID ' . $page->id);
                            $slug = $translation?->slug ? ' (' . $translation->slug . ')' : '';
                        @endphp
                        <option value="{{ $page->id }}" @selected(old('cms_page_id', $menuItem->cms_page_id) == $page->id)>
                            {{ $title . $slug }}
                        </option>
                    @endforeach
                </select>
                @error('cms_page_id')
                    <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                @enderror
            </div>

            {{-- URL --}}
            <div x-show="type === 'link'">
                <label class="block text-xxxs font-medium text-gray-700 mb-1">
                    Lien spécifique (URL)
                </label>
                <input type="text" name="url" value="{{ old('url', $menuItem->url) }}"
                    class="w-full rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900"
                    placeholder="Ex : /contact, https://votresite.com/promo">
                @error('url')
                    <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                @enderror
            </div>

            {{-- Visibilité --}}
            <div class="flex items-center space-x-2 pt-1 gap-2">
                <input type="checkbox" name="is_active" id="is_active" class="h-3 w-3 rounded border-gray-300"
                    value="1" {{ old('is_active', $menuItem->is_active) ? 'checked' : '' }}>
                <label for="is_active" class="text-xxxs text-gray-700">
                    Afficher dans le menu
                </label>
            </div>

            <div class="pt-2 flex justify-end space-x-2 gap-3">
                <a href="{{ route('admin.apparence.menus.index') }}"
                    class="px-3 py-1.5 rounded-lg border border-gray-200 font-semibold text-xs text-gray-600 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit"
                    class="px-4 py-1.5 rounded-lg bg-[#111827] text-xs text-white font-medium hover:bg-black">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
@endsection
