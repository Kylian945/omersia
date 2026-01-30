@extends('admin::layout')

@section('title', "Commande #{$order->number}")
@section('page-title', "Commande #{$order->number}")

@section('content')
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">Commande #{{ $order->number }}</h1>
                <p class="text-xs text-neutral-500">
                    Passée le {{ $order->placed_at->format('d/m/Y à H:i') }}
                </p>
            </div>

            <a href="{{ route('admin.orders.index') }}"
                class="px-3 py-1.5 rounded-md border text-xs bg-white hover:bg-neutral-50">
                Retour
            </a>
        </div>

        {{-- Grid 2 colonnes --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

            {{-- Colonne Gauche – Détails commande --}}
            <div class="lg:col-span-2 space-y-3">

                {{-- Infos client --}}
                <div class="rounded-2xl bg-white border border-neutral-200 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <x-lucide-user class="w-4 h-4" />
                        <h2 class="text-sm font-semibold">Client</h2>
                    </div>

                    @if ($order->customer)
                        <p class="text-xs text-neutral-700">
                            {{ $order->customer->firstname }} {{ $order->customer->lastname }}
                        </p>
                        <p class="text-xs text-neutral-500">{{ $order->customer->email }}</p>
                    @else
                        <p class="text-xs text-neutral-500 italic">Client invité</p>
                    @endif
                </div>

                {{-- Adresse livraison --}}
                <div class="rounded-2xl bg-white border border-neutral-200 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <x-lucide-map-pin class="w-4 h-4" />
                        <h2 class="text-sm font-semibold">Adresse de livraison</h2>
                    </div>

                    <div class="text-xs text-neutral-700 space-y-1">
                        <p>{{ $order->shipping_address['line1'] }}</p>
                        @if (!empty($order->shipping_address['line2']))
                            <p>{{ $order->shipping_address['line2'] }}</p>
                        @endif
                        <p>
                            {{ $order->shipping_address['postcode'] }}
                            {{ $order->shipping_address['city'] }}
                        </p>
                        <p>{{ $order->shipping_address['country'] }}</p>

                        @if (!empty($order->shipping_address['phone']))
                            <p class="text-neutral-500">Tél : {{ $order->shipping_address['phone'] }}</p>
                        @endif
                    </div>
                </div>

                {{-- Adresse facturation --}}
                <div class="rounded-2xl bg-white border border-neutral-200 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <x-lucide-file-text class="w-4 h-4" />
                        <h2 class="text-sm font-semibold">Adresse de facturation</h2>
                    </div>

                    <div class="text-xs text-neutral-700 space-y-1">
                        <p>{{ $order->billing_address['line1'] }}</p>

                        @if (!empty($order->billing_address['line2']))
                            <p>{{ $order->billing_address['line2'] }}</p>
                        @endif

                        <p>
                            {{ $order->billing_address['postcode'] }}
                            {{ $order->billing_address['city'] }}
                        </p>
                        <p>{{ $order->billing_address['country'] }}</p>

                        @if (!empty($order->billing_address['phone']))
                            <p class="text-neutral-500">Tél : {{ $order->billing_address['phone'] }}</p>
                        @endif
                    </div>
                </div>

                {{-- Items --}}
                <div class="rounded-2xl bg-white border border-neutral-200 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <x-lucide-shopping-bag class="w-4 h-4" />
                        <h2 class="text-sm font-semibold">
                            Articles ({{ $order->items->count() }})
                        </h2>
                    </div>

                    <div class="divide-y divide-neutral-100 text-xs">
                        @foreach ($order->items as $item)
                            <div class="py-3 flex justify-between">
                                <div class="flex gap-2 items-center">
                                    <img class="w-10 h-10 rounded-md"
                                        src="{{ $item->product->mainImage->url }}" alt="image produit" />

                                    <div>
                                        <p class="font-medium">{{ $item->name }}</p>
                                        @if ($item->product->sku)
                                            <p class="text-neutral-500 text-xxs">SKU : {{ $item->product->sku }}</p>
                                        @elseif($item->variant && $item->variant->sku)
                                            <p class="text-neutral-500 text-xxs">SKU : {{ $item->variant->sku }}</p>
                                        @else
                                            <p class="text-neutral-500 text-xxs italic">NO SKU</p>
                                        @endif

                                        <p class="text-neutral-700">
                                            {{ $item->quantity }}
                                            × {{ number_format($item->unit_price, 2, ',', ' ') }} €
                                        </p>
                                    </div>
                                </div>

                                <div class="font-semibold">
                                    {{ number_format($item->total_price, 2, ',', ' ') }} €
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- Colonne droite – Résumé --}}
            <aside class="space-y-3">

                {{-- Statuts --}}
                <div class="rounded-2xl bg-white border border-neutral-200 p-4 text-xs">
                    <div class="flex items-center gap-2 mb-3">
                        <x-lucide-badge-check class="w-4 h-4" />
                        <h2 class="text-sm font-semibold">Statuts</h2>
                    </div>

                    @if (session('success'))
                        <div class="mb-3 p-2 bg-green-50 border border-green-200 rounded-lg text-green-700 text-xs">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="block font-medium mb-1">Statut commande</label>
                            <select name="status"
                                class="w-full rounded-lg border border-neutral-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-neutral-900">
                                <option value="draft" {{ $order->status === 'draft' ? 'selected' : '' }}>Brouillon
                                </option>
                                <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmée
                                </option>
                                <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>En
                                    préparation</option>
                                <option value="in_transit" {{ $order->status === 'in_transit' ? 'selected' : '' }}>En
                                    transit</option>
                                <option value="out_for_delivery"
                                    {{ $order->status === 'out_for_delivery' ? 'selected' : '' }}>En cours de livraison
                                </option>
                                <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Livrée
                                </option>
                                <option value="refunded" {{ $order->status === 'refunded' ? 'selected' : '' }}>Remboursée
                                </option>
                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Annulée
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">Statut paiement</label>
                            <select name="payment_status"
                                class="w-full rounded-lg border border-neutral-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-neutral-900">
                                <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Payée
                                </option>
                                <option value="unpaid" {{ $order->payment_status === 'unpaid' ? 'selected' : '' }}>Non payée
                                </option>
                                <option value="pending" {{ $order->payment_status === 'pending' ? 'selected' : '' }}>En
                                    attente</option>
                                <option value="refunded" {{ $order->payment_status === 'refunded' ? 'selected' : '' }}>
                                    Remboursée</option>
                                <option value="partially_refunded"
                                    {{ $order->payment_status === 'partially_refunded' ? 'selected' : '' }}>Partiellement
                                    remboursée</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">Statut fulfillment</label>
                            <select name="fulfillment_status"
                                class="w-full rounded-lg border border-neutral-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-neutral-900">
                                <option value="unfulfilled"
                                    {{ $order->fulfillment_status === 'unfulfilled' ? 'selected' : '' }}>Non traité
                                </option>
                                <option value="partial" {{ $order->fulfillment_status === 'partial' ? 'selected' : '' }}>
                                    Partiel</option>
                                <option value="fulfilled"
                                    {{ $order->fulfillment_status === 'fulfilled' ? 'selected' : '' }}>Traité</option>
                                <option value="canceled"
                                    {{ $order->fulfillment_status === 'canceled' ? 'selected' : '' }}>Annulé</option>
                            </select>
                        </div>

                        <div class="pt-2 border-t">
                            <h3 class="font-medium mb-2">Informations de suivi (optionnel)</h3>

                            <div class="space-y-2">
                                <div>
                                    <label class="block text-xxs text-neutral-600 mb-1">Numéro de suivi</label>
                                    <input type="text" name="tracking_number"
                                        value="{{ $order->meta['tracking']['number'] ?? '' }}"
                                        class="w-full rounded-lg border border-neutral-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                        placeholder="123456789">
                                </div>

                                <div>
                                    <label class="block text-xxs text-neutral-600 mb-1">URL de suivi</label>
                                    <input type="url" name="tracking_url"
                                        value="{{ $order->meta['tracking']['url'] ?? '' }}"
                                        class="w-full rounded-lg border border-neutral-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                        placeholder="https://track.carrier.com/...">
                                </div>

                                <div>
                                    <label class="block text-xxs text-neutral-600 mb-1">Transporteur</label>
                                    <input type="text" name="carrier"
                                        value="{{ $order->meta['tracking']['carrier'] ?? '' }}"
                                        class="w-full rounded-lg border border-neutral-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                        placeholder="Colissimo, Chronopost, UPS...">
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full px-3 py-2 rounded-lg bg-neutral-900 text-white text-xs font-medium hover:bg-black transition">
                            Mettre à jour les statuts
                        </button>
                    </form>
                </div>

                {{-- Livraison --}}
                <div class="rounded-2xl bg-white border border-neutral-200 p-4 text-xs space-y-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-lucide-truck class="w-4 h-4" />
                        <h2 class="text-sm font-semibold">Méthode de livraison</h2>
                    </div>

                    @if ($order->shippingMethod)
                        <p class="font-medium">{{ $order->shippingMethod->name }}</p>
                        <p class="text-neutral-500">
                            {{ number_format($order->shippingMethod->price, 2, ',', ' ') }} €
                        </p>
                        @if ($order->shippingMethod->delivery_time)
                            <p class="text-neutral-500">{{ $order->shippingMethod->delivery_time }}</p>
                        @endif
                    @else
                        <p class="text-neutral-500 italic">Aucune méthode enregistrée</p>
                    @endif

                    @if (!empty($order->meta['tracking']))
                        <div class="mt-3 pt-3 border-t space-y-1">
                            <p class="font-medium text-neutral-700">Informations de suivi</p>
                            @if (!empty($order->meta['tracking']['carrier']))
                                <p class="text-neutral-600">{{ $order->meta['tracking']['carrier'] }}</p>
                            @endif
                            @if (!empty($order->meta['tracking']['number']))
                                <p class="text-neutral-500">N° : {{ $order->meta['tracking']['number'] }}</p>
                            @endif
                            @if (!empty($order->meta['tracking']['url']))
                                <a href="{{ $order->meta['tracking']['url'] }}" target="_blank"
                                    class="inline-block text-blue-600 hover:underline">
                                    Suivre le colis →
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Totaux --}}
                <div class="rounded-2xl bg-white border border-neutral-200 p-4 text-xs space-y-2">
                    <div class="flex items-center gap-2 mb-2">
                        <x-lucide-wallet class="w-4 h-4" />
                        <h2 class="text-sm font-semibold">Résumé</h2>
                    </div>

                    <div class="flex justify-between">
                        <span>Sous-total</span>
                        <span>{{ number_format($order->subtotal, 2, ',', ' ') }} €</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Réduction</span>
                        <span>- {{ number_format($order->discount_total, 2, ',', ' ') }} €</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Livraison</span>
                        <span>{{ number_format($order->shipping_total, 2, ',', ' ') }} €</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Taxes</span>
                        <span>{{ number_format($order->tax_total, 2, ',', ' ') }} €</span>
                    </div>

                    <div class="pt-2 border-t flex justify-between font-semibold text-neutral-900">
                        <span>Total</span>
                        <span>{{ number_format($order->total, 2, ',', ' ') }} €</span>
                    </div>
                </div>

            </aside>

        </div>

    </div>
@endsection
