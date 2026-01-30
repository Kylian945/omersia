@extends('admin::layout')

@section('title', 'Créer une réduction')
@section('page-title', 'Créer une réduction')

@section('content')
@php
    // Initial type from query (?type=product) or old() (validation)
    $initialType = old('type', request('type', 'order'));
    $hasPresetType = request()->filled('type'); // type choisi via la modal

    $typeTitle = [
        'product'    => 'Réduction sur les produits',
        'order'      => 'Réduction sur la commande',
        'shipping'   => 'Réduction sur les frais de port',
        'buy_x_get_y'=> 'Achetez X, obtenez Y',
    ][$initialType] ?? 'Réduction';

    $typeDescription = [
        'product'    => 'Appliquez une remise sur des produits ou catégories spécifiques.',
        'order'      => 'Appliquez une remise globale sur le total de la commande.',
        'shipping'   => 'Appliquez une remise ou la gratuité sur les frais de port.',
        'buy_x_get_y'=> 'Créez une offre de type “Achetez X, obtenez Y” avec des produits offerts ou remisés.',
    ][$initialType] ?? 'Configurez les paramètres de votre réduction.';
@endphp

<div class="max-w-3xl space-y-4 mx-auto">

    {{-- Header type de réduction (comme Shopify) --}}
    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex items-start gap-3">
        <div class="rounded-lg bg-neutral-900/5 p-2 mt-0.5">
            @if ($initialType === 'product')
                <x-lucide-tags class="w-4 h-4 text-neutral-800" />
            @elseif ($initialType === 'order')
                <x-lucide-shopping-bag class="w-4 h-4 text-neutral-800" />
            @elseif ($initialType === 'shipping')
                <x-lucide-truck class="w-4 h-4 text-neutral-800" />
            @elseif ($initialType === 'buy_x_get_y')
                <x-lucide-gift class="w-4 h-4 text-neutral-800" />
            @else
                <x-lucide-badge-percent class="w-4 h-4 text-neutral-800" />
            @endif
        </div>
        <div class="space-y-1">
            <h1 class="text-sm font-semibold">{{ $typeTitle }}</h1>
            <p class="text-xxs text-neutral-500">
                {{ $typeDescription }}
            </p>

            @if(!$hasPresetType)
                <p class="text-xxs text-neutral-400">
                    Vous pouvez modifier le type de réduction dans le formulaire ci-dessous.
                </p>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('discounts.store') }}" class="space-y-4">
        @csrf

        {{-- On passe le type initial et l’info “type pré-sélectionné” au partial --}}
        @include('admin::discounts.partials.form', [
            'discount' => null,
            'initialType' => $initialType,
            'hasPresetType' => $hasPresetType,
            'products' => $products ?? [],
            'collections' => $collections ?? [],
            'customerGroups' => $customerGroups ?? [],
            'customers' => $customers ?? [],
        ])

        <div class="flex justify-end gap-2">
            <a href="{{ route('discounts.index') }}"
               class="px-3 py-1.5 rounded-md border text-xs">
                Annuler
            </a>
            <button type="submit"
                    class="px-3 py-1.5 rounded-md bg-black text-white text-xs">
                Enregistrer
            </button>
        </div>
    </form>

</div>
@endsection
