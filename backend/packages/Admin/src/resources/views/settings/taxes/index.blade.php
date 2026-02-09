@extends('admin::settings.layout')

@section('title', 'Gestion des taxes')
@section('page-title', 'Taxes / TVA')

@section('settings-content')
<div x-data="{}" class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-lucide-percent class="w-4 h-4" />
            <h1 class="text-base font-semibold">Zones de taxation et taux de TVA</h1>
        </div>

        <a href="{{ route('admin.settings.taxes.zones.create') }}"
           class="inline-flex items-center rounded-lg bg-neutral-900 text-white text-xs px-4 py-1.5 hover:bg-black">
            <x-lucide-plus class="w-4 h-4 mr-1" />
            Nouvelle zone
        </a>
    </div>

    {{-- Tax Zones --}}
    @if($taxZones->isEmpty())
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-6">
            <p class="text-xs text-neutral-500 text-center">
                Aucune zone de taxation configurée. Créez une zone pour commencer à gérer les taxes.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($taxZones as $zone)
                <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
                    {{-- Zone Header --}}
                    <div class="bg-neutral-50 border-b border-neutral-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-semibold text-neutral-900">{{ $zone->name }}</h3>
                                        <code class="text-xxs bg-white px-2 py-0.5 rounded-md border border-neutral-200">{{ $zone->code }}</code>
                                        @if($zone->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xxs font-medium">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 text-xxs font-medium">
                                                Inactive
                                            </span>
                                        @endif
                                    </div>
                                    @if($zone->description)
                                        <p class="text-xxs text-neutral-500 mt-0.5">{{ $zone->description }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.settings.taxes.rates.create', $zone) }}"
                                   class="inline-flex items-center rounded-lg border border-neutral-200 px-3 py-1 text-xxs text-neutral-700 hover:bg-neutral-50">
                                    <x-lucide-plus class="w-3 h-3 mr-1" />
                                    Ajouter un taux
                                </a>
                                <a href="{{ route('admin.settings.taxes.zones.edit', $zone) }}"
                                   class="rounded-full border border-gray-200 px-3 py-1 text-xxs text-gray-700 hover:bg-gray-50">
                                    Modifier
                                </a>
                                <button type="button"
                                        class="rounded-full border border-red-200 px-3 py-1 text-xxs text-red-600 hover:bg-red-50"
                                        @click="$dispatch('open-modal', { name: 'delete-zone-{{ $zone->id }}' })">
                                    Supprimer
                                </button>
                            </div>
                        </div>

                        {{-- Zone Geography Info --}}
                        <div class="mt-2 flex flex-wrap gap-4 text-xxs text-neutral-600">
                            @if($zone->countries)
                                <div class="flex items-center gap-1">
                                    <x-lucide-map-pin class="w-3 h-3" />
                                    <span>Pays: {{ implode(', ', $zone->countries) }}</span>
                                </div>
                            @endif
                            @if($zone->states)
                                <div class="flex items-center gap-1">
                                    <x-lucide-map class="w-3 h-3" />
                                    <span>États/Régions définis</span>
                                </div>
                            @endif
                            @if($zone->postal_codes)
                                <div class="flex items-center gap-1">
                                    <x-lucide-hash class="w-3 h-3" />
                                    <span>Codes postaux: {{ implode(', ', array_slice($zone->postal_codes, 0, 3)) }}{{ count($zone->postal_codes) > 3 ? '...' : '' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Tax Rates Table --}}
                    @if($zone->taxRates->isEmpty())
                        <div class="px-4 py-3 text-center">
                            <p class="text-xxs text-neutral-400">Aucun taux de taxe configuré pour cette zone</p>
                        </div>
                    @else
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-left text-neutral-500 border-b border-neutral-100">
                                    <th class="py-2 px-4">Nom</th>
                                    <th class="py-2 px-4">Type</th>
                                    <th class="py-2 px-4 text-right">Taux</th>
                                    <th class="py-2 px-4 text-center">Frais de port</th>
                                    <th class="py-2 px-4 text-center">Composée</th>
                                    <th class="py-2 px-4 text-center">Statut</th>
                                    <th class="py-2 px-4 text-right"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($zone->taxRates as $rate)
                                    <tr class="border-b border-neutral-50 last:border-0">
                                        <td class="py-2 px-4 font-medium text-neutral-900">
                                            {{ $rate->name }}
                                        </td>
                                        <td class="py-2 px-4 text-neutral-500">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-neutral-100 text-xxs">
                                                {{ $rate->type === 'percentage' ? 'Pourcentage' : 'Montant fixe' }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-4 text-right font-semibold text-neutral-900">
                                            @if($rate->type === 'percentage')
                                                {{ number_format($rate->rate, 2) }}%
                                            @else
                                                {{ number_format($rate->rate, 2, ',', ' ') }} €
                                            @endif
                                        </td>
                                        <td class="py-2 px-4 text-center">
                                            @if($rate->shipping_taxable)
                                                <x-lucide-check class="w-4 h-4 text-emerald-600 inline" />
                                            @else
                                                <x-lucide-x class="w-4 h-4 text-neutral-300 inline" />
                                            @endif
                                        </td>
                                        <td class="py-2 px-4 text-center">
                                            @if($rate->compound)
                                                <x-lucide-check class="w-4 h-4 text-blue-600 inline" />
                                            @else
                                                <x-lucide-x class="w-4 h-4 text-neutral-300 inline" />
                                            @endif
                                        </td>
                                        <td class="py-2 px-4 text-center">
                                            @if($rate->is_active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xxs font-medium">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 text-xxs font-medium">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-2 px-4 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="{{ route('admin.settings.taxes.rates.edit', [$zone, $rate]) }}"
                                                   class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                                    Modifier
                                                </a>
                                                <button type="button"
                                                        class="rounded-full border border-red-200 px-2 py-0.5 text-xxxs text-red-600 hover:bg-red-50"
                                                        @click="$dispatch('open-modal', { name: 'delete-rate-{{ $rate->id }}' })">
                                                    Supprimer
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    {{-- Modal de confirmation de suppression de la zone --}}
                    <x-admin::modal name="delete-zone-{{ $zone->id }}"
                        :title="'Supprimer la zone « ' . $zone->name . ' » ?'"
                        description="Cette action supprimera également tous les taux de taxe associés à cette zone."
                        size="max-w-md">
                        <p class="text-xs text-gray-600">
                            Voulez-vous vraiment supprimer la zone de taxation
                            <span class="font-semibold">{{ $zone->name }}</span>
                            (<code class="text-xxxs bg-gray-100 px-1 py-0.5 rounded">{{ $zone->code }}</code>)
                            et <span class="font-semibold">tous ses {{ $zone->taxRates->count() }} taux de taxe</span> ?
                        </p>

                        <div class="flex justify-end gap-2 pt-3">
                            <button type="button"
                                class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                                @click="open = false">
                                Annuler
                            </button>

                            <form method="POST" action="{{ route('admin.settings.taxes.zones.destroy', $zone) }}">
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

                    {{-- Modals de confirmation de suppression des taux --}}
                    @foreach($zone->taxRates as $rate)
                        <x-admin::modal name="delete-rate-{{ $rate->id }}"
                            :title="'Supprimer le taux « ' . $rate->name . ' » ?'"
                            description="Cette action est définitive et ne peut pas être annulée."
                            size="max-w-md">
                            <p class="text-xs text-gray-600">
                                Voulez-vous vraiment supprimer le taux de taxe
                                <span class="font-semibold">{{ $rate->name }}</span>
                                ({{ $rate->type === 'percentage' ? number_format($rate->rate, 2) . '%' : number_format($rate->rate, 2, ',', ' ') . ' €' }}) ?
                            </p>

                            <div class="flex justify-end gap-2 pt-3">
                                <button type="button"
                                    class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                                    @click="open = false">
                                    Annuler
                                </button>

                                <form method="POST" action="{{ route('admin.settings.taxes.rates.destroy', [$zone, $rate]) }}">
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
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
