@extends('admin::layout')

@section('title', 'Produits')
@section('page-title', 'Produits')

@section('content')
    <div class="flex items-center justify-between mb-3">
        <div>
            <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                <x-lucide-package class="w-3 h-3" />
                Produits
            </div>
            <div class="text-xs text-gray-500">
                Gérez vos produits simples et à déclinaisons, leur visibilité et leurs prix.
            </div>
        </div>
        <a href="{{ route('products.create') }}"
            class="rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-black">
            Ajouter un produit
        </a>
    </div>

    @if ($products->count() === 0)
        <div class="border border-dashed border-slate-200 rounded-xl p-6 text-center text-xs text-slate-500 bg-slate-50/40">
            Aucun produit pour le moment. Les nouveaux produits apparaîtront automatiquement ici.
        </div>
    @else
        <div x-data="{ q: @js(request('q')) }" class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
            <table class="w-full text-xs text-gray-700">
                <div class="flex items-center gap-3 p-1.5">
                    <div class="relative flex-1">
                        <form method="GET" action="{{ route('products.index') }}" class="relative flex-1"
                            x-ref="searchForm">
                            <input name="q" type="text" value="{{ request('q') }}" x-model="q"
                                @input.debounce.300ms="$refs.searchForm.requestSubmit()"
                                placeholder="Rechercher un produit…"
                                class="w-full rounded-xl border-0 bg-white px-10 py-2 text-sm placeholder:text-neutral-400 focus:border-neutral-400 focus:outline-none">
                            <x-lucide-search class="absolute left-3 top-2.5 h-4 w-4 text-neutral-400" />
                            <button type="submit" class="sr-only">Rechercher</button>
                        </form>
                    </div>
                </div>
                <thead class="bg-[#f9fafb] border-b border-t border-slate-200">
                    <tr>
                        <th class="py-2 px-3 text-left font-medium text-slate-600">Nom</th>
                        <th class="py-2 px-3 text-left font-medium text-slate-600">Type</th>
                        <th class="py-2 px-3 text-left font-medium text-slate-600">SKU</th>
                        <th class="py-2 px-3 text-left font-medium text-slate-600">Catégories</th>
                        <th class="py-2 px-3 text-left font-medium text-slate-600">Stock</th>
                        <th class="py-2 px-3 text-left font-medium text-slate-600">Actif</th>
                        <th class="py-2 px-3 text-right font-medium text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        @php
                            $t = $product->translation('fr');
                            $variantCount = (int) ($product->variants_count ?? 0);
                            $isVariant = $product->type === 'variant' || $variantCount > 0;
                            $globalStockQty = $isVariant
                                ? (int) ($product->variants_stock_qty ?? 0)
                                : (int) $product->stock_qty;
                            $stockIsManaged = $isVariant || (bool) $product->manage_stock;
                        @endphp
                        <tr class="border-b border-gray-50 hover:bg-[#fafafa]">
                            {{-- Nom --}}
                            <td class="py-2 px-3">
                                <div class="flex items-center gap-2">
                                    @if ($product->mainImage->url)
                                        <img class="w-10 h-10 rounded-md" src="{{ $product->mainImage->url }}"
                                            alt="image produit" />
                                    @endif
                                    <div>
                                        <div class="font-medium text-xs text-gray-900">
                                            {{ $t?->name ?? 'Sans nom' }}
                                        </div>
                                        <div class="text-xxxs text-gray-400">
                                            /{{ $t?->slug }}
                                        </div>
                                    </div>
                                </div>

                            </td>

                            {{-- Type --}}
                            <td class="py-2 px-3">
                                @if ($isVariant)
                                    <span
                                        class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xxxs text-indigo-700 gap-1">
                                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                                        Déclinaisons
                                        @if ($variantCount)
                                            <span class="text-xxxs text-indigo-500">({{ $variantCount }})</span>
                                        @endif
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xxxs text-gray-700 gap-1">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-500"></span>
                                        Simple
                                    </span>
                                @endif
                            </td>

                            {{-- SKU --}}
                            <td class="py-2 px-3 text-gray-600">
                                @if (!$isVariant)
                                    {{ $product->sku }}
                                @else
                                    <span class="text-xxxs text-gray-400 italic">
                                        Géré par variantes
                                    </span>
                                @endif
                            </td>

                            {{-- Catégories --}}
                            <td class="py-2 px-3 text-gray-500 text-xxxs">
                                {{ $product->categories->count() }} cat.
                            </td>

                            {{-- Stock --}}
                            <td class="py-2 px-3">
                                <div class="flex flex-col leading-tight">
                                    <span
                                        id="product-stock-value-{{ $product->id }}"
                                        data-product-stock-id="{{ $product->id }}"
                                        data-product-is-variant="{{ $isVariant ? '1' : '0' }}"
                                        data-product-manage-stock="{{ $product->manage_stock ? '1' : '0' }}"
                                        class="font-semibold text-xs {{ $stockIsManaged && $globalStockQty <= 0 ? 'text-red-600' : 'text-gray-900' }}"
                                    >
                                        @if ($stockIsManaged)
                                            {{ $globalStockQty }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                    <span
                                        id="product-stock-note-{{ $product->id }}"
                                        class="text-xxxs text-gray-400"
                                    >
                                        @if ($isVariant)
                                            Global ({{ $variantCount }} variantes)
                                        @elseif ($product->manage_stock)
                                            Stock simple
                                        @else
                                            Stock non géré
                                        @endif
                                    </span>
                                </div>
                            </td>

                            {{-- Actif --}}
                            <td class="py-2 px-3">
                                @if ($product->is_active)
                                    <span
                                        class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xxxs text-emerald-700">
                                        Actif
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">
                                        Inactif
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-2 px-3 text-right align-middle">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('products.edit', $product) }}"
                                        class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                        Modifier
                                    </a>
                                    <button type="button"
                                        class="rounded-full border border-red-100 px-2 py-0.5 text-xxxs text-red-500 hover:bg-red-50"
                                        @click="$dispatch('open-modal', { name: 'delete-product-{{ $product->id }}' })">
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-3 py-2 border-t border-gray-100">
                {{ $products->links() }}
            </div>
        </div>
    @endif

    {{-- Modals de confirmation de suppression --}}
    @foreach ($products as $product)
        <x-admin::modal name="delete-product-{{ $product->id }}" :title="'Supprimer le produit « ' . $product->name . ' » ?'"
            description="Cette action est définitive et ne peut pas être annulée." size="max-w-md">
            <p class="text-xs text-gray-600">
                Voulez-vous vraiment supprimer le produit
                <span class="font-semibold">{{ $product->name }}</span> ?
            </p>

            <div class="flex justify-end gap-2 pt-3">
                <button type="button"
                    class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                    @click="open = false">
                    Annuler
                </button>

                <form action="{{ route('products.destroy', $product) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-xs font-medium text-white hover:bg-neutral-900">
                        <x-lucide-trash-2 class="h-3 w-3" />
                        Confirmer la suppression
                    </button>
                </form>
            </div>
        </x-admin::modal>
    @endforeach
@endsection

@push('scripts')
    @vite(['packages/Admin/src/resources/js/products/stock-realtime.js'])
@endpush
