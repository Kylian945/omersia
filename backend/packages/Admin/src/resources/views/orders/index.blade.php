@extends('admin::layout')

@section('title', 'Commandes')
@section('page-title', 'Commandes')

@section('content')
    <div x-data="{ q: @js($filters['q'] ?? '') }" class="space-y-4">

        {{-- Header / barre supérieure --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                    <x-lucide-shopping-cart class="w-3 h-3" />
                    Commandes | ({{ $orders->total() }})
                </div>
                <div class="text-xs text-gray-500">
                    Gérez les commandes de votre boutique.
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button"
                    class="font-semibold inline-flex items-center justify-center rounded-md border border-neutral-200 text-xs px-4 py-1.5 bg-white hover:bg-neutral-50">
                    Exporter
                </button>
                <button type="button"
                    class="font-semibold inline-flex items-center justify-center rounded-md bg-neutral-900 text-white text-xs px-4 py-1.5 hover:bg-black">
                    Créer une commande
                </button>
            </div>
        </div>

        {{-- Filtres / barre de recherche (Shopify-like) --}}


        {{-- Listing des commandes façon Shopify --}}
        <div class="rounded-xl bg-white border border-neutral-200 shadow-sm overflow-hidden">
            {{-- Barre bulk actions (comme Shopify quand une ligne est cochée – pour l’instant statique) --}}
            <form method="GET" class="rounded-xl bg-white" x-ref="searchForm">
                <div
                    class="px-3 py-2 border-b border-neutral-100 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                    {{-- Recherche --}}
                    <div class="flex-1 flex items-center gap-2">
                        <div class="relative w-full">
                            <span
                                class="pointer-events-none absolute top-2 left-2 flex items-center text-neutral-400 text-xs">
                                <x-lucide-search class="w-4 h-4" />
                            </span>
                            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" x-model="q"
                                @input.debounce.300ms="$refs.searchForm.requestSubmit()"
                                class="w-full rounded-md border-0 pl-7 pr-2 py-1.5 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-neutral-900/5"
                                placeholder="Rechercher une commande, un client, un e-mail…" />
                        </div>
                    </div>

                    {{-- Filtres rapides --}}
                    <div class="flex flex-wrap gap-2 justify-start md:justify-end">
                        {{-- Statut commande --}}
                        <select name="status"
                            class="rounded-md border border-neutral-200 px-2 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-neutral-900/5">
                            <option value="">Tous les statuts</option>
                            @php
                                $statuses = [
                                    'confirmed' => 'Confirmé',
                                    'processing' => 'En préparation',
                                    'in_transit' => 'En transit',
                                    'out_for_delivery' => 'En cours de livraison',
                                    'delivered' => 'Livré',
                                    'refunded' => 'Remboursée',
                                    'cancelled' => 'Annulée',
                                ];
                            @endphp
                            @foreach ($statuses as $k => $v)
                                <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $v }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Paiement --}}
                        <select name="payment"
                            class="rounded-md border border-neutral-200 px-2 pr-8 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-neutral-900/5">
                            <option value="">Paiement (tous)</option>
                            @php
                                $payments = [
                                    'paid' => 'Payé',
                                    'unpaid' => 'Impayé',
                                    'pending' => 'En attente',
                                    'refunded' => 'Remboursé',
                                    'partially_refunded' => 'Part. remboursé',
                                ];
                            @endphp
                            @foreach ($payments as $k => $v)
                                <option value="{{ $k }}" @selected(($filters['payment'] ?? '') === $k)>{{ $v }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Dates (menu simple) --}}
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
                            <option value="placed_at_desc" @selected(($filters['sort'] ?? '') === 'placed_at_desc')>
                                Date ↓
                            </option>
                            <option value="placed_at_asc" @selected(($filters['sort'] ?? '') === 'placed_at_asc')>
                                Date ↑
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

                        <a href="{{ route('admin.orders.index') }}"
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
                                    aria-label="Sélectionner toutes les commandes" />
                            </th>
                            <th class="px-3 py-2">Commande</th>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Client</th>
                            <th class="px-3 py-2">Paiement</th>
                            <th class="px-3 py-2">Fulfillment</th>
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
                                        aria-label="Sélectionner la commande #{{ $order->number }}" />
                                </td>

                                {{-- Commande (numéro + statut) --}}
                                <td class="px-3 py-2 align-top">
                                    <a href="{{ route('admin.orders.show', $order->id) }}"
                                        class="text-sm font-semibold text-neutral-900 hover:underline">
                                        #{{ $order->number }}
                                    </a>
                                    <div class="mt-1">
                                        @php
                                            $statusMap = [
                                                'confirmed' => 'bg-blue-50 text-blue-700 border-blue-100',
                                                'processing' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                                'in_transit' => 'bg-yellow-50 text-yellow-700 border-yellow-100',
                                                'out_for_delivery' => 'bg-amber-50 text-amber-700 border-amber-100',
                                                'delivered' => 'bg-green-50 text-green-700 border-green-100',
                                                'refunded' => 'bg-gray-50 text-gray-700 border-gray-100',
                                                'cancelled' => 'bg-red-50 text-red-700 border-red-100',
                                            ];
                                            $statusClass =
                                                $statusMap[$order->status] ??
                                                'bg-neutral-50 text-neutral-700 border-neutral-100';
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-medium border {{ $statusClass }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs text-neutral-400">
                                        {{ $order->items_count }} article{{ $order->items_count > 1 ? 's' : '' }}
                                    </div>
                                </td>

                                {{-- Date --}}
                                <td class="px-3 py-2 align-top text-neutral-700">
                                    {{ optional($order->placed_at)->format('d M Y') }}<br>
                                    <span class="text-xs text-neutral-400">
                                        {{ optional($order->placed_at)->format('H:i') }}
                                    </span>
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

                                {{-- Paiement --}}
                                <td class="px-3 py-2 align-top">
                                    @php
                                        $payMap = [
                                            'paid' => 'bg-green-50 text-green-700 border-green-100',
                                            'unpaid' => 'bg-red-50 text-red-700 border-red-100',
                                            'pending' => 'bg-yellow-50 text-yellow-700 border-yellow-100',
                                            'refunded' => 'bg-gray-50 text-gray-700 border-gray-100',
                                            'partially_refunded' => 'bg-gray-50 text-gray-700 border-gray-100',
                                        ];
                                        $payLabel =
                                            [
                                                'paid' => 'Payé',
                                                'unpaid' => 'Impayé',
                                                'pending' => 'En attente',
                                                'refunded' => 'Remboursé',
                                                'partially_refunded' => 'Partiellement remboursé',
                                            ][$order->payment_status] ?? ucfirst($order->payment_status);
                                        $payClass =
                                            $payMap[$order->payment_status] ??
                                            'bg-neutral-50 text-neutral-700 border-neutral-100';
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-medium border {{ $payClass }}">
                                        {{ $payLabel }}
                                    </span>
                                </td>

                                {{-- Fulfillment --}}
                                <td class="px-3 py-2 align-top">
                                    @php
                                        $fulfillMap = [
                                            'unfulfilled' => 'bg-neutral-50 text-neutral-700 border-neutral-100',
                                            'partial' => 'bg-blue-50 text-blue-700 border-blue-100',
                                            'fulfilled' => 'bg-green-50 text-green-700 border-green-100',
                                            'canceled' => 'bg-red-50 text-red-700 border-red-100',
                                        ];
                                        $fulfillLabel =
                                            [
                                                'unfulfilled' => 'Non traité',
                                                'partial' => 'Partiellement traité',
                                                'fulfilled' => 'Traité',
                                                'canceled' => 'Annulé',
                                            ][$order->fulfillment_status] ?? ucfirst($order->fulfillment_status);
                                        $fulfillClass =
                                            $fulfillMap[$order->fulfillment_status] ??
                                            'bg-neutral-50 text-neutral-700 border-neutral-100';
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-medium border {{ $fulfillClass }}">
                                        {{ $fulfillLabel }}
                                    </span>
                                </td>

                                {{-- Total --}}
                                <td class="px-3 py-2 align-top text-right">
                                    <div class="font-semibold text-neutral-900">
                                        {{ number_format($order->total, 2, ',', ' ') }} {{ $order->currency }}
                                    </div>
                                    @if ($order->shipping_total > 0)
                                        <div class="text-xs text-neutral-400">
                                            {{ number_format($order->shipping_total, 2, ',', ' ') }} € de livraison
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-neutral-500">
                                    Aucune commande trouvée avec ces filtres.
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
    </div>
@endsection

@push('scripts')
    <script>
        window.omersiaOrderRealtimeConfig = {
            orderId: null,
        };
    </script>
    @vite(['packages/Admin/src/resources/js/orders/realtime.js'])
@endpush
