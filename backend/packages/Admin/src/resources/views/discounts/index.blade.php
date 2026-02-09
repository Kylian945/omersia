@extends('admin::layout')

@section('title', 'Réductions')
@section('page-title', 'Réductions')

@section('content')
    {{-- Wrapper Alpine pour gérer l’état de la modal --}}
    <div x-data="{ openCreateModal: false }" class="space-y-4">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-sm font-semibold flex items-center gap-2">
                    <x-lucide-badge-percent class="w-4 h-4" />
                    Réductions
                </h1>
                <div class="text-xs text-gray-500">
                    Gérez les réductions de votre boutique.
                </div>
            </div>

            {{-- Bouton qui ouvre la modal --}}
            <button type="button" @click="openCreateModal = true"
                class="px-3 py-1.5 rounded-md bg-black text-white text-xs font-semibold">
                Créer une réduction
            </button>
        </div>

        <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
            <table class="min-w-full text-xs">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">Nom</th>
                        <th class="px-3 py-2 text-left font-medium">Type</th>
                        <th class="px-3 py-2 text-left font-medium">Méthode</th>
                        <th class="px-3 py-2 text-left font-medium">Code</th>
                        <th class="px-3 py-2 text-left font-medium">Statut</th>
                        <th class="px-3 py-2 text-left font-medium">Groupes</th>
                        <th class="px-3 py-2 text-left font-medium">Compatibilité</th>
                        <th class="px-3 py-2 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discounts as $discount)
                        <tr class="border-t border-neutral-100">
                            <td class="px-3 py-2">
                                <div class="font-medium text-xs">{{ $discount->name }}</div>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                {{ ucfirst($discount->type) }}
                            </td>
                            <td class="px-3 py-2 text-xs">
                                {{ $discount->method === 'code' ? 'Code promo' : 'Automatique' }}
                            </td>
                            <td class="px-3 py-2 text-xs">
                                {{ $discount->code ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-xs">
                                @if ($discount->is_active)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-xxs">
                                        Actif
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full bg-neutral-50 text-neutral-500 text-xxs">
                                        Inactif
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2 ">

                                    @forelse ($discount->customerGroups as $group)
                                        <div class="bg-gray-50 border px-2 py-0.5 border-gray-100 text-xs rounded-full">
                                            {{ $group->name }}
                                        </div>
                                    @empty
                                        –
                                    @endforelse
                                </div>

                            </td>
                            <td>
                                <div class="flex gap-2 items-center px-3 py-2">
                                    @if ($discount->combines_with_product_discounts)
                                        <x-lucide-shopping-bag class="w-4 h-4 text-gray-400" />
                                    @endif
                                    @if ($discount->combines_with_order_discounts)
                                        <x-lucide-shopping-cart class="w-4 h-4 text-gray-400" />
                                    @endif
                                    @if ($discount->combines_with_shipping_discounts)
                                        <x-lucide-truck class="w-4 h-4 text-gray-400" />
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right text-xs">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('discounts.edit', $discount) }}"
                                        class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                        Modifier
                                    </a>
                                    <button type="button"
                                            class="rounded-full border border-red-100 px-2 py-0.5 text-xxxs text-red-500 hover:bg-red-50"
                                            @click="$dispatch('open-modal', { name: 'delete-discount-{{ $discount->id }}' })">
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-xs text-neutral-400">
                                Aucune réduction pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $discounts->links() }}

        {{-- Modals de confirmation de suppression --}}
        @foreach($discounts as $discount)
            <x-admin::modal name="delete-discount-{{ $discount->id }}"
                :title="'Supprimer la réduction « ' . $discount->name . ' » ?'"
                description="Cette action est définitive et ne peut pas être annulée."
                size="max-w-md">
                <p class="text-xs text-gray-600">
                    Voulez-vous vraiment supprimer la réduction
                    <span class="font-semibold">{{ $discount->name }}</span> ?
                </p>

                <div class="flex justify-end gap-2 pt-3">
                    <button type="button"
                        class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                        @click="open = false">
                        Annuler
                    </button>

                    <form action="{{ route('discounts.destroy', $discount) }}" method="POST">
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

        {{-- MODAL : Choix du type de réduction, façon Shopify --}}
        <div x-cloak x-show="openCreateModal" x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center">
            {{-- Overlay --}}
            <div class="absolute inset-0 bg-black/40" @click="openCreateModal = false"></div>

            {{-- Contenu modal --}}
            <div @click.stop x-transition
                class="relative z-10 w-full max-w-2xl rounded-2xl bg-white shadow-xl border border-black/5 p-5 space-y-4">

                {{-- Header --}}
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold flex items-center gap-2">
                            <x-lucide-badge-percent class="w-4 h-4" />
                            Créer une réduction
                        </h2>
                        <p class="mt-1 text-xs text-neutral-500">
                            Choisissez le type de réduction à configurer.
                        </p>
                    </div>
                    <button type="button" @click="openCreateModal = false" class="p-1 rounded-full hover:bg-neutral-100">
                        <x-lucide-x class="w-4 h-4 text-neutral-500" />
                    </button>
                </div>

                {{-- Choix des types, comme Shopify --}}
                <div class="grid grid-cols-1 gap-3">
                    {{-- Réduction sur les produits --}}
                    <a href="{{ route('discounts.create', ['type' => 'product']) }}"
                        class="group rounded-2xl border border-neutral-200 hover:border-neutral-900 hover:bg-neutral-50 transition-colors p-4 flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-neutral-900/5 p-2">
                                <x-lucide-tags class="w-4 h-4 text-neutral-800" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-semibold">Réduction sur les produits</span>
                                <span class="text-xxs text-neutral-500">
                                    Appliquez une remise sur des produits ou variantes spécifiques.
                                </span>
                            </div>
                        </div>
                    </a>

                    {{-- Réduction sur la commande --}}
                    <a href="{{ route('discounts.create', ['type' => 'order']) }}"
                        class="group rounded-2xl border border-neutral-200 hover:border-neutral-900 hover:bg-neutral-50 transition-colors p-4 flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-neutral-900/5 p-2">
                                <x-lucide-shopping-bag class="w-4 h-4 text-neutral-800" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-semibold">Réduction sur la commande</span>
                                <span class="text-xxs text-neutral-500">
                                    Remise globale selon le montant ou le contenu du panier.
                                </span>
                            </div>
                        </div>
                    </a>

                    {{-- Réduction sur les frais de port --}}
                    <a href="{{ route('discounts.create', ['type' => 'shipping']) }}"
                        class="group rounded-2xl border border-neutral-200 hover:border-neutral-900 hover:bg-neutral-50 transition-colors p-4 flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-neutral-900/5 p-2">
                                <x-lucide-truck class="w-4 h-4 text-neutral-800" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-semibold">Réduction sur les frais de port</span>
                                <span class="text-xxs text-neutral-500">
                                    Livraison gratuite ou remise sur les frais de port.
                                </span>
                            </div>
                        </div>
                    </a>

                    {{-- Achetez X, obtenez Y --}}
                    <a href="{{ route('discounts.create', ['type' => 'buy_x_get_y']) }}"
                        class="group rounded-2xl border border-neutral-200 hover:border-neutral-900 hover:bg-neutral-50 transition-colors p-4 flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-neutral-900/5 p-2">
                                <x-lucide-gift class="w-4 h-4 text-neutral-800" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-semibold">Achetez X, obtenez Y</span>
                                <span class="text-xxs text-neutral-500">
                                    Offrez des produits ou appliquez une remise selon les quantités achetées.
                                </span>
                            </div>
                        </div>
                    </a>
                </div>

                {{-- Footer optionnel --}}
                <div class="flex justify-end">
                    <button type="button" @click="openCreateModal = false"
                        class="text-xxs text-neutral-500 hover:text-neutral-700">
                        Annuler
                    </button>
                </div>
            </div>
        </div>

    </div>
@endsection
