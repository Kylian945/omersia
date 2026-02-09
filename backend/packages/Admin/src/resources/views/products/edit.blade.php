@extends('admin::layout')

@section('title', 'Modifier le produit')
@section('page-title', 'Modifier le produit')

@section('content')
    @php
        $t = $product->translation('fr');
        $currentMain = $product->images->where('is_main', true)->first();

        $hasVariants = $product->type === 'variant' || ($product->variants ?? collect())->count() > 0;

        $initialType = old('type') ? old('type') : ($hasVariants ? 'variant' : 'simple');

        // Options initiales pour Alpine (si produit à variantes)
        $initialOptions = [];
        $initialVariants = [];

        if ($hasVariants || old('type') === 'variant') {
            $initialOptions = ($product->options ?? collect())
                ->map(function ($o) {
                    return [
                        'name' => $o->name,
                        'valuesText' => $o->values->pluck('value')->join(','), // "S,M,L"
                    ];
                })
                ->values()
                ->all();

            $initialVariants = ($product->variants ?? collect())
                ->map(function ($v) {
                    $values = $v->values
                        ->map(function ($vv) {
                            $optName = $vv->option->name ?? '';
                            return $optName && $vv->value
                                ? $optName . ':' . $vv->value // ex: "Taille:S"
                                : null;
                        })
                        ->filter()
                        ->values()
                        ->all();

                    $label = $v->name;
                    if (!$label && count($values)) {
                        $label = collect($values)
                            ->map(function ($vv) {
                                return explode(':', $vv, 2)[1] ?? '';
                            })
                            ->filter()
                            ->implode(' / ');
                    }

                    return [
                        'id' => $v->id,
                        'label' => $label ?: 'Variante #' . $v->id,
                        'sku' => $v->sku,
                        'is_active' => (bool) $v->is_active,
                        'stock_qty' => $v->stock_qty,
                        'price' => $v->price,
                        'compare_at_price' => $v->compare_at_price,
                        'values' => $values,
                    ];
                })
                ->values()
                ->all();
        }
    @endphp

    <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" x-data="productCreateForm({
        type: '{{ $initialType }}',
        options: @js($initialOptions),
        variants: @js($initialVariants),
    })"
        class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        @csrf
        @method('PUT')

        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Détails --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div>
                    <div class="text-xs font-semibold text-gray-800">Détails du produit</div>
                    <div class="text-xxxs text-gray-500">
                        Mettez à jour le nom, le slug et la description.
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Nom</label>
                    <input type="text" name="name" value="{{ old('name', $t?->name) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $t?->slug) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Description courte</label>
                    <textarea name="short_description"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs h-16 resize-none">{{ old('short_description', $t?->short_description) }}</textarea>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Description</label>
                    <textarea name="description" class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs h-32 resize-y">{{ old('description', $t?->description) }}</textarea>
                </div>
            </div>

            {{-- Images --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="text-xs font-semibold text-gray-800">Images du produit</div>
                        <div class="text-xxxs text-gray-500">
                            Gérez les images existantes, ajoutez-en de nouvelles et choisissez l’image principale.
                        </div>
                    </div>
                </div>

                {{-- Images existantes --}}
                @if ($product->images->count())
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($product->images as $image)
                            @php
                                $value = 'existing-' . $image->id;
                                $isChecked = old('main_image') ? old('main_image') === $value : $image->is_main;
                            @endphp

                            <label class="relative border rounded-xl overflow-hidden cursor-pointer group">
                                <img src="{{ $image->url }}" alt=""
                                    class="w-full h-52 object-cover group-hover:opacity-95">

                                <div
                                    class="absolute bottom-1 left-1 right-1 flex items-center justify-center gap-1
                                            bg-black/60 text-white text-xxxs px-1.5 py-0.5 rounded-full">
                                    <input type="radio" name="main_image" value="{{ $value }}" class="h-2 w-2"
                                        {{ $isChecked ? 'checked' : '' }}>
                                    <span>
                                        {{ $image->is_main ? 'Image principale actuelle' : 'Définir comme principale' }}
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="text-xxxs text-gray-500">
                        Aucune image pour le moment. Ajoutez-en ci-dessous.
                    </div>
                @endif

                {{-- Ajout de nouvelles images --}}
                <div class="mt-3 space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                        Ajouter de nouvelles images
                    </label>
                    <input type="file" id="new-images-input" name="images[]" multiple accept="image/*"
                        class="w-full text-xs text-gray-600">

                    <p class="text-xxxs text-gray-400">
                        Les nouvelles images seront ajoutées à la suite. Vous pouvez aussi les définir directement comme
                        image principale.
                    </p>

                    <div id="new-images-preview" class="mt-2 grid grid-cols-3 gap-2"></div>
                </div>

                <input type="hidden" id="main_image_input" name="main_image"
                    value="{{ old('main_image', $currentMain ? 'existing-' . $currentMain->id : '') }}">
            </div>

            {{-- SEO --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div class="text-xs font-semibold text-gray-800">Référencement</div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Meta title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $t?->meta_title) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Meta description</label>
                    <textarea name="meta_description"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs h-20 resize-none">{{ old('meta_description', $t?->meta_description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Colonne droite --}}
        <div class="space-y-4">
            {{-- Type de produit --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-2">
                <div class="text-xs font-semibold text-gray-800">Type de produit</div>
                <p class="text-xxxs text-gray-500">
                    Basculez entre un produit simple (prix & stock uniques) et un produit avec déclinaisons.
                </p>

                <div class="flex flex-col gap-1 text-xxxs text-gray-700">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="type" value="simple" x-model="productType" @change="onTypeChange"
                            {{ $initialType === 'simple' ? 'checked' : '' }}>
                        <span>Produit simple</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="type" value="variant" x-model="productType" @change="onTypeChange"
                            {{ $initialType === 'variant' ? 'checked' : '' }}>
                        <span>Produit avec déclinaisons</span>
                    </label>
                </div>
            </div>

            {{-- Inventaire produit simple --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3"
                x-show="productType === 'simple'">
                <div class="text-xs font-semibold text-gray-800">Inventaire (produit simple)</div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs"
                        x-bind:required="productType === 'simple'">
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Stock</label>
                    <input type="number" name="stock_qty" value="{{ old('stock_qty', $product->stock_qty) }}"
                        min="0" class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                        Prix TTC (€)
                    </label>
                    <input type="number" name="price" step="0.01" min="0"
                        value="{{ old('price', $product->price ?? 0) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs"
                        x-bind:required="productType === 'simple'">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-700">
                        Prix barré / avant remise (optionnel)
                    </label>
                    <input type="number" name="compare_at_price" step="0.01" min="0"
                        value="{{ old('compare_at_price', $product->compare_at_price) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                    <p class="text-xxxs text-gray-400">
                        Utilisé pour afficher une promotion si supérieur au prix TTC.
                    </p>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                        class="h-3 w-3 rounded border-gray-300">
                    <span class="text-xs text-gray-700">Produit actif</span>
                </div>
            </div>

            {{-- Builder de déclinaisons --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3"
                x-show="productType === 'variant'">
                <div class="text-xs font-semibold text-gray-800">Déclinaisons</div>
                <p class="text-xxxs text-gray-500">
                    Gérez les options (ex : Taille, Couleur) et les variantes associées (SKU, prix, stock).
                    Toute sauvegarde remplace les variantes selon le tableau ci-dessous.
                </p>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $product->is_active) ? 'checked' : '' }}
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
                                <button type="button" class="text-xxxs text-red-500 ml-auto"
                                    @click="removeOption(index)">
                                    Supprimer
                                </button>
                            </div>

                            <div>
                                <input type="text"
                                    class="w-full rounded-lg border border-gray-200 px-2 py-1 text-xxxs"
                                    placeholder="Valeurs séparées par des virgules (ex : S,M,L)" x-model="opt.valuesText">
                            </div>

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
                        class="rounded-full bg-[#111827] px-3 py-1 text-xs text-white hover:bg-black"
                        @click="generateVariants">
                        Générer / régénérer les variantes
                    </button>
                    <p class="text-xxxs text-gray-400 my-2">
                        La régénération recalcule la grille à partir des options. Les variantes non présentes dans la grille
                        seront supprimées lors de la sauvegarde.
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
                                        <input type="text" class="w-full border border-gray-200 rounded px-1 py-0 text-xs"
                                            :name="`variants[${idx}][sku]`" x-model="v.sku">
                                    </td>

                                    <td class="px-2 py-1 text-center">
                                        <input type="checkbox" :name="`variants[${idx}][is_active]`" value="1"
                                            x-model="v.is_active" class="h-3 w-3 rounded">
                                    </td>

                                    <td class="px-2 py-1">
                                        <input type="number" min="0"
                                            class="w-full border border-gray-200 rounded px-1 py-0 text-xs"
                                            :name="`variants[${idx}][stock_qty]`" x-model="v.stock_qty">
                                    </td>

                                    <td class="px-2 py-1">
                                        <input type="number" step="0.01" min="0"
                                            class="w-full border border-gray-200 rounded px-1 py-0 text-xs"
                                            :name="`variants[${idx}][price]`" x-model="v.price">
                                    </td>

                                    <td class="px-2 py-1">
                                        <input type="number" step="0.01" min="0"
                                            class="w-full border border-gray-200 rounded px-1 py-0 text-xs"
                                            :name="`variants[${idx}][compare_at_price]`" x-model="v.compare_at_price">
                                    </td>

                                    <template x-for="(val, vIndex) in (v.values || [])" :key="vIndex">
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
                        Gérez les catégories associées à ce produit.
                    </p>

                    @php
                        $selectedCategories = old(
                            'categories',
                            isset($product) ? $product->categories->pluck('id')->all() : [],
                        );

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
                    $selectedRelated = old(
                        'related_products',
                        isset($product) ? $product->relatedProducts->pluck('id')->all() : [],
                    );
                @endphp

                <div x-data="{ search: '' }" class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <div class="w-full">
                            <div class="flex justify-between items-center">
                                <div class="text-xs font-semibold text-gray-800">Produits associés</div>
                                <span class="pointer-events-none text-xxxs text-gray-400">
                                    {{ $relatedProducts->count() }} produits disponibles
                                </span>
                            </div>
                            <p class="text-xxxs text-gray-500">
                                Gérez les produits liés (suggestions, cross-sell, ensembles...).
                            </p>
                        </div>
                    </div>

                    <div class="relative mt-1">
                        <input type="text" x-model="search" placeholder="Rechercher par nom, SKU ou slug..."
                            class="w-full rounded-full border border-gray-200 px-3 py-1.5 text-xxxs focus:outline-none focus:ring-1 focus:ring-gray-900" />
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
                        Les produits déjà liés sont pré-sélectionnés. La recherche filtre l’affichage sans enlever votre
                        sélection.
                    </p>
                </div>
            @endif

            {{-- Actions principales --}}
            <div id="product-edit-primary-actions"
                class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex flex-col gap-2">
                <button type="submit"
                    class="w-full rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-medium text-white hover:bg-black">
                    Enregistrer les modifications
                </button>
                <a href="{{ route('products.index') }}"
                    class="w-full text-center rounded-lg border border-gray-200 px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </div>

        {{-- Barre sticky "Enregistrer" --}}
        <div id="product-edit-sticky-actions"
            class="hidden fixed inset-x-0 bottom-0 z-40 bg-white/95 backdrop-blur border-t border-black/5 px-4 py-2">
            <div class="max-w-5xl mx-auto flex items-center justify-between gap-3">
                <div class="hidden sm:flex flex-col">
                    <span class="text-xxxs text-gray-500">
                        Modifications non enregistrées
                    </span>
                    <span class="text-xs font-medium text-gray-800">
                        Enregistrez le produit avant de quitter la page.
                    </span>
                </div>

                <div class="flex items-center gap-2 ml-auto">
                    <a href="{{ route('products.index') }}"
                        class="px-4 py-1.5 rounded-lg border border-gray-200 font-semibold text-xs text-gray-700 hover:bg-gray-50">
                        Annuler
                    </a>
                    <button type="submit"
                        class="px-4 py-1.5 rounded-lg bg-[#111827] text-xs font-semibold text-white hover:bg-black">
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection
