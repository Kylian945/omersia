@extends('admin::layout')

@section('title', 'Modifier la page')
@section('page-title', 'Modifier la page')

@section('content')
    @php $t = $page->translations->first(); @endphp

    <form action="{{ route('admin.apparence.ecommerce-pages.update', $page) }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
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
                    <label class="block text-xs font-medium text-gray-700">Type de page</label>
                    <select name="type" id="page-type" required class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                        <option value="home" {{ $page->type === 'home' ? 'selected' : '' }}>Page d'accueil</option>
                        <option value="category" {{ $page->type === 'category' ? 'selected' : '' }}>Page catégorie</option>
                        <option value="product" {{ $page->type === 'product' ? 'selected' : '' }}>Page produit</option>
                    </select>
                    @error('type')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Champ slug caché pour soumettre la valeur --}}
                <div id="slug-container" style="display: none;">
                    <input type="hidden" name="slug" id="slug-input" value="{{ $page->slug }}">
                </div>

                {{-- Affichage informatif du slug pour page d'accueil --}}
                <div id="slug-info-container" class="space-y-2" style="display: none;">
                    <label class="block text-xs font-medium text-gray-700">Slug</label>
                    <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-600">
                        accueil
                    </div>
                    <p class="text-xxxs text-gray-500">Le slug est automatiquement défini pour la page d'accueil</p>
                    @error('slug')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="category-select-container" class="space-y-2" style="display: none;">
                    <label class="block text-xs font-medium text-gray-700">Catégorie</label>
                    <select id="category-select"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                        <option value="">Sélectionnez une catégorie</option>
                        @foreach($categories as $category)
                            @php
                                $translation = $category->translations->first();
                            @endphp
                            @if($translation)
                                <option value="{{ $translation->slug }}" {{ $page->slug === $translation->slug ? 'selected' : '' }}>
                                    {{ $translation->name }} ({{ $translation->slug }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-xxxs text-gray-500">Le slug sera automatiquement défini selon la catégorie sélectionnée</p>
                </div>

                <div id="product-select-container" class="space-y-2" style="display: none;">
                    <label class="block text-xs font-medium text-gray-700">Produit</label>
                    <select id="product-select"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                        <option value="">Sélectionnez un produit</option>
                        @foreach($products as $product)
                            @php
                                $translation = $product->translations->first();
                            @endphp
                            @if($translation)
                                <option value="{{ $translation->slug }}" {{ $page->slug === $translation->slug ? 'selected' : '' }}>
                                    {{ $translation->name }} ({{ $translation->slug }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-xxxs text-gray-500">Le slug sera automatiquement défini selon le produit sélectionné</p>
                </div>

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

                        <a href="{{ route('admin.apparence.ecommerce-pages.builder', ['page' => $page->id, 'locale' => 'fr']) }}"
                            class="rounded-lg bg-gray-900 text-white px-4 py-1.5 font-semibold text-xs hover:bg-black flex items-center gap-1.5">
                            <x-lucide-blocks class="w-4 h-4"/> Ouvrir le Builder
                        </a>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div class="text-xs font-semibold text-gray-800">Référencement</div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Meta title</label>
                    <input type="text" name="meta_title" value="{{ $t?->meta_title }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Meta description</label>
                    <textarea name="meta_description"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs h-20 resize-none">{{ $t?->meta_description }}</textarea>
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

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" {{ $page->is_active ? 'checked' : '' }}
                        class="h-3 w-3 rounded border-gray-300">
                    <span class="text-xs text-gray-700">Page visible</span>
                </div>
            </div>

            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex flex-col gap-2">
                <button
                    class="w-full rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                    Enregistrer les modifications
                </button>
                <a href="{{ route('admin.apparence.ecommerce-pages.index') }}"
                    class="w-full text-center rounded-lg border border-gray-200 font-semibold px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    @vite(['packages/Admin/src/resources/js/apparence/ecommerce-pages-form.js'])
@endpush
