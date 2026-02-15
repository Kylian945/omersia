@extends('admin::layout')

@section('title', 'Nouvelle page E-commerce')
@section('page-title', 'Créer une page E-commerce')

@section('content')
    <form action="{{ route('admin.apparence.ecommerce-pages.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @csrf

        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div>
                    <div class="text-xs font-semibold text-gray-800">Contenu de la page</div>
                    <div class="text-xxxs text-gray-500">
                        Créez une page e-commerce personnalisable (Accueil, Catégorie, Produit).
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Titre de la page</label>
                    <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <input type="text" name="title" required
                            class="flex-1 border-0 px-3 py-1.5 text-xs focus:ring-0"
                            placeholder="Ex: Page d'accueil">
                        <button type="button" data-ai-content-open-modal
                            data-ai-content-context="ecommerce_page"
                            data-ai-content-target="title"
                            data-ai-content-target-label="Titre de page e-commerce"
                            data-ai-content-generate-url="{{ route('admin.ai.generate-content') }}"
                            class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                            aria-label="Générer le titre de page avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    @error('title')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Type de page</label>
                    <select name="type" id="page-type" required
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                        <option value="">Sélectionnez un type</option>
                        <option value="home">Page d'accueil</option>
                        <option value="category">Page catégorie</option>
                        <option value="product">Page produit</option>
                    </select>
                    @error('type')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Champ slug caché pour soumettre la valeur --}}
                <div id="slug-container" style="display: none;">
                    <input type="hidden" name="slug" id="slug-input">
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
                                <option value="{{ $translation->slug }}">
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
                                <option value="{{ $translation->slug }}">
                                    {{ $translation->name }} ({{ $translation->slug }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-xxxs text-gray-500">Le slug sera automatiquement défini selon le produit sélectionné</p>
                </div>

                {{-- Contenu (mode builder externe) --}}
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Contenu</label>

                    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex items-center justify-between">
                        <div class="text-xxxs text-gray-500">
                            Enregistrez la page pour activer le <strong>builder visuel</strong>.
                        </div>

                        <button type="button" disabled
                            class="inline-flex items-center gap-2 rounded-lg bg-gray-200 text-gray-400 px-4 font-semibold py-1.5 text-xs cursor-not-allowed">
                            <x-lucide-blocks class="w-3 h-3"/> Ouvrir le Builder
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne droite --}}
        <div class="space-y-4">
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div class="text-xs font-semibold text-gray-800">Paramètres</div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Langue</label>
                    <select name="locale" required
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                        <option value="fr">Français</option>
                        <option value="en">English</option>
                    </select>
                    @error('locale')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex flex-col gap-2">
                <button type="submit"
                    class="w-full rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                    Créer la page
                </button>
                <a href="{{ route('admin.apparence.ecommerce-pages.index') }}"
                    class="w-full text-center rounded-lg font-semibold border border-gray-200 px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </div>

        @include('admin::components.ai-content-modal')
    </form>
@endsection

@push('scripts')
    @vite(['packages/Admin/src/resources/js/apparence/ecommerce-pages-form.js'])
@endpush
