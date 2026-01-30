@extends('admin::layout')

@section('title', 'Nouveau menu - ' . ($menu->name ?? ''))
@section('page-title', 'Ajouter un élément au menu : ' . ($menu->name ?? ''))

@section('content')
    <div class="mb-3">
        <div class="text-xs font-semibold text-gray-800">
            Ajouter un élément au menu "{{ $menu->name }} ({{ $menu->location }})"
        </div>
        <div class="text-xxxs text-gray-500">
            Choisissez un label puis liez-le à une catégorie ou à une URL spécifique.
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4">
        <form action="{{ route('admin.apparence.menus.store', ['menu' => $menu->slug]) }}"
              method="POST"
              class="space-y-3"
              x-data="{ type: '{{ old('type', 'category') }}' }">
            @csrf

            <input type="hidden" name="menu" value="{{ $menu->slug }}">

            {{-- Label --}}
            <div>
                <label class="block text-xxxs font-medium text-gray-700 mb-1">
                    Label affiché
                </label>
                <input type="text" name="label" value="{{ old('label') }}"
                    class="w-full rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900"
                    placeholder="Ex : Boutique, Nouveautés, Contact">
                @error('label')
                    <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                @enderror
            </div>

            {{-- Type --}}
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xxxs font-medium text-gray-700 mb-1">
                        Type de lien
                    </label>
                    <select name="type" x-model="type"
                        class="w-full rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900">
                        <option value="category">Catégorie</option>
                        <option value="link">Lien spécifique</option>
                    </select>
                    @error('type')
                        <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Position --}}
                <div>
                    <label class="block text-xxxs font-medium text-gray-700 mb-1">
                        Position
                    </label>
                    <input type="number" name="position" value="{{ old('position', 1) }}"
                        class="w-full rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900"
                        min="1">
                    @error('position')
                        <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Catégorie (si type = category) --}}
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
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                            {{ $ct?->name ?? 'Sans nom (ID ' . $category->id . ')' }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                @enderror
            </div>

            {{-- URL (si type = link) --}}
            <div x-show="type === 'link'">
                <label class="block text-xxxs font-medium text-gray-700 mb-1">
                    Lien spécifique (URL)
                </label>
                <input type="text" name="url" value="{{ old('url') }}"
                    class="w-full rounded-xl border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900"
                    placeholder="Ex : /contact, https://votresite.com/promo">
                @error('url')
                    <div class="text-xxxs text-red-500 mt-0.5">{{ $message }}</div>
                @enderror
            </div>

            {{-- Visibilité --}}
            <div class="flex items-center space-x-2 pt-1 gap-2">
                <input type="checkbox" name="is_active" id="is_active" class="h-3 w-3 rounded border-gray-300"
                    value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                <label for="is_active" class="text-xxxs text-gray-700">
                    Afficher dans le menu
                </label>
            </div>

            <div class="pt-2 flex justify-end space-x-2 gap-3">
                <a href="{{ route('admin.apparence.menus.index') }}"
                    class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit"
                    class="px-4 py-1.5 rounded-lg bg-[#111827] text-xs text-white font-semibold hover:bg-black">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
@endsection
