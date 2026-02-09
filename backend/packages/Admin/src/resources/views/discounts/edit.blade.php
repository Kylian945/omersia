@extends('admin::layout')

@section('title', 'Modifier la réduction')
@section('page-title', 'Modifier la réduction')

@section('content')
<div class="max-w-3xl space-y-4 mx-auto">

    {{-- Header avec bouton Retour + Supprimer --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-sm font-semibold flex items-center gap-2">
                <x-lucide-badge-percent class="w-4 h-4" />
                Modifier la réduction
            </h1>
            <p class="text-xs text-neutral-500">
                Mettez à jour les paramètres de cette réduction.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('discounts.index') }}"
               class="px-3 py-1.5 rounded-md border text-xs">
                Retour
            </a>

            {{-- Formulaire de suppression séparé, pas imbriqué --}}
            <form method="POST"
                  action="{{ route('discounts.destroy', $discount) }}"
                  onsubmit="return confirm('Supprimer cette réduction ?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-3 py-1.5 rounded-md border border-red-200 text-xs text-red-600">
                    Supprimer
                </button>
            </form>
        </div>
    </div>

    {{-- Formulaire d’édition --}}
    <form method="POST" action="{{ route('discounts.update', $discount) }}" class="space-y-4">
        @csrf
        @method('PUT')

        @include('admin::discounts.partials.form', [
            'discount'       => $discount,
            'initialType'    => $initialType ?? $discount->type,
            'products'       => $products ?? [],
            'collections'    => $collections ?? [],
            'customerGroups' => $customerGroups ?? [],
            'customers'      => $customers ?? [],
            'hasPresetType'  => true, // pour ne plus afficher le select type si tu utilises ce flag
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
