@extends('admin::layout')

@section('title', 'Créer un produit')
@section('page-title', 'Créer un produit')

@section('content')
    @php
        $product = (object) [
            'id' => null,
            'type' => old('type', 'simple'),
            'is_active' => (bool) old('is_active', 1),
            'sku' => old('sku'),
            'stock_qty' => old('stock_qty', 0),
            'price' => old('price', 0),
            'compare_at_price' => old('compare_at_price'),
            'images' => collect(),
            'variants' => collect(),
            'options' => collect(),
            'categories' => collect(),
            'relatedProducts' => collect(),
        ];

        $t = null;
        $currentMain = null;
        $previousProduct = $previousProduct ?? null;
        $nextProduct = $nextProduct ?? null;

        $hasVariants = $product->type === 'variant';

        $initialType = old('type', $hasVariants ? 'variant' : 'simple');
        $initialName = old('name', '');
        $initialIsActive = (bool) old('is_active', 1);

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
                        'image_key' => $v->product_image_id ? 'existing-' . $v->product_image_id : '',
                        'values' => $values,
                    ];
                })
                ->values()
                ->all();
        }

        $hasErrorFor = static function (array $keys) use ($errors): bool {
            foreach ($keys as $key) {
                if ($errors->has($key)) {
                    return true;
                }
            }

            return false;
        };

        $initialActiveTab = old('ui_tab', '');
        if ($initialActiveTab === '') {
            if ($hasErrorFor(['meta_title', 'meta_description'])) {
                $initialActiveTab = 'seo';
            } elseif ($hasErrorFor(['categories', 'categories.*', 'related_products', 'related_products.*'])) {
                $initialActiveTab = 'organization';
            } elseif (
                $hasErrorFor([
                    'type',
                    'sku',
                    'stock_qty',
                    'price',
                    'compare_at_price',
                    'is_active',
                    'options',
                    'options.*',
                    'variants',
                    'variants.*',
                ])
            ) {
                $initialActiveTab = $initialType === 'variant' ? 'variants' : 'offer';
            } elseif (
                $hasErrorFor(['images', 'images.*', 'main_image', 'ai_generated_images', 'ai_generated_images.*'])
            ) {
                $initialActiveTab = 'media';
            }
        }

        if ($initialActiveTab === '') {
            $initialActiveTab = 'general';
        }

        $initialActiveVariantTab = old('ui_variant_tab', '');
        if ($initialActiveVariantTab === '') {
            if ($hasErrorFor(['variants.*.image_key'])) {
                $initialActiveVariantTab = 'images';
            } elseif (
                $hasErrorFor([
                    'variants.*.sku',
                    'variants.*.stock_qty',
                    'variants.*.price',
                    'variants.*.compare_at_price',
                    'variants.*.is_active',
                ])
            ) {
                $initialActiveVariantTab = 'pricing';
            } elseif ($hasErrorFor(['variants.*.values', 'variants.*.label'])) {
                $initialActiveVariantTab = 'combinations';
            } else {
                $initialActiveVariantTab = 'options';
            }
        }
    @endphp

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" x-data="productCreateForm({
        type: @js($initialType),
        isActive: @js($initialIsActive),
        productName: @js($initialName),
        activeTab: @js($initialActiveTab),
        activeVariantTab: @js($initialActiveVariantTab),
        options: @js(old('options', $initialOptions)),
        variants: @js(old('variants', $initialVariants)),
    })"
        data-product-id="" class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        @csrf
        <input type="hidden" name="ui_tab" x-model="activeTab">
        <input type="hidden" name="ui_variant_tab" x-model="activeVariantTab">
        <input type="hidden" name="is_active" :value="productIsActive ? 1 : 0">

        <div class="lg:col-span-3 rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <div class="truncate text-base font-semibold text-gray-900"
                            x-text="productName !== '' ? productName : 'Nouveau produit'"></div>
                        <div class="inline-flex items-center gap-2 rounded-lg bg-white px-2 py-1">

                            <select name="type" x-model="productType" @change="onTypeChange"
                                class="rounded-md border border-gray-200 pl-2 pr-8 py-1 text-xxs font-medium text-gray-800 focus:border-gray-400 focus:outline-none focus:ring-0">
                                <option value="simple">Simple</option>
                                <option value="variant">Déclinaison</option>
                            </select>
                        </div>
                        <div class="inline-flex items-center gap-2 bg-white px-2 py-1">

                            <button type="button" @click="productIsActive = !productIsActive"
                                :class="productIsActive ? 'bg-emerald-500' : 'bg-gray-300'"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors">
                                <span :class="productIsActive ? 'translate-x-5' : 'translate-x-1'"
                                    class="inline-block h-3 w-3 rounded-full bg-white transition-transform"></span>
                            </button>
                            <span class="rounded-full px-2 py-0.5 text-xxs font-semibold"
                                :class="productIsActive ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700'"
                                x-text="productIsActive ? 'Actif' : 'Inactif'"></span>
                        </div>
                    </div>

                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($previousProduct)
                        <a href="{{ route('products.edit', $previousProduct) }}"
                            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xxs font-medium text-gray-700 hover:bg-gray-50">
                            Produit précédent
                        </a>
                    @else
                        <button type="button" disabled
                            class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-xxs font-medium text-gray-400 cursor-not-allowed">
                            Produit précédent
                        </button>
                    @endif

                    @if ($nextProduct)
                        <a href="{{ route('products.edit', $nextProduct) }}"
                            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xxs font-medium text-gray-700 hover:bg-gray-50">
                            Produit suivant
                        </a>
                    @else
                        <button type="button" disabled
                            class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-xxs font-medium text-gray-400 cursor-not-allowed">
                            Produit suivant
                        </button>
                    @endif
                </div>
            </div>

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2">
                    <div class="text-xxs font-semibold text-red-700">Des champs doivent être corrigés</div>
                    <ul class="mt-1 space-y-0.5 text-xxs text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-3">
                <div class="grid grid-cols-2 gap-1.5 text-xxs text-gray-600 md:grid-cols-3 lg:grid-cols-6">
                    <button type="button" @click="setActiveTab('general')"
                        :class="isTabActive('general') ?
                            'border-gray-900 bg-gray-900 text-white' :
                            'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                        class="rounded-lg border px-2 py-1 text-center transition">
                        Général
                    </button>
                    <button type="button" @click="setActiveTab('media')"
                        :class="isTabActive('media') ?
                            'border-gray-900 bg-gray-900 text-white' :
                            'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                        class="rounded-lg border px-2 py-1 text-center transition">
                        Médias
                    </button>
                    <button type="button" @click="setActiveTab('offer')" x-show="productType === 'simple'" x-cloak
                        :class="isTabActive('offer') ?
                            'border-gray-900 bg-gray-900 text-white' :
                            'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                        class="rounded-lg border px-2 py-1 text-center transition">
                        Prix & stock
                    </button>
                    <button type="button" @click="setActiveTab('variants')" x-show="productType === 'variant'" x-cloak
                        :class="isTabActive('variants') ?
                            'border-gray-900 bg-gray-900 text-white' :
                            'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                        class="rounded-lg border px-2 py-1 text-center transition">
                        Déclinaisons
                    </button>
                    <button type="button" @click="setActiveTab('organization')"
                        :class="isTabActive('organization') ?
                            'border-gray-900 bg-gray-900 text-white' :
                            'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                        class="rounded-lg border px-2 py-1 text-center transition">
                        Catalogue
                    </button>
                    <button type="button" @click="setActiveTab('seo')"
                        :class="isTabActive('seo') ?
                            'border-gray-900 bg-gray-900 text-white' :
                            'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                        class="rounded-lg border px-2 py-1 text-center transition">
                        SEO
                    </button>
                </div>
            </div>

            {{-- Détails --}}
            <div id="product-section-details"
                class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3 scroll-mt-20"
                x-show="isTabActive('general')" x-cloak>
                <div>
                    <div class="flex items-center gap-2">
                        <div class="text-xs font-semibold text-gray-800">Détails du produit</div>
                    </div>
                    <div class="text-xxs text-gray-500">
                        Mettez à jour le nom, le slug et la description.
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Nom</label>
                    <div class="flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <input type="text" name="name" value="{{ old('name', $t?->name) }}" x-model="productName"
                            class="flex-1 border-0 px-3 py-1.5 text-xs focus:ring-0"
                            x-bind:required="isTabActive('general')">
                        <button type="button" data-ai-open-modal data-ai-target="name"
                            data-ai-target-label="Nom du produit" data-ai-generate-url="{{ route('products.ai.generate') }}"
                            class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                            aria-label="Générer le nom du produit avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $t?->slug) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs"
                        x-bind:required="isTabActive('general')">
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Description courte</label>
                    <div
                        class="product-wysiwyg-group flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <div class="min-w-0 flex-1">
                            <textarea name="short_description" class="hidden">{{ old('short_description', $t?->short_description) }}</textarea>
                            <div data-product-wysiwyg="short_description"
                                data-product-wysiwyg-placeholder="Résumé produit (bénéfices, usage, matière...)"
                                data-product-wysiwyg-min-height="90"></div>
                        </div>
                        <button type="button" data-ai-open-modal data-ai-target="short_description"
                            data-ai-target-label="Description courte"
                            data-ai-generate-url="{{ route('products.ai.generate') }}"
                            class="inline-flex w-10 shrink-0 items-start justify-center border-l border-gray-200 pt-2 text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                            aria-label="Générer la description courte avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Description</label>
                    <div
                        class="product-wysiwyg-group flex items-stretch rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <div class="min-w-0 flex-1">
                            <textarea name="description" class="hidden">{{ old('description', $t?->description) }}</textarea>
                            <div data-product-wysiwyg="description"
                                data-product-wysiwyg-placeholder="Description complète du produit (détails, arguments, entretien...)"
                                data-product-wysiwyg-min-height="200"></div>
                        </div>
                        <button type="button" data-ai-open-modal data-ai-target="description"
                            data-ai-target-label="Description produit"
                            data-ai-generate-url="{{ route('products.ai.generate') }}"
                            class="inline-flex w-10 shrink-0 items-start justify-center border-l border-gray-200 pt-2 text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                            aria-label="Générer la description produit avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            {{-- Images --}}
            <div id="product-section-images"
                class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3 scroll-mt-20"
                x-show="isTabActive('media')" x-cloak>
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <div class="text-xs font-semibold text-gray-800">Images du produit</div>
                        </div>
                        <div class="text-xxs text-gray-500">
                            Gérez les images existantes, ajoutez-en de nouvelles et choisissez l’image principale.
                        </div>
                    </div>
                    <button type="button" data-ai-image-generate-button
                        data-ai-image-generate-url="{{ route('products.ai.generate-image') }}"
                        class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-2 py-1 text-xxs font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
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
                                        class="absolute left-1 top-1 z-10 inline-flex items-center rounded-md bg-black/90 px-1.5 py-0.5 text-xxs font-semibold uppercase tracking-wide text-white shadow-sm">
                                        IA
                                    </span>
                                @endif

                                <button type="button" title="Supprimer l'image" aria-label="Supprimer l'image"
                                    class="absolute right-1 top-1 z-10 inline-flex h-7 w-7 items-center justify-center rounded-md bg-white/90 text-gray-600 shadow-sm hover:bg-white"
                                    @click="$dispatch('open-modal', { name: 'delete-product-image-{{ $image->id }}' })">
                                    <x-lucide-trash-2 class="h-3.5 w-3.5" />
                                </button>

                                <label
                                    class="absolute bottom-1 left-1 right-1 flex items-center justify-center gap-1
                                            bg-black/60 text-white text-xxs px-1.5 py-0.5 rounded-full cursor-pointer">
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
                        <x-admin::modal name="delete-product-image-{{ $image->id }}" :title="'Supprimer cette image ?'"
                            description="Cette action est définitive." size="max-w-md">
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
                    <div class="text-xxs text-gray-500">
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

                    <p class="text-xxs text-gray-400">
                        Les nouvelles images seront ajoutées à la suite. Vous pouvez aussi les définir directement comme
                        image principale.
                    </p>

                    <div id="new-images-preview" class="mt-2 grid grid-cols-3 gap-2"></div>
                </div>

                <div class="mt-3 space-y-2">
                    <div class="text-xs font-medium text-gray-700">Images IA générées</div>
                    <p class="text-xxs text-gray-400">
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
                                            class="absolute left-1 top-1 z-10 inline-flex items-center rounded-md bg-emerald-600/90 px-1.5 py-0.5 text-xxs font-semibold uppercase tracking-wide text-white shadow-sm">
                                            IA
                                        </span>
                                        <div
                                            class="absolute bottom-1 left-1 right-1 flex items-center justify-center gap-1 bg-black/60 text-white text-xxs px-1.5 py-0.5 rounded-full">
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
                    value="{{ old('main_image', '0') }}">
            </div>

            {{-- SEO --}}
            <div id="product-section-seo"
                class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3 scroll-mt-20"
                x-show="isTabActive('seo')" x-cloak>
                <div>
                    <div class="flex items-center gap-2">
                        <div class="text-xs font-semibold text-gray-800">Référencement</div>
                    </div>
                    <p class="text-xxs text-gray-500">
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
                        <textarea name="meta_description" class="flex-1 border-0 px-3 py-1.5 text-xs h-20 resize-none focus:ring-0">{{ old('meta_description', $t?->meta_description) }}</textarea>
                        <button type="button" data-ai-open-modal data-ai-target="meta_description"
                            data-ai-target-label="Meta description"
                            data-ai-generate-url="{{ route('products.ai.generate') }}"
                            class="inline-flex items-center justify-center border-l border-gray-200 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                            aria-label="Générer la meta description avec l'IA">
                            <x-lucide-wand-sparkles class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-4" x-show="isTabActive('offer') || isTabActive('variants') || isTabActive('organization')"
                x-cloak>
                {{-- Inventaire produit simple --}}
                <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3"
                    x-show="isTabActive('offer') && productType === 'simple'" x-cloak>
                    <div class="text-xs font-semibold text-gray-800">Inventaire (produit simple)</div>

                    <div class="space-y-2">
                        <label class="block text-xs font-medium text-gray-700">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                            class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs"
                            x-bind:required="productType === 'simple' && isTabActive('offer')">
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
                            x-bind:required="productType === 'simple' && isTabActive('offer')">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-gray-700">
                            Prix barré / avant remise (optionnel)
                        </label>
                        <input type="number" name="compare_at_price" step="0.01" min="0"
                            value="{{ old('compare_at_price', $product->compare_at_price) }}"
                            class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                        <p class="text-xxs text-gray-400">
                            Utilisé pour afficher une promotion si supérieur au prix TTC.
                        </p>
                    </div>

                </div>

                <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-3"
                    x-show="isTabActive('offer') && productType === 'variant'" x-cloak>
                    <p class="text-xxs text-gray-600">
                        Ce produit est en mode déclinaisons. Gérez les prix, stocks et images par variante dans l’onglet
                        <span class="font-semibold text-gray-900">Déclinaisons</span>.
                    </p>
                </div>

                {{-- Builder de déclinaisons --}}
                <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3"
                    x-show="isTabActive('variants') && productType === 'variant'" x-cloak>
                    <div>
                        <div class="text-xs font-semibold text-gray-800">Déclinaisons</div>
                        <p class="text-xxs text-gray-500">
                            Gérez les options (ex : Taille, Couleur) et les variantes associées (SKU, prix, stock, image).
                            Toute sauvegarde remplace les variantes selon le tableau ci-dessous.
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-1.5 rounded-xl border border-gray-100 bg-gray-50/50 p-1 text-xxs md:grid-cols-4">
                        <button type="button" @click="setActiveVariantTab('options')"
                            :class="isVariantTabActive('options') ?
                                'bg-white border border-gray-200 text-gray-900' :
                                'text-gray-600 hover:bg-white/70'"
                            class="rounded-lg px-2 py-1 text-center transition">
                            1. Options
                        </button>
                        <button type="button" @click="setActiveVariantTab('combinations')"
                            :class="isVariantTabActive('combinations') ?
                                'bg-white border border-gray-200 text-gray-900' :
                                'text-gray-600 hover:bg-white/70'"
                            class="rounded-lg px-2 py-1 text-center transition">
                            2. Combinaisons
                        </button>
                        <button type="button" @click="setActiveVariantTab('pricing')"
                            :class="isVariantTabActive('pricing') ?
                                'bg-white border border-gray-200 text-gray-900' :
                                'text-gray-600 hover:bg-white/70'"
                            class="rounded-lg px-2 py-1 text-center transition">
                            3. Prix & stock
                        </button>
                        <button type="button" @click="setActiveVariantTab('images')"
                            :class="isVariantTabActive('images') ?
                                'bg-white border border-gray-200 text-gray-900' :
                                'text-gray-600 hover:bg-white/70'"
                            class="rounded-lg px-2 py-1 text-center transition">
                            4. Images
                        </button>
                    </div>

                    <div class="space-y-2 mt-2" x-show="isVariantTabActive('options')" x-cloak>
                        <template x-for="(opt, index) in options" :key="index">
                            <div class="flex flex-col gap-1 rounded-xl border border-gray-100 px-2 py-2 bg-gray-50/40">
                                <div class="flex items-center gap-2">
                                    <input type="text"
                                        class="w-32 rounded-lg border border-gray-200 px-2 py-1 text-xxs"
                                        placeholder="Nom de l’option (ex : Taille)" x-model="opt.name">
                                    <button type="button" class="text-xxs text-gray-500 ml-auto"
                                        @click="removeOption(index)">
                                        Supprimer
                                    </button>
                                </div>

                                <div>
                                    <input type="text"
                                        class="w-full rounded-lg border border-gray-200 px-2 py-1 text-xxs"
                                        placeholder="Valeurs séparées par des virgules (ex : S,M,L)"
                                        x-model="opt.valuesText">
                                </div>

                                <input type="hidden" :name="`options[${index}][name]`" x-model="opt.name">
                                <template x-for="(val, vIndex) in splitValues(opt.valuesText)" :key="vIndex">
                                    <input type="hidden" :name="`options[${index}][values][${vIndex}]`"
                                        :value="val">
                                </template>
                            </div>
                        </template>

                        <button type="button" class="text-xxs text-gray-700 underline" @click="addOption()">
                            + Ajouter une option
                        </button>
                    </div>

                    <div class="space-y-2" x-show="isVariantTabActive('combinations')" x-cloak>
                        <div class="mt-1">
                            <button type="button"
                                class="rounded-full bg-[#111827] px-3 py-1 text-xxs text-white hover:bg-black"
                                @click="generateVariants">
                                Générer / régénérer les variantes
                            </button>
                            <p class="text-xxs text-gray-400 mt-1">
                                La régénération aligne les variantes sur les options et valeurs définies.
                            </p>
                        </div>

                        <div class="max-h-72 overflow-y-auto rounded-xl border border-gray-100" x-show="variants.length">
                            <table class="w-full text-xxs text-gray-700">
                                <thead class="sticky top-0 z-10 bg-gray-50">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Variante</th>
                                        <th class="px-2 py-1 text-left">Attributs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(v, idx) in variants" :key="`combo-${idx}`">
                                        <tr class="border-t">
                                            <td class="px-2 py-1">
                                                <input type="text"
                                                    class="w-full rounded border border-gray-200 px-1 py-0.5"
                                                    x-model="v.label">
                                            </td>
                                            <td class="px-2 py-1 text-gray-500">
                                                <span x-text="(v.values || []).join(' / ')"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-xxs text-gray-400" x-show="!variants.length">
                            Aucune variante générée. Définissez vos options puis générez les combinaisons.
                        </p>
                    </div>

                    <div class="space-y-3" x-show="isVariantTabActive('pricing')" x-cloak>
                        <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-2 space-y-2"
                            x-show="variants.length">
                            <div class="text-xxs font-semibold text-gray-700">Actions rapides (prix / stock)</div>
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                <input type="number" min="0"
                                    class="w-full rounded border border-gray-200 px-1.5 py-1 text-xxs" placeholder="Stock"
                                    x-model="bulkVariantUpdate.stock_qty">
                                <input type="number" step="0.01" min="0"
                                    class="w-full rounded border border-gray-200 px-1.5 py-1 text-xxs"
                                    placeholder="Prix TTC" x-model="bulkVariantUpdate.price">
                                <input type="number" step="0.01" min="0"
                                    class="w-full rounded border border-gray-200 px-1.5 py-1 text-xxs"
                                    placeholder="Prix barré" x-model="bulkVariantUpdate.compare_at_price">
                                <select class="w-full rounded border border-gray-200 px-1.5 py-1 text-xxs"
                                    x-model="bulkVariantUpdate.active_mode">
                                    <option value="keep">Actif: conserver</option>
                                    <option value="active">Actif: oui</option>
                                    <option value="inactive">Actif: non</option>
                                </select>
                            </div>
                            <div class="flex justify-end">
                                <button type="button"
                                    class="rounded-full border border-gray-200 px-3 py-1 text-xxs text-gray-700 hover:bg-gray-50"
                                    @click="bulkVariantUpdate.image_key = ''; applyBulkToVariants()">
                                    Appliquer aux variantes
                                </button>
                            </div>
                        </div>

                        <div class="max-h-72 overflow-y-auto rounded-xl border border-gray-100" x-show="variants.length">
                            <table class="w-full text-xxs text-gray-700">
                                <thead class="sticky top-0 z-10 bg-gray-50">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Variante</th>
                                        <th class="px-2 py-1 text-left">SKU</th>
                                        <th class="px-2 py-1">Actif</th>
                                        <th class="px-2 py-1">Stock</th>
                                        <th class="px-2 py-1">Prix</th>
                                        <th class="px-2 py-1">Prix barré</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(v, idx) in variants" :key="`pricing-${idx}`">
                                        <tr class="border-t">
                                            <td class="px-2 py-1" x-text="v.label"></td>
                                            <td class="px-2 py-1">
                                                <input type="text"
                                                    class="w-full border border-gray-200 rounded px-1 py-0.5"
                                                    x-model="v.sku">
                                            </td>
                                            <td class="px-2 py-1 text-center">
                                                <input type="checkbox" value="1" x-model="v.is_active">
                                            </td>
                                            <td class="px-2 py-1">
                                                <input type="number" min="0"
                                                    class="w-full border border-gray-200 rounded px-1 py-0.5"
                                                    x-model="v.stock_qty">
                                            </td>
                                            <td class="px-2 py-1">
                                                <input type="number" step="0.01" min="0"
                                                    class="w-full border border-gray-200 rounded px-1 py-0.5"
                                                    x-model="v.price">
                                            </td>
                                            <td class="px-2 py-1">
                                                <input type="number" step="0.01" min="0"
                                                    class="w-full border border-gray-200 rounded px-1 py-0.5"
                                                    x-model="v.compare_at_price">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-xxs text-gray-400" x-show="!variants.length">
                            Aucune variante à tarifer pour le moment.
                        </p>
                    </div>

                    <div class="space-y-3" x-show="isVariantTabActive('images')" x-cloak>
                        <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-2 space-y-2"
                            x-show="variants.length">
                            <div class="text-xxs font-semibold text-gray-700">Affectation rapide des images</div>
                            <div class="space-y-2">
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" class="rounded-lg border px-2 py-1 text-xxs transition"
                                        :class="bulkVariantUpdate.image_key === '' ?
                                            'border-gray-900 bg-gray-900 text-white' :
                                            'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                        @click="bulkVariantUpdate.image_key = ''">
                                        Conserver
                                    </button>
                                    <button type="button" class="rounded-lg border px-2 py-1 text-xxs transition"
                                        :class="bulkVariantUpdate.image_key === '__clear__' ?
                                            'border-gray-900 bg-gray-900 text-white' :
                                            'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                        @click="bulkVariantUpdate.image_key = '__clear__'">
                                        Aucune
                                    </button>
                                    <template x-for="choice in variantImageChoices" :key="`bulk-image-${choice.key}`">
                                        <button type="button"
                                            class="group relative h-11 w-11 overflow-hidden rounded-lg border transition"
                                            :class="bulkVariantUpdate.image_key === choice.key ?
                                                'border-gray-900 ring-1 ring-gray-900' :
                                                'border-gray-200 hover:border-gray-400'"
                                            @click="bulkVariantUpdate.image_key = choice.key" :title="choice.label"
                                            :aria-label="`Choisir ${choice.label}`">
                                            <img :src="choice.url || ''" :alt="choice.label"
                                                class="h-full w-full object-cover" x-show="choice.url !== ''" x-cloak>
                                            <span
                                                class="flex h-full w-full items-center justify-center bg-gray-100 text-[9px] text-gray-500"
                                                x-show="choice.url === ''" x-cloak>
                                                IMG
                                            </span>
                                        </button>
                                    </template>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xxs text-gray-500"
                                        x-text="`Sélection: ${variantImageLabel(bulkVariantUpdate.image_key)}`"></p>
                                    <button type="button"
                                        class="rounded-full border border-gray-200 px-3 py-1 text-xxs text-gray-700 hover:bg-gray-50"
                                        @click="bulkVariantUpdate.stock_qty = ''; bulkVariantUpdate.price = ''; bulkVariantUpdate.compare_at_price = ''; bulkVariantUpdate.active_mode = 'keep'; applyBulkToVariants()">
                                        Appliquer l’image
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="max-h-72 overflow-y-auto rounded-xl border border-gray-100" x-show="variants.length">
                            <table class="w-full text-xxs text-gray-700">
                                <thead class="sticky top-0 z-10 bg-gray-50">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Variante</th>
                                        <th class="px-2 py-1 text-left">Image</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(v, idx) in variants" :key="`image-${idx}`">
                                        <tr class="border-t">
                                            <td class="px-2 py-1" x-text="v.label"></td>
                                            <td class="px-2 py-1 min-w-48">
                                                <div class="space-y-1">
                                                    <div class="flex flex-wrap gap-1">
                                                        <button type="button"
                                                            class="rounded border px-1.5 py-0.5 text-[10px] transition"
                                                            :class="v.image_key === '' ?
                                                                'border-gray-900 bg-gray-900 text-white' :
                                                                'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                                            @click="v.image_key = ''">
                                                            Auto
                                                        </button>
                                                        <template x-for="choice in variantImageChoices"
                                                            :key="`${idx}-${choice.key}`">
                                                            <button type="button"
                                                                class="relative h-9 w-9 overflow-hidden rounded border transition"
                                                                :class="v.image_key === choice.key ?
                                                                    'border-gray-900 ring-1 ring-gray-900' :
                                                                    'border-gray-200 hover:border-gray-400'"
                                                                @click="v.image_key = choice.key" :title="choice.label"
                                                                :aria-label="`Choisir ${choice.label}`">
                                                                <img :src="choice.url || ''" :alt="choice.label"
                                                                    class="h-full w-full object-cover"
                                                                    x-show="choice.url !== ''" x-cloak>
                                                                <span
                                                                    class="flex h-full w-full items-center justify-center bg-gray-100 text-[9px] text-gray-500"
                                                                    x-show="choice.url === ''" x-cloak>
                                                                    IMG
                                                                </span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-xxs text-gray-400" x-show="!variants.length">
                            Aucune variante à illustrer pour le moment.
                        </p>
                    </div>

                    <div class="hidden">
                        <template x-for="(v, idx) in variants" :key="`payload-${idx}`">
                            <div>
                                <input type="hidden" :name="`variants[${idx}][id]`" :value="v.id ?? ''">
                                <input type="hidden" :name="`variants[${idx}][label]`" :value="v.label">
                                <input type="hidden" :name="`variants[${idx}][sku]`" :value="v.sku">
                                <input type="hidden" :name="`variants[${idx}][is_active]`" :value="v.is_active ? 1 : 0">
                                <input type="hidden" :name="`variants[${idx}][stock_qty]`" :value="v.stock_qty ?? 0">
                                <input type="hidden" :name="`variants[${idx}][price]`" :value="v.price ?? ''">
                                <input type="hidden" :name="`variants[${idx}][compare_at_price]`"
                                    :value="v.compare_at_price ?? ''">
                                <input type="hidden" :name="`variants[${idx}][image_key]`" :value="v.image_key ?? ''">
                                <template x-for="(val, vIndex) in (v.values || [])"
                                    :key="`payload-value-${idx}-${vIndex}`">
                                    <input type="hidden" :name="`variants[${idx}][values][${vIndex}]`"
                                        :value="val">
                                </template>
                            </div>
                        </template>
                    </div>
                    <p class="text-xxs text-gray-400" x-show="variants.length && variantImageChoices.length === 0">
                        Aucune image disponible pour l’instant: ajoutez des images produit pour pouvoir les affecter aux
                        déclinaisons.
                    </p>
                    @if ($errors->has('variants.*.image_key'))
                        <p class="text-xxs text-red-600">
                            {{ collect($errors->get('variants.*.image_key'))->flatten()->first() }}
                        </p>
                    @endif
                </div>

                {{-- Catégories --}}
                @if (isset($categories) && $categories->count())
                    <div id="product-section-categories"
                        class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3 scroll-mt-20"
                        x-show="isTabActive('organization')" x-cloak>
                        <div>
                            <div class="flex items-center gap-2">
                                <div class="text-xs font-semibold text-gray-800">Catégories</div>
                            </div>
                            <p class="text-xxs text-gray-500">
                                Gérez les catégories associées à ce produit.
                            </p>
                        </div>

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

                                    echo '<label class="flex items-center gap-2 text-xxs text-gray-700" style="padding-left:' .
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

                    <div id="product-section-related" x-data="{ search: '' }"
                        class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-2 scroll-mt-20"
                        x-show="isTabActive('organization')" x-cloak>
                        <div class="flex items-center justify-between gap-2">
                            <div class="w-full">
                                <div class="flex justify-between items-center">
                                    <div class="text-xs font-semibold text-gray-800">Produits associés</div>
                                    <span class="pointer-events-none text-xxs text-gray-400">
                                        {{ $relatedProducts->count() }} produits disponibles
                                    </span>
                                </div>
                                <p class="text-xxs text-gray-500">
                                    Gérez les produits liés (suggestions, cross-sell, ensembles...).
                                </p>
                            </div>
                        </div>

                        <div class="relative mt-1">
                            <input type="text" x-model="search" placeholder="Rechercher par nom, SKU ou slug..."
                                class="w-full rounded-full border border-gray-200 px-3 py-1.5 text-xxs focus:outline-none focus:ring-1 focus:ring-gray-900" />
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
                                    class="flex items-center gap-2 rounded-lg px-2 py-1 text-xxs text-gray-700 hover:bg-gray-50 cursor-pointer"
                                    x-show="search === '' || '{{ $labelKey }}'.includes(search.toLowerCase())">
                                    <input type="checkbox" name="related_products[]" value="{{ $related->id }}"
                                        class="h-3 w-3 rounded border-gray-300" @checked(in_array($related->id, $selectedRelated))>
                                    <div class="flex flex-col leading-tight">
                                        <span class="font-medium text-gray-900">
                                            {{ $rt?->name ?? 'Produit #' . $related->id }}
                                        </span>
                                        <span class="text-xxs text-gray-500">
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

                        <p class="text-xxs text-gray-400">
                            Les produits déjà liés sont pré-sélectionnés. La recherche filtre l’affichage sans enlever
                            votre
                            sélection.
                        </p>
                    </div>
                @endif

                @if ((!isset($categories) || !$categories->count()) && (!isset($relatedProducts) || !$relatedProducts->count()))
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-3"
                        x-show="isTabActive('organization')" x-cloak>
                        <p class="text-xxs text-gray-600">
                            Aucune donnée de catalogue disponible pour ce produit (catégories ou produits associés).
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Colonne droite --}}
        <div class="space-y-4 sticky top-20 self-start">
            <div class="space-y-4">
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
        </div>

        {{-- Barre sticky "Enregistrer" --}}
        <div id="product-sticky-actions"
            class="hidden fixed inset-x-0 bottom-0 z-40 bg-white/95 backdrop-blur border-t border-black/5 px-4 py-2">
            <div class="max-w-5xl mx-auto flex items-center justify-between gap-3">
                <div class="hidden sm:flex flex-col">
                    <span class="text-xxs text-gray-500">
                        Vous avez des modifications en cours
                    </span>
                    <span class="text-xs font-medium text-gray-800">
                        Enregistrez le produit quand vous êtes prêt.
                    </span>
                </div>

                <div class="flex items-center gap-2 ml-auto">
                    <a href="{{ route('products.index') }}"
                        class="px-4 py-1.5 rounded-lg border border-gray-200 font-semibold text-xs text-gray-700 hover:bg-gray-50">
                        Annuler
                    </a>
                    <button type="submit"
                        class="px-4 py-1.5 rounded-lg bg-[#111827] text-xs font-semibold text-white hover:bg-black">
                        Créer le produit
                    </button>
                </div>
            </div>
        </div>

        <div id="product-ai-prompt-modal"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 backdrop-blur-sm px-4">
            <div class="w-full max-w-lg rounded-2xl border border-black/5 bg-white p-4 shadow-xl">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="text-xs font-semibold text-gray-900">Génération IA du produit</div>
                        <div class="text-xxs text-gray-500">
                            Décris le résultat attendu (angle marketing, mots-clés SEO, ton, contraintes).
                        </div>
                    </div>
                    <button type="button" id="product-ai-modal-close-button"
                        class="text-xs text-gray-400 hover:text-gray-700" aria-label="Fermer la modal IA">
                        ✕
                    </button>
                </div>

                <div class="mt-3 space-y-2">
                    <p class="text-xxs text-gray-500">
                        Champ ciblé: <span id="product-ai-target-label" class="font-semibold text-gray-700">-</span>
                    </p>
                    <label for="product-ai-prompt-input" class="block text-xs font-medium text-gray-700">
                        Prompt de génération
                    </label>
                    <textarea id="product-ai-prompt-input" rows="5" maxlength="2000"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y"
                        placeholder="Ex: Rédige un titre orienté conversion et une description premium, en mettant en avant la durabilité du produit."></textarea>
                    <p class="text-xxs text-gray-500">
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
                        <div class="text-xxs text-gray-500">
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
                    <p id="product-ai-image-reference-empty" class="hidden text-xxs text-gray-500">
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
                    <p class="text-xxs text-gray-500">
                        Seul ce prompt est utilisé pour la génération.
                    </p>
                    <p id="product-ai-image-modal-error" class="hidden text-xxs text-red-600"></p>
                    <div id="product-ai-image-loading" class="hidden items-center gap-1.5 text-xxs text-gray-600">
                        <span
                            class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-gray-300 border-t-gray-700"></span>
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
