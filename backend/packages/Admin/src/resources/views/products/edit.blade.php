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
        data-product-id="{{ $product->id }}"
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
                    <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <input type="text" name="name" value="{{ old('name', $t?->name) }}"
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
                    <input type="text" name="slug" value="{{ old('slug', $t?->slug) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Description courte</label>
                    <div class="product-wysiwyg-group flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <div class="min-w-0 flex-1">
                            <textarea name="short_description" class="hidden">{{ old('short_description', $t?->short_description) }}</textarea>
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
                            <textarea name="description" class="hidden">{{ old('description', $t?->description) }}</textarea>
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
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="text-xs font-semibold text-gray-800">Images du produit</div>
                        <div class="text-xxxs text-gray-500">
                            Gérez les images existantes, ajoutez-en de nouvelles et choisissez l’image principale.
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

                {{-- Images existantes --}}
                @if ($product->images->count())
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($product->images as $image)
                            @php
                                $value = 'existing-' . $image->id;
                                $isChecked = old('main_image') ? old('main_image') === $value : $image->is_main;
                                $isAiImage = $image->isAiGenerated();
                            @endphp

                            <div class="relative border rounded-xl overflow-hidden group"
                                data-product-existing-image-id="{{ $image->id }}"
                                data-product-existing-image-url="{{ $image->url }}">
                                <img src="{{ $image->url }}" alt=""
                                    class="w-full h-52 object-cover group-hover:opacity-95">

                                @if ($isAiImage)
                                    <span
                                        class="absolute left-1 top-1 z-10 inline-flex items-center rounded-md bg-black/90 px-1.5 py-0.5 text-xxxs font-semibold uppercase tracking-wide text-white shadow-sm">
                                        IA
                                    </span>
                                @endif

                                <button type="button"
                                    title="Supprimer l'image"
                                    aria-label="Supprimer l'image"
                                    class="absolute right-1 top-1 z-10 inline-flex h-7 w-7 items-center justify-center rounded-md bg-white/90 text-gray-600 shadow-sm hover:bg-white"
                                    @click="$dispatch('open-modal', { name: 'delete-product-image-{{ $image->id }}' })">
                                    <x-lucide-trash-2 class="h-3.5 w-3.5" />
                                </button>

                                <label
                                    class="absolute bottom-1 left-1 right-1 flex items-center justify-center gap-1
                                            bg-black/60 text-white text-xxxs px-1.5 py-0.5 rounded-full cursor-pointer">
                                    <input type="radio" name="main_image" value="{{ $value }}" class="h-2 w-2"
                                        {{ $isChecked ? 'checked' : '' }}>
                                    <span>
                                        {{ $image->is_main ? 'Image principale actuelle' : 'Définir comme principale' }}
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    @foreach ($product->images as $image)
                        <x-admin::modal name="delete-product-image-{{ $image->id }}"
                            :title="'Supprimer cette image ?'" description="Cette action est définitive." size="max-w-md">
                            <p class="text-xs text-gray-600">
                                Confirmer la suppression de l’image produit.
                            </p>

                            <div class="flex justify-end gap-2 pt-3">
                                <button type="button"
                                    class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                                    @click="open = false">
                                    Annuler
                                </button>

                                <form action="{{ route('products.images.destroy', [$product, $image]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-xs font-medium text-white hover:bg-neutral-900">
                                        <x-lucide-trash-2 class="h-3 w-3" />
                                        Confirmer
                                    </button>
                                </form>
                            </div>
                        </x-admin::modal>
                    @endforeach
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
                                            class="absolute left-1 top-1 z-10 inline-flex items-center rounded-md bg-emerald-600/90 px-1.5 py-0.5 text-xxxs font-semibold uppercase tracking-wide text-white shadow-sm">
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

                <input type="hidden" id="main_image_input" name="main_image"
                    value="{{ old('main_image', $currentMain ? 'existing-' . $currentMain->id : '') }}">
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
                        <input type="text" name="meta_title" value="{{ old('meta_title', $t?->meta_title) }}"
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
                            class="flex-1 border-0 px-3 py-1.5 text-xs h-20 resize-none focus:ring-0">{{ old('meta_description', $t?->meta_description) }}</textarea>
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
