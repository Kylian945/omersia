@extends('admin::layout')

@section('title', 'Tableau de bord')
@section('page-title', 'Vue d’ensemble de la boutique')

@section('content')
    {{-- Barre de filtres de période --}}
    <div class="rounded-2xl bg-white p-3 shadow-sm border border-black/5 mb-4">

        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="text-xxs text-gray-500 font-medium">Période :</span>
                <div class="flex gap-1">
                    <a href="{{ route('admin.dashboard', ['period' => 'day']) }}"
                        class="px-3 py-1 text-xs rounded-lg {{ $period === 'day' ? 'bg-black text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        Jour
                    </a>
                    <a href="{{ route('admin.dashboard', ['period' => 'week']) }}"
                        class="px-3 py-1 text-xs rounded-lg {{ $period === 'week' ? 'bg-black text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        Semaine
                    </a>
                    <a href="{{ route('admin.dashboard', ['period' => 'month']) }}"
                        class="px-3 py-1 text-xs rounded-lg {{ $period === 'month' ? 'bg-black text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        Mois
                    </a>
                    <a href="{{ route('admin.dashboard', ['period' => 'year']) }}"
                        class="px-3 py-1 text-xs rounded-lg {{ $period === 'year' ? 'bg-black text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        Année
                    </a>
                </div>
            </div>
            {{-- Filtre de dates personnalisées --}}
            <div class="flex items-center gap-3">
                <span class="text-xxs text-gray-500 font-medium">Personnalisé :</span>
                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <input type="hidden" name="period" value="custom">
                    <div class="flex items-center gap-2">
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="px-2 py-1 text-xs rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent"
                            placeholder="Du">
                        <span class="text-xxs text-gray-400">→</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="px-2 py-1 text-xs rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent"
                            placeholder="Au">
                    </div>
                    <button type="submit"
                        class="px-3 py-1 text-xs rounded-lg bg-black text-white hover:bg-gray-800 transition-colors">
                        Appliquer
                    </button>
                    @if ($period === 'custom')
                        <a href="{{ route('admin.dashboard') }}"
                            class="px-3 py-1 text-xs rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                            Réinitialiser
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Graphique des commandes de la période --}}
    <div class="rounded-2xl bg-white p-4 shadow-sm border border-black/5 mb-4">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-800">Commandes - {{ $periodLabel }}</h2>
                <p class="text-xxs text-gray-500 mt-0.5">Évolution quotidienne</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-xxs text-gray-400">
                    <span id="orders-chart-total">{{ array_sum($chartData) }}</span> commandes
                </div>
                {{-- Bouton Export --}}
                <div class="relative">
                    <button id="exportButton"
                        class="inline-flex items-center gap-1.5 px-3 py-1 text-xs rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                        <x-lucide-download class="w-3.5 h-3.5" />
                        Exporter
                    </button>
                    {{-- Menu déroulant --}}
                    <div id="exportMenu"
                        class="hidden absolute right-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50 min-w-[120px]">
                        <a href="{{ route('admin.dashboard.export', array_merge(request()->all(), ['format' => 'csv'])) }}"
                            class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                            <x-lucide-file-spreadsheet class="w-3.5 h-3.5" />
                            CSV
                        </a>
                        <a href="{{ route('admin.dashboard.export', array_merge(request()->all(), ['format' => 'pdf'])) }}"
                            class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                            <x-lucide-file-text class="w-3.5 h-3.5" />
                            PDF
                        </a>
                        <a href="{{ route('admin.dashboard.export', array_merge(request()->all(), ['format' => 'txt'])) }}"
                            class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                            <x-lucide-file-text class="w-3.5 h-3.5" />
                            TXT
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="relative" style="height: 120px;">
            <canvas id="ordersChart"></canvas>
        </div>
    </div>

    {{-- Cartes stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        {{-- Ventes de la période --}}
        <div class="rounded-2xl bg-white p-3 shadow-sm border border-black/5">
            <div class="text-xxxs text-gray-500">Ventes - {{ $periodLabel }}</div>
            <div id="kpi-period-sales" class="mt-1 text-lg font-semibold tracking-tight">
                {{ number_format($periodSales ?? 0, 2, ',', ' ') }} €
            </div>

            @if (!is_null($salesDiffPercent ?? null))
                @php
                    $isUp = ($salesDiffDirection ?? null) === 'up';
                @endphp
                <div
                    id="kpi-sales-diff"
                    data-compare-label="{{ $compareLabel }}"
                    class="mt-1 text-xxxs {{ $isUp ? 'text-emerald-500' : 'text-red-500' }}"
                >
                    {{ $isUp ? '+' : '-' }}{{ number_format($salesDiffPercent, 1, ',', ' ') }}% vs {{ $compareLabel }}
                </div>
            @else
                <div
                    id="kpi-sales-diff"
                    data-compare-label="{{ $compareLabel }}"
                    class="mt-1 text-xxxs text-gray-400"
                >
                    Pas de comparaison disponible
                </div>
            @endif
        </div>

        {{-- Commandes de la période --}}
        <div class="rounded-2xl bg-white p-3 shadow-sm border border-black/5">
            <div class="text-xxxs text-gray-500">Commandes - {{ $periodLabel }}</div>
            <div id="kpi-period-orders-count" class="mt-1 text-lg font-semibold">
                {{ $periodOrdersCount ?? 0 }}
            </div>
            <div id="kpi-average-order-value" class="mt-1 text-xxxs text-gray-400">
                Moyenne {{ $periodOrdersCount ? number_format($averageOrderValue, 2, ',', ' ') . ' €' : '—' }}
            </div>
        </div>

        {{-- Taux de conversion (placeholder) --}}
        <div class="rounded-2xl bg-white p-3 shadow-sm border border-black/5">
            <div class="text-xxxs text-gray-500">Taux de conversion</div>
            <div class="mt-1 text-lg font-semibold">
                {{-- À calculer quand tu auras les sessions / visites --}}
                —
            </div>
            <div class="mt-1 text-xxxs text-gray-400">Objectif : 3,0%</div>
        </div>

        {{-- Produits actifs --}}
        <div class="rounded-2xl bg-white p-3 shadow-sm border border-black/5">
            <div class="text-xxxs text-gray-500">Produits actifs</div>
            <div class="mt-1 text-lg font-semibold">{{ $productsCount ?? '—' }}</div>
            <div class="mt-1 text-xxxs text-gray-400">
                <a href="{{ route('products.index') }}" class="underline">Gérer le catalogue</a>
            </div>
        </div>

        {{-- Paniers actifs (temps réel) --}}
        <div class="rounded-2xl bg-white p-3 shadow-sm border border-black/5">
            <div class="text-xxxs text-gray-500">Paniers actifs</div>
            <div id="active-carts-count" class="mt-1 text-lg font-semibold">
                {{ $activeCartsCount ?? '—' }}
            </div>
            <div class="mt-1 text-xxxs text-gray-400">
                Mis à jour en temps réel
            </div>
        </div>
    </div>

    {{-- Grille principale --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
        {{-- Dernières commandes --}}
        <div class="lg:col-span-2 rounded-2xl bg-white p-3 shadow-sm border border-black/5">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <div class="text-xs font-semibold text-gray-800">Dernières commandes</div>
                </div>
                <span id="latest-orders-count" class="text-xxxs text-gray-400">
                    {{ isset($lastOrders) ? $lastOrders->count() . ' dernières' : 'Bientôt' }}
                </span>
            </div>

            <table class="w-full text-xs text-gray-700">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="py-1 text-left font-medium text-gray-400">N°</th>
                        <th class="py-1 text-left font-medium text-gray-400">Client</th>
                        <th class="py-1 text-left font-medium text-gray-400">Montant</th>
                        <th class="py-1 text-left font-medium text-gray-400">Paiement</th>
                        <th class="py-1 text-left font-medium text-gray-400">Status</th>
                    </tr>
                </thead>
                <tbody id="latest-orders-body">
                    @forelse($lastOrders ?? [] as $order)
                        <tr class="border-b border-gray-50">
                            <td class="py-1">
                                <a href="{{ route('admin.orders.show', $order->id) }}"
                                    class="text-xs font-semibold text-neutral-900 hover:underline">
                                    #{{ $order->number ?? $order->id }}
                                </a>
                            </td>
                            <td class="py-1">
                                {{ $order->customer_name ?? (substr($order->customer->firstname, 0, 1) . '.' . $order->customer->lastname ?? '—') }}
                            </td>
                            <td class="py-1">
                                {{ number_format($order->total ?? 0, 2, ',', ' ') }} €
                            </td>
                            <td class="py-1">
                                @php
                                    $status = $order->payment_status ?? 'unknown';
                                    $statusClasses = match ($status) {
                                        'paid' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                        'pending',
                                        'awaiting_payment'
                                            => 'bg-yellow-50 text-yellow-700 border-yellow-100',
                                        'cancelled' => 'bg-red-50 text-red-600 border-red-100',
                                        default => 'bg-gray-50 text-gray-600 border-gray-100',
                                    };
                                    $statusLabel = match ($status) {
                                        'paid' => 'Payée',
                                        'pending', 'awaiting_payment' => 'En attente',
                                        'cancelled' => 'Annulée',
                                        default => ucfirst($status),
                                    };
                                @endphp
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xxxs border {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="py-1">
                                @php
                                    $statusMap = [
                                        'confirmed' => 'bg-blue-50 text-blue-700 border-blue-100',
                                        'processing' => 'bg-sky-50 text-sky-700 border-indigo-100',
                                        'in_transit' => 'bg-cyan-50 text-cyan-700 border-cyan-100',
                                        'out_for_delivery' => 'bg-teal-50 text-teal-700 border-teal-100',
                                        'delivered' => 'bg-lime-50 text-lime-700 border-lime-100',
                                        'refunded' => 'bg-gray-50 text-gray-700 border-gray-100',
                                        'cancelled' => 'bg-red-50 text-red-700 border-red-100',
                                    ];
                                    $statusClass =
                                        $statusMap[$order->status] ??
                                        'bg-neutral-50 text-neutral-700 border-neutral-100';
                                @endphp
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xxxs border {{ $statusClass }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-2 text-xxxs text-gray-400" colspan="5">
                                Aucune commande pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Raccourcis / tâches --}}
        <div class="rounded-2xl bg-white p-3 shadow-sm border border-black/5 space-y-2">
            <div class="text-xs font-semibold text-gray-800">Actions rapides</div>

            <a href="{{ route('products.create') }}"
                class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-xs text-gray-900 hover:bg-gray-100">
                <span>Ajouter un nouveau produit</span>
                <span class="text-sm">+</span>
            </a>

            <a href="{{ route('categories.create') }}"
                class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-xs text-gray-800 hover:bg-gray-100">
                <span>Créer une catégorie</span>
                <span class="text-xs"><x-lucide-folders class="w-4 h-4" /></span>
            </a>

            <a href="{{ route('pages.create') }}"
                class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-xs text-gray-800 hover:bg-gray-100">
                <span>Nouvelle page CMS</span>
                <span class="text-xs"><x-lucide-file-text class="w-4 h-4" /></span>
            </a>

            <div class="mt-3 pt-2 border-t border-gray-100 text-xxxs text-gray-500">
                Tout est pensé pour évoluer : métriques, filtres, onglets, vues sauvegardées, etc.
            </div>
        </div>
    </div>

    <script>
        window.dashboardConfig = {
            chartLabels: @json($chartLabels),
            chartData: @json($chartData),
            chartPreviousData: @json($chartPreviousData),
            todayIndex: @json($todayIndex),
            activeCartsRoute: "{{ route('admin.metrics.active-carts') }}",
            ordersChartRoute: "{{ route('admin.metrics.orders-chart', request()->only(['period', 'date_from', 'date_to'])) }}",
            latestOrdersRoute: "{{ route('admin.metrics.latest-orders') }}"
        };
    </script>
    @vite(['packages/Admin/src/resources/js/dashboard-chart.js', 'packages/Admin/src/resources/js/dashboard.js'])
@endsection
