@extends('admin::settings.layout')

@section('settings-content')

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        {{-- Clés API --}}
        <a href="{{ route('admin.settings.api-keys.index') }}"
            class="group rounded-2xl bg-white border border-black/5 shadow-sm hover:shadow-md transition-all duration-150 p-4 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-6 w-6 rounded-lg bg-gray-900 flex items-center justify-center text-white text-xs font-semibold">
                            <x-lucide-key-round class="w-3 h-3" />
                        </div>
                        <div class="text-xs font-semibold text-gray-800">Clés API</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-3.5 w-3.5 text-gray-400 group-hover:text-gray-700 transition" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <div class="text-xxxs text-gray-500 leading-snug">
                    Créez, révoquez et gérez les clés API utilisées par vos intégrations et frontends.
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between text-xxxs">
                        <span class="text-gray-600">Clés actives</span>
                        <span class="font-semibold text-gray-900">{{ $configData['api_keys_active'] }} / {{ $configData['api_keys_count'] }}</span>
                    </div>
                </div>
            </div>
        </a>

        {{-- Paiements --}}
        <a href="{{ route('admin.settings.payments.index') }}"
            class="group rounded-2xl bg-white border border-black/5 shadow-sm hover:shadow-md transition-all duration-150 p-4 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-6 w-6 rounded-lg bg-green-600 flex items-center justify-center text-white text-xs font-semibold">
                            <x-lucide-credit-card class="w-3 h-3" />
                        </div>
                        <div class="text-xs font-semibold text-gray-800">Paiements</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-3.5 w-3.5 text-gray-400 group-hover:text-gray-700 transition" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <div class="text-xxxs text-gray-500 leading-snug">
                    Configurez vos fournisseurs de paiement (Stripe, PayPal, etc.).
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between text-xxxs">
                        <span class="text-gray-600">Fournisseurs actifs</span>
                        <span class="font-semibold text-gray-900">{{ $configData['payment_providers_enabled'] }}</span>
                    </div>
                </div>
            </div>
        </a>

        {{-- Méthodes de livraison --}}
        <a href="{{ route('admin.settings.shipping_methods.index') }}"
            class="group rounded-2xl bg-white border border-black/5 shadow-sm hover:shadow-md transition-all duration-150 p-4 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-6 w-6 rounded-lg bg-blue-600 flex items-center justify-center text-white text-xs font-semibold">
                            <x-lucide-truck class="w-3 h-3" />
                        </div>
                        <div class="text-xs font-semibold text-gray-800">Livraison</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-3.5 w-3.5 text-gray-400 group-hover:text-gray-700 transition" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <div class="text-xxxs text-gray-500 leading-snug">
                    Gérez les méthodes et tarifs de livraison pour vos commandes.
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between text-xxxs">
                        <span class="text-gray-600">Méthodes disponibles</span>
                        <span class="font-semibold text-gray-900">{{ $configData['shipping_methods_count'] }}</span>
                    </div>
                </div>
            </div>
        </a>

        {{-- Taxes --}}
        <a href="{{ route('admin.settings.taxes.index') }}"
            class="group rounded-2xl bg-white border border-black/5 shadow-sm hover:shadow-md transition-all duration-150 p-4 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-6 w-6 rounded-lg bg-purple-600 flex items-center justify-center text-white text-xs font-semibold">
                            <x-lucide-receipt class="w-3 h-3" />
                        </div>
                        <div class="text-xs font-semibold text-gray-800">Taxes</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-3.5 w-3.5 text-gray-400 group-hover:text-gray-700 transition" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <div class="text-xxxs text-gray-500 leading-snug">
                    Configurez les zones fiscales et les taux de taxes applicables.
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between text-xxxs">
                        <span class="text-gray-600">Taxes configurées</span>
                        <span class="font-semibold text-gray-900">{{ $configData['tax_rates_count'] }}</span>
                    </div>
                </div>
            </div>
        </a>

        {{-- Meilisearch --}}
        <a href="{{ route('admin.settings.meilisearch.index') }}"
            class="group rounded-2xl bg-white border border-black/5 shadow-sm hover:shadow-md transition-all duration-150 p-4 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-6 w-6 rounded-lg bg-pink-600 flex items-center justify-center text-white text-xs font-semibold">
                            <x-lucide-search class="w-3 h-3" />
                        </div>
                        <div class="text-xs font-semibold text-gray-800">Meilisearch</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-3.5 w-3.5 text-gray-400 group-hover:text-gray-700 transition" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <div class="text-xxxs text-gray-500 leading-snug">
                    Gérez l'indexation et la synchronisation de vos données de recherche.
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between text-xxxs">
                        <span class="text-gray-600">Produits indexés</span>
                        <span class="font-semibold text-gray-900">{{ $configData['products_indexed'] }} / {{ $configData['products_count'] }}</span>
                    </div>
                </div>
            </div>
        </a>

        {{-- GDPR / RGPD --}}
        <a href="{{ route('admin.settings.gdpr.index') }}"
            class="group rounded-2xl bg-white border border-black/5 shadow-sm hover:shadow-md transition-all duration-150 p-4 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-6 w-6 rounded-lg bg-red-600 flex items-center justify-center text-white text-xs font-semibold">
                            <x-lucide-shield-check class="w-3 h-3" />
                        </div>
                        <div class="text-xs font-semibold text-gray-800">RGPD / GDPR</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-3.5 w-3.5 text-gray-400 group-hover:text-gray-700 transition" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <div class="text-xxxs text-gray-500 leading-snug">
                    Gérez les demandes RGPD : accès, export et suppression des données personnelles.
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between text-xxxs">
                        <span class="text-gray-600">Demandes en attente</span>
                        <span class="font-semibold text-gray-900">{{ \Omersia\Gdpr\Models\DataRequest::where('status', 'pending')->count() }}</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
@endsection
