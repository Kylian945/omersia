@extends('admin::layout')

@section('title', 'Créer un produit')
@section('page-title', 'Créer un produit')

@section('content')
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" x-data="productCreateForm('{{ old('type', 'simple') }}')"
        data-product-id=""
        class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @csrf

        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Détails --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div>
                    <div class="text-xs font-semibold text-gray-800">Détails du produit</div>
                    <div class="text-xxxs text-gray-500">
                        Renseignez les informations principales du produit.
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Nom</label>
                    <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <input type="text" name="name" value="{{ old('name') }}"
                            class="flex-1 border-0 px-3 py-1.5 text-xs focus:ring-0" required>
                        <button type="button" data-ai-open-modal data-ai-target="name" data-ai-target-label="Nom du produit"
                            data-ai-generate-url="{{ route('products.ai.generate') }}"
                            class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                            aria-label="Générer le nom du produit avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug') }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Description courte</label>
                    <div class="product-wysiwyg-group flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <div class="min-w-0 flex-1">
                            <textarea name="short_description" class="hidden">{{ old('short_description') }}</textarea>
                            <div data-product-wysiwyg="short_description"
                                data-product-wysiwyg-placeholder="Résumé produit (bénéfices, usage, matière...)"
                                data-product-wysiwyg-min-height="90"></div>
                        </div>
                        <button type="button" data-ai-open-modal data-ai-target="short_description"
                            data-ai-target-label="Description courte" data-ai-generate-url="{{ route('products.ai.generate') }}"
                            class="inline-flex w-10 shrink-0 items-start justify-center border-l border-gray-200 pt-2 text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                            aria-label="Générer la description courte avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Description</label>
                    <div class="product-wysiwyg-group flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <div class="min-w-0 flex-1">
                            <textarea name="description" class="hidden">{{ old('description') }}</textarea>
                            <div data-product-wysiwyg="description"
                                data-product-wysiwyg-placeholder="Description complète du produit (détails, arguments, entretien...)"
                                data-product-wysiwyg-min-height="200"></div>
                        </div>
                        <button type="button" data-ai-open-modal data-ai-target="description"
                            data-ai-target-label="Description produit" data-ai-generate-url="{{ route('products.ai.generate') }}"
                            class="inline-flex w-10 shrink-0 items-start justify-center border-l border-gray-200 pt-2 text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                            aria-label="Générer la description produit avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            {{-- Images --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold text-gray-800">Images du produit</div>
                        <div class="text-xxxs text-gray-500">
                            Téléchargez une ou plusieurs images et choisissez l’image principale.
                        </div>
                    </div>
                    <button type="button" data-ai-image-generate-button
                        data-ai-image-generate-url="{{ route('products.ai.generate-image') }}"
                        class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-2 py-1 text-xxxs font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                        aria-label="Générer une image produit avec l'IA">
                        <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        <span>Générer via IA</span>
                    </button>
                </div>

                @php
                    $oldAiImages = old('ai_generated_images', []);
                @endphp

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Images</label>
                    <input type="file" name="images[]" multiple accept="image/*"
                        class="w-full text-xs text-gray-600">
                </div>

                <div id="image-preview-container" class="mt-2 grid grid-cols-3 gap-2 text-xxxs text-gray-500">
                    <p class="col-span-3">
                        Après sélection des fichiers, vous pourrez choisir l’image principale (index 0 par défaut).
                    </p>
                </div>

                <div class="mt-3 space-y-2">
                    <div class="text-xs font-medium text-gray-700">Images IA générées</div>
                    <p class="text-xxxs text-gray-400">
                        La modal permet de choisir une image de référence (optionnel) et d’ajouter un prompt.
                    </p>

                    <div id="ai-generated-images-preview" class="grid grid-cols-3 gap-2">
                        @if (is_array($oldAiImages))
                            @foreach ($oldAiImages as $index => $oldAiImage)
                                @if (is_string($oldAiImage) && str_starts_with($oldAiImage, 'data:image/'))
                                    <label class="relative border rounded-xl overflow-hidden cursor-pointer group">
                                        <img src="{{ $oldAiImage }}" alt="Image IA générée"
                                            class="w-full h-52 object-cover group-hover:opacity-95">
                                        <span
                                            class="absolute left-1 top-1 z-10 inline-flex items-center rounded-md bg-black/90 px-1.5 py-0.5 text-xxxs font-semibold uppercase tracking-wide text-white shadow-sm">
                                            IA
                                        </span>
                                        <div
                                            class="absolute bottom-1 left-1 right-1 flex items-center justify-center gap-1 bg-black/60 text-white text-xxxs px-1.5 py-0.5 rounded-full">
                                            <input type="radio" name="main_image" value="ai-{{ $index }}"
                                                class="h-2 w-2"
                                                {{ old('main_image') === 'ai-' . $index ? 'checked' : '' }}>
                                            <span>
                                                {{ old('main_image') === 'ai-' . $index ? 'Image principale' : 'Définir comme principale' }}
                                            </span>
                                        </div>
                                    </label>
                                @endif
                            @endforeach
                        @endif
                    </div>

                    @error('ai_generated_images')
                        <p class="text-xxs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('ai_generated_images.*')
                        <p class="text-xxs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="ai-generated-images-inputs" class="hidden">
                    @if (is_array($oldAiImages))
                        @foreach ($oldAiImages as $index => $oldAiImage)
                            @if (is_string($oldAiImage) && str_starts_with($oldAiImage, 'data:image/'))
                                <input type="hidden" name="ai_generated_images[{{ $index }}]"
                                    value="{{ $oldAiImage }}" data-ai-generated-input
                                    data-ai-generated-index="{{ $index }}">
                            @endif
                        @endforeach
                    @endif
                </div>

                <input type="hidden" name="main_image" id="main_image_input" value="{{ old('main_image', '0') }}">
            </div>

            {{-- SEO --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div>
                    <div class="text-xs font-semibold text-gray-800">Référencement</div>
                    <p class="text-xxxs text-gray-500">
                        Génération manuelle uniquement, puis validation éditoriale avant enregistrement.
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Meta title</label>
                    <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                            class="flex-1 border-0 px-3 py-1.5 text-xs focus:ring-0">
                        <button type="button" data-ai-open-modal data-ai-target="meta_title"
                            data-ai-target-label="Meta title" data-ai-generate-url="{{ route('products.ai.generate') }}"
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
                            class="flex-1 border-0 px-3 py-1.5 text-xs h-20 resize-none focus:ring-0">{{ old('meta_description') }}</textarea>
                        <button type="button" data-ai-open-modal data-ai-target="meta_description"
                            data-ai-target-label="Meta description" data-ai-generate-url="{{ route('products.ai.generate') }}"
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
            {{-- Type de produit --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-2">
                <div class="text-xs font-semibold text-gray-800">Type de produit</div>
                <p class="text-xxxs text-gray-500">
                    Choisissez entre un produit simple (prix & stock uniques) ou avec déclinaisons.
                </p>

                <div class="flex flex-col gap-1 text-xxxs text-gray-700">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="type" value="simple" x-model="productType" @change="onTypeChange"
                            {{ old('type', 'simple') === 'simple' ? 'checked' : '' }}>
                        <span>Produit simple</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="type" value="variant" x-model="productType" @change="onTypeChange"
                            {{ old('type') === 'variant' ? 'checked' : '' }}>
                        <span>Produit avec déclinaisons (taille, couleur...)</span>
                    </label>
                </div>
            </div>

            {{-- Inventaire / prix pour produit simple --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3"
                x-show="productType === 'simple'">
                <div class="text-xs font-semibold text-gray-800">Inventaire (produit simple)</div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku') }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Stock</label>
                    <input type="number" name="stock_qty" value="{{ old('stock_qty', 0) }}" min="0"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                        Prix TTC (€)
                    </label>
                    <input type="number" name="price" step="0.01" min="0" value="{{ old('price', 0) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs" required>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-700">
                        Prix barré / avant remise (optionnel)
                    </label>
                    <input type="number" name="compare_at_price" step="0.01" min="0"
                        value="{{ old('compare_at_price') }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                    <p class="text-xxxs text-gray-400">
                        S’affiche comme prix barré si supérieur au prix TTC.
                    </p>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}
                        class="h-3 w-3 rounded border-gray-300">
                    <span class="text-xs text-gray-700">Produit actif</span>
                </div>
            </div>

            {{-- Builder de déclinaisons pour produit "variant" --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3"
                x-show="productType === 'variant'">
                <div class="text-xs font-semibold text-gray-800">Déclinaisons</div>
                <p class="text-xxxs text-gray-500">
                    Ajoutez des options (ex : Taille, Couleur), générez automatiquement les variantes
                    avec leurs SKU, prix et stock.
                </p>

                {{-- Info actif global --}}
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}
                        class="h-3 w-3 rounded border-gray-300">
                    <span class="text-xs text-gray-700">Produit actif</span>
                </div>

                {{-- Options --}}
                <div class="space-y-2 mt-2">
                    <template x-for="(opt, index) in options" :key="index">
                        <div class="flex flex-col gap-1 rounded-xl border border-gray-100 px-2 py-2 bg-gray-50/40">
                            <div class="flex items-center gap-2">
                                <input type="text" class="w-32 rounded-lg border border-gray-200 px-2 py-1 text-xxxs"
                                    placeholder="Nom de l’option (ex : Taille)" x-model="opt.name">
                                <button type="button" class="text-xxxs text-gray-500 ml-auto"
                                    @click="removeOption(index)">
                                    Supprimer
                                </button>
                            </div>

                            <div>
                                <input type="text"
                                    class="w-full rounded-lg border border-gray-200 px-2 py-1 text-xxxs"
                                    placeholder="Valeurs séparées par des virgules (ex : S,M,L)" x-model="opt.valuesText">
                            </div>

                            {{-- Inputs cachés pour le backend --}}
                            <input type="hidden" :name="`options[${index}][name]`" x-model="opt.name">
                            <template x-for="(val, vIndex) in splitValues(opt.valuesText)" :key="vIndex">
                                <input type="hidden" :name="`options[${index}][values][${vIndex}]`"
                                    :value="val">
                            </template>
                        </div>
                    </template>

                    <button type="button" class="text-xxxs text-gray-700 underline" @click="addOption()">
                        + Ajouter une option
                    </button>
                </div>

                {{-- Génération des variantes --}}
                <div class="mt-3">
                    <button type="button"
                        class="rounded-full bg-[#111827] px-3 py-1 text-xxxs text-white hover:bg-black"
                        @click="generateVariants">
                        Générer / régénérer les variantes
                    </button>
                    <p class="text-xxxs text-gray-400 mt-1">
                        Les variantes existantes de ce formulaire seront remplacées lors d’une régénération.
                    </p>
                </div>

                {{-- Tableau des variantes --}}
                <div class="mt-3 max-h-60 overflow-y-auto" x-show="variants.length">
                    <table class="w-full text-xxxs text-gray-700">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-2 py-1 text-left">Variante</th>
                                <th class="px-2 py-1 text-left">SKU</th>
                                <th class="px-2 py-1">Actif</th>
                                <th class="px-2 py-1">Stock</th>
                                <th class="px-2 py-1">Prix</th>
                                <th class="px-2 py-1">Prix barré</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(v, idx) in variants" :key="idx">
                                <tr class="border-t">
                                    <td class="px-2 py-1" x-text="v.label"></td>

                                    <td class="px-2 py-1">
                                        <input type="text" class="w-full border border-gray-200 rounded px-1 py-0.5"
                                            :name="`variants[${idx}][sku]`" x-model="v.sku">
                                    </td>

                                    <td class="px-2 py-1 text-center">
                                        <input type="checkbox" :name="`variants[${idx}][is_active]`" value="1"
                                            x-model="v.is_active">
                                    </td>

                                    <td class="px-2 py-1">
                                        <input type="number" min="0"
                                            class="w-full border border-gray-200 rounded px-1 py-0.5"
                                            :name="`variants[${idx}][stock_qty]`" x-model="v.stock_qty">
                                    </td>

                                    <td class="px-2 py-1">
                                        <input type="number" step="0.01" min="0"
                                            class="w-full border border-gray-200 rounded px-1 py-0.5"
                                            :name="`variants[${idx}][price]`" x-model="v.price">
                                    </td>

                                    <td class="px-2 py-1">
                                        <input type="number" step="0.01" min="0"
                                            class="w-full border border-gray-200 rounded px-1 py-0.5"
                                            :name="`variants[${idx}][compare_at_price]`" x-model="v.compare_at_price">
                                    </td>

                                    {{-- Champs cachés pour les valeurs d’options: optName:value --}}
                                    <template x-for="(val, vIndex) in v.values" :key="vIndex">
                                        <input type="hidden" :name="`variants[${idx}][values][${vIndex}]`"
                                            :value="val">
                                    </template>

                                    <input type="hidden" :name="`variants[${idx}][label]`" x-model="v.label">
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Catégories --}}
            @if (isset($categories) && $categories->count())
                <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                    <div class="text-xs font-semibold text-gray-800">Catégories</div>
                    <p class="text-xxxs text-gray-500">
                        Associez ce produit à une ou plusieurs catégories.
                    </p>

                    @php
                        $selectedCategories = old('categories', []);
                        $grouped = $categories->groupBy('parent_id');

                        $renderCategoryTree = function ($parentId, $level = 0) use (
                            &$renderCategoryTree,
                            $grouped,
                            $selectedCategories,
                        ) {
                            if (!isset($grouped[$parentId])) {
                                return;
                            }

                            foreach ($grouped[$parentId] as $category) {
                                $ct = $category->translation('fr');
                                $isChecked = in_array($category->id, $selectedCategories);
                                $indent = 4 + $level * 12;

                                echo '<label class="flex items-center gap-2 text-xxxs text-gray-700" style="padding-left:' .
                                    $indent .
                                    'px">';
                                echo '<input type="checkbox" name="categories[]" value="' .
                                    $category->id .
                                    '" class="h-3 w-3 rounded border-gray-300"' .
                                    ($isChecked ? ' checked' : '') .
                                    '>';
                                echo '<span>' . e($ct?->name ?? 'Catégorie #' . $category->id) . '</span>';
                                echo '</label>';

                                $renderCategoryTree($category->id, $level + 1);
                            }
                        };
                    @endphp

                    <div class="space-y-1 max-h-40 overflow-y-auto px-1 py-1">
                        {!! $renderCategoryTree(null, 0) !!}
                    </div>
                </div>
            @endif

            {{-- Produits associés --}}
            @if (isset($relatedProducts) && $relatedProducts->count())
                @php
                    $selectedRelated = old('related_products', []);
                @endphp

                <div x-data="{ search: '' }" class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <div class="text-xs font-semibold text-gray-800">Produits associés</div>
                            <p class="text-xxxs text-gray-500">
                                Recherchez et associez des produits liés (suggestions, cross-sell, ensembles...).
                            </p>
                        </div>
                    </div>

                    <div class="relative mt-1">
                        <input type="text" x-model="search" placeholder="Rechercher par nom, SKU ou slug..."
                            class="w-full rounded-full border border-gray-200 px-3 py-1.5 text-xxxs focus:outline-none focus:ring-1 focus:ring-gray-900" />
                        <span class="pointer-events-none absolute right-3 top-1.5 text-xxxs text-gray-400">
                            {{ $relatedProducts->count() }} produits
                        </span>
                    </div>

                    <div class="mt-2 max-h-48 overflow-y-auto space-y-1 pr-1">
                        @foreach ($relatedProducts as $related)
                            @php
                                $rt = $related->translation('fr');
                                $label = trim(
                                    ($rt?->name ?? 'Produit #' . $related->id) .
                                        ' ' .
                                        ($related->sku ? '• ' . $related->sku : '') .
                                        ' ' .
                                        ($rt?->slug ? '• ' . $rt->slug : ''),
                                );
                                $labelKey = \Illuminate\Support\Str::lower($label);
                            @endphp

                            <label
                                class="flex items-center gap-2 rounded-lg px-2 py-1 text-xxxs text-gray-700 hover:bg-gray-50 cursor-pointer"
                                x-show="search === '' || '{{ $labelKey }}'.includes(search.toLowerCase())">
                                <input type="checkbox" name="related_products[]" value="{{ $related->id }}"
                                    class="h-3 w-3 rounded border-gray-300" @checked(in_array($related->id, $selectedRelated))>
                                <div class="flex flex-col leading-tight">
                                    <span class="font-medium text-gray-900">
                                        {{ $rt?->name ?? 'Produit #' . $related->id }}
                                    </span>
                                    <span class="text-xxxs text-gray-500">
                                        @if ($related->sku)
                                            SKU: {{ $related->sku }}
                                        @endif
                                        @if ($rt?->slug)
                                            • {{ $rt->slug }}
                                        @endif
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <p class="text-xxxs text-gray-400">
                        Sélection multiple via les cases à cocher. La recherche filtre l’affichage sans perdre
                        votre sélection.
                    </p>
                </div>
            @endif

            {{-- Actions principales --}}
            <div id="product-primary-actions"
                class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex flex-col gap-2">
                <button type="submit"
                    class="w-full rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-medium text-white hover:bg-black">
                    Créer le produit
                </button>
                <a href="{{ route('products.index') }}"
                    class="w-full text-center rounded-lg border border-gray-200 px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </div>

        {{-- Barre sticky "Enregistrer" --}}
        <div id="product-sticky-actions"
            class="hidden fixed inset-x-0 bottom-0 z-40 bg-white/95 backdrop-blur border-t border-black/5 px-4 py-2">
            <div class="max-w-5xl mx-auto flex items-center justify-between gap-3">
                <div class="hidden sm:flex flex-col">
                    <span class="text-xxxs text-gray-500">
                        Vous avez des modifications en cours
                    </span>
                    <span class="text-xs font-medium text-gray-800">
                        Enregistrez le produit quand vous êtes prêt.
                    </span>
                </div>

                <div class="flex items-center gap-2 ml-auto">
                    <a href="{{ route('products.index') }}"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        Annuler
                    </a>
                    <button type="submit"
                        class="px-4 py-1.5 rounded-lg bg-[#111827] text-xs font-semibold text-white hover:bg-black">
                        Créer le produit
                    </button>
                </div>
            </div>
        </div>

        <div id="product-ai-prompt-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 backdrop-blur-sm px-4">
            <div class="w-full max-w-lg rounded-2xl border border-black/5 bg-white p-4 shadow-xl">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="text-xs font-semibold text-gray-900">Génération IA du produit</div>
                        <div class="text-xxxs text-gray-500">
                            Décris le résultat attendu (angle marketing, mots-clés SEO, ton, contraintes).
                        </div>
                    </div>
                    <button type="button" id="product-ai-modal-close-button"
                        class="text-xs text-gray-400 hover:text-gray-700" aria-label="Fermer la modal IA">
                        ✕
                    </button>
                </div>

                <div class="mt-3 space-y-2">
                    <p class="text-xxxs text-gray-500">
                        Champ ciblé: <span id="product-ai-target-label" class="font-semibold text-gray-700">-</span>
                    </p>
                    <label for="product-ai-prompt-input" class="block text-xs font-medium text-gray-700">
                        Prompt de génération
                    </label>
                    <textarea id="product-ai-prompt-input" rows="5" maxlength="2000"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y"
                        placeholder="Ex: Rédige un titre orienté conversion et une description premium, en mettant en avant la durabilité du produit."></textarea>
                    <p class="text-xxxs text-gray-500">
                        Ce prompt est combiné avec les settings IA globaux et les données actuelles du produit.
                    </p>
                    <p id="product-ai-modal-error" class="hidden text-xxs text-red-600"></p>
                </div>

                <div class="mt-3 flex items-center justify-end gap-2">
                    <button type="button" id="product-ai-modal-cancel-button"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="button" id="product-ai-modal-submit-button"
                        class="rounded-lg bg-neutral-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-black disabled:opacity-60 disabled:cursor-not-allowed">
                        Lancer la génération
                    </button>
                </div>
            </div>
        </div>

        <div id="product-ai-image-prompt-modal"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 backdrop-blur-sm px-4">
            <div class="w-full max-w-lg rounded-2xl border border-black/5 bg-white p-4 shadow-xl">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="text-xs font-semibold text-gray-900">Génération d’image IA</div>
                        <div class="text-xxxs text-gray-500">
                            Si des images existent, sélectionne une référence, puis saisis un prompt précis.
                        </div>
                    </div>
                    <button type="button" id="product-ai-image-modal-close-button"
                        class="text-xs text-gray-400 hover:text-gray-700" aria-label="Fermer la modal image IA">
                        ✕
                    </button>
                </div>

                <div class="mt-3 space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                        Image de référence
                    </label>
                    <div id="product-ai-image-reference-options" class="grid grid-cols-2 gap-2 sm:grid-cols-3"></div>
                    <p id="product-ai-image-reference-empty" class="hidden text-xxxs text-gray-500">
                        Aucune image existante pour ce produit.
                    </p>
                </div>

                <div class="mt-3 space-y-2">
                    <label for="product-ai-image-prompt-input" class="block text-xs font-medium text-gray-700">
                        Prompt de génération image
                    </label>
                    <textarea id="product-ai-image-prompt-input" rows="5" maxlength="1500"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y"
                        placeholder="Ex: Photo produit premium sur fond neutre, éclairage studio, angle 3/4, rendu réaliste."></textarea>
                    <p class="text-xxxs text-gray-500">
                        Seul ce prompt est utilisé pour la génération.
                    </p>
                    <p id="product-ai-image-modal-error" class="hidden text-xxs text-red-600"></p>
                    <div id="product-ai-image-loading" class="hidden items-center gap-1.5 text-xxxs text-gray-600">
                        <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-gray-300 border-t-gray-700"></span>
                        <span>Génération d'image en cours...</span>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-end gap-2">
                    <button type="button" id="product-ai-image-modal-cancel-button"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="button" id="product-ai-image-modal-submit-button"
                        class="rounded-lg bg-neutral-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-black disabled:opacity-60 disabled:cursor-not-allowed">
                        Générer l’image
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
