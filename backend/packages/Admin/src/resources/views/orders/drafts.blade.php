@extends('admin::layout')

@section('title', 'Paniers abandonnés')
@section('page-title', 'Paniers abandonnés')

@section('content')
    <div class="space-y-4">

        {{-- Header / barre supérieure --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                    <x-lucide-shopping-bag class="w-3 h-3" />
                    Paniers abandonnés | ({{ $orders->total() }})
                </div>
                <div class="text-xs text-gray-500">
                    Commandes en brouillon (checkout non finalisé).
                </div>
            </div>
            <a href="{{ route('admin.orders.index') }}"
                class="font-semibold inline-flex items-center justify-center rounded-md border border-neutral-200 text-xs px-4 py-1.5 bg-white hover:bg-neutral-50">
                <x-lucide-arrow-left class="w-3 h-3 mr-1" />
                Retour aux commandes
            </a>
        </div>

        {{-- Filtres / barre de recherche --}}
        <div class="rounded-xl bg-white border border-neutral-200 shadow-sm overflow-hidden">
            <form method="GET" class="rounded-xl bg-white">
                <div
                    class="px-3 py-2 border-b border-neutral-100 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                    {{-- Recherche --}}
                    <div class="flex-1 flex items-center gap-2">
                        <div class="relative w-full">
                            <span
                                class="pointer-events-none absolute top-2 left-2 flex items-center text-neutral-400 text-xs">
                                <x-lucide-search class="w-4 h-4" />
                            </span>
                            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                                class="w-full rounded-md border-0 pl-7 pr-2 py-1.5 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-neutral-900/5"
                                placeholder="Rechercher un panier, un client, un e-mail…" />
                        </div>
                    </div>

                    {{-- Filtres rapides --}}
                    <div class="flex flex-wrap gap-2 justify-start md:justify-end">
                        {{-- Dates --}}
                        <div class="flex items-center gap-2">
                            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                                class="rounded-md border border-neutral-200 px-2 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-neutral-900/5" />
                            <span class="text-xs text-neutral-400">→</span>
                            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                                class="rounded-md border border-neutral-200 px-2 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-neutral-900/5" />
                        </div>

                        {{-- Tri --}}
                        <select name="sort"
                            class="rounded-md border border-neutral-200 px-2 pr-8 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-neutral-900/5">
                            <option value="updated_at_desc" @selected(($filters['sort'] ?? '') === 'updated_at_desc')>
                                Dernière modification ↓
                            </option>
                            <option value="updated_at_asc" @selected(($filters['sort'] ?? '') === 'updated_at_asc')>
                                Dernière modification ↑
                            </option>
                            <option value="total_desc" @selected(($filters['sort'] ?? '') === 'total_desc')>
                                Total ↓
                            </option>
                            <option value="total_asc" @selected(($filters['sort'] ?? '') === 'total_asc')>
                                Total ↑
                            </option>
                        </select>

                        <button type="submit"
                            class="font-semibold inline-flex items-center justify-center rounded-md bg-neutral-900 text-white text-xs px-4 py-1.5 hover:bg-black">
                            Filtrer
                        </button>

                        <a href="{{ route('admin.orders.drafts') }}"
                            class="font-semibold inline-flex items-center justify-center rounded-md border border-neutral-200 text-xs px-4 py-1.5 bg-white hover:bg-neutral-50">
                            Réinitialiser
                        </a>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="text-xs text-neutral-500 border-b border-neutral-100 bg-neutral-50/60">
                        <tr>
                            <th class="px-3 py-2 w-10">
                                <input type="checkbox"
                                    class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900"
                                    aria-label="Sélectionner tous les paniers" />
                            </th>
                            <th class="px-3 py-2">Numéro</th>
                            <th class="px-3 py-2">Dernière modification</th>
                            <th class="px-3 py-2">Client</th>
                            <th class="px-3 py-2">Articles</th>
                            <th class="px-3 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @forelse($orders as $order)
                            <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                                {{-- Checkbox ligne --}}
                                <td class="px-3 py-2 align-top">
                                    <input type="checkbox"
                                        class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900"
                                        aria-label="Sélectionner le panier #{{ $order->number }}" />
                                </td>

                                {{-- Numéro --}}
                                <td class="px-3 py-2 align-top">
                                    <a href="{{ route('admin.orders.show', $order->id) }}"
                                        class="text-sm font-semibold text-neutral-900 hover:underline">
                                        #{{ $order->number }}
                                    </a>
                                    <div class="mt-1">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-medium border bg-amber-50 text-amber-700 border-amber-100">
                                            Brouillon
                                        </span>
                                    </div>
                                </td>

                                {{-- Date de dernière modification --}}
                                <td class="px-3 py-2 align-top text-neutral-700">
                                    {{ optional($order->updated_at)->format('d M Y') }}<br>
                                    <span class="text-xs text-neutral-400">
                                        {{ optional($order->updated_at)->format('H:i') }}
                                    </span>
                                    <div class="text-xs text-neutral-400 mt-1">
                                        Créé le {{ optional($order->created_at)->format('d M Y') }}
                                    </div>
                                </td>

                                {{-- Client --}}
                                <td class="px-3 py-2 align-top">
                                    @php
                                        $name = trim(
                                            ($order->customer_firstname ?? '') .
                                                ' ' .
                                                ($order->customer_lastname ?? ''),
                                        );
                                    @endphp
                                    <div class="text-neutral-900">
                                        {{ $name ?: '—' }}
                                    </div>
                                    @if ($order->customer_email)
                                        <div class="text-xs text-neutral-500">
                                            {{ $order->customer_email }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Articles --}}
                                <td class="px-3 py-2 align-top">
                                    <div class="text-neutral-700">
                                        {{ $order->items_count }} article{{ $order->items_count > 1 ? 's' : '' }}
                                    </div>
                                </td>

                                {{-- Total --}}
                                <td class="px-3 py-2 align-top text-right">
                                    <div class="font-semibold text-neutral-900">
                                        {{ number_format($order->total, 2, ',', ' ') }} {{ $order->currency }}
                                    </div>
                                    @if ($order->shipping_total > 0)
                                        <div class="text-xs text-neutral-400">
                                            + {{ number_format($order->shipping_total, 2, ',', ' ') }} € livraison
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-neutral-500">
                                    Aucun panier abandonné trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-3 py-2 border-t border-neutral-100">
                {{ $orders->links() }}
            </div>
        </div>

        {{-- Info supplémentaire --}}
        <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-xs text-blue-800">
            <div class="flex items-start gap-2">
                <x-lucide-info class="w-4 h-4 flex-shrink-0 mt-0.5" />
                <div>
                    <strong>À propos des paniers abandonnés :</strong>
                    Ces commandes ont été créées pendant le checkout mais n'ont jamais été finalisées (pas de paiement).
                    Elles n'apparaissent pas dans la liste des commandes principales.
                    Vous pouvez les consulter pour analyser les abandons de panier ou les convertir manuellement en commande.
                </div>
            </div>
        </div>
    </div>
@endsection
