@extends('admin::settings.layout')

@section('title', 'Méthodes de paiement')
@section('page-title', 'Méthodes de paiement')

@section('settings-content')
    <div class="space-y-6">

        {{-- Intro --}}
        <div class="space-y-1">
            <p class="text-xs text-neutral-500">
                Configurez les moyens de paiement disponibles sur votre boutique.
            </p>
            <p class="text-xs text-neutral-500">
                Stripe est intégré nativement. Un moyen de paiement de test (sans Stripe) est actif par défaut.
            </p>
            <p class="text-xs text-neutral-500">
                Les autres moyens de paiement peuvent être ajoutés via des modules.
            </p>
        </div>

        {{-- Cartes résumé Stripe / Modules --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Stripe core --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <x-lucide-credit-card class="w-4 h-4 text-emerald-500" />
                        <div>
                            <div class="text-sm font-semibold text-neutral-900">Stripe</div>
                            <div class="text-xxs text-neutral-500">Moyen de paiement natif</div>
                        </div>
                    </div>
                    @if ($stripeEnabled ?? false)
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-medium bg-emerald-50 text-emerald-600">
                            Actif
                        </span>
                    @else
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-medium bg-neutral-100 text-neutral-600">
                            Inactif
                        </span>
                    @endif
                </div>

                <p class="text-xs text-neutral-600">
                    Stripe est fourni par défaut par l’application. Gérez les clés API, le mode test/production
                    et les options avancées depuis cette interface.
                </p>

                <div class="flex items-center justify-between gap-3 text-xs">
                    <div class="space-y-0.5 text-xxs text-neutral-500">
                        <div>Mode :
                            <span class="font-medium text-neutral-800">
                                {{ $stripeMode ?? 'test' }}
                            </span>
                        </div>
                        <div>Devise principale :
                            <span class="font-medium text-neutral-800">
                                {{ $primaryCurrency ?? 'EUR' }}
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('admin.settings.payments.stripe') }}"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs bg-neutral-900 text-white hover:bg-black transition">
                        Configurer Stripe
                        <x-lucide-arrow-right class="w-3 h-3" />
                    </a>
                </div>
            </div>

            {{-- Modules de paiement --}}
            <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <x-lucide-puzzle class="w-4 h-4 text-indigo-500" />
                        <div>
                            <div class="text-sm font-semibold text-neutral-900">Modules de paiement</div>
                            <div class="text-xxs text-neutral-500">PayPal, Alma, Monetico, etc.</div>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-neutral-600">
                    Installez des modules pour ajouter de nouveaux moyens de paiement (PayPal,
                    paiement en plusieurs fois, solutions bancaires…). Chaque module expose son
                    propre écran de configuration.
                </p>

                <div class="flex items-center justify-between gap-3 text-xs">
                    <div class="text-xxs text-neutral-500">
                        {{ $modulesCount ?? 0 }} module(s) de paiement installé(s)
                    </div>
                    {{-- Lien vers ton gestionnaire de modules, ou autre --}}
                    <a href="{{ route('admin.modules.index', ['type' => 'payment']) }}"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs bg-white hover:bg-neutral-50">
                        Gérer les modules
                        <x-lucide-external-link class="w-3 h-3" />
                    </a>
                </div>
            </div>
        </div>

        {{-- Listing détaillé des moyens de paiement --}}
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm">
            <div class="px-4 py-3 border-b border-black/5 flex items-center justify-between">
                <div>
                    <div class="text-xs font-semibold text-neutral-900">Moyens de paiement disponibles</div>
                    <div class="text-xxs text-neutral-500">
                        Activez ou désactivez les moyens de paiement disponibles sur le checkout.
                    </div>
                </div>
            </div>

            <div class="divide-y divide-neutral-100">
                {{-- Stripe natif en premier --}}
                <div class="px-4 py-3 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-neutral-900 flex items-center justify-center">
                            <x-lucide-credit-card class="w-4 h-4 text-white" />
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-neutral-900 flex items-center gap-2">
                                Stripe
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xxs font-medium bg-neutral-900 text-white uppercase">
                                    Core
                                </span>
                            </div>
                            <div class="text-xxs text-neutral-500">
                                Cartes bancaires, Wallets (Apple Pay, Google Pay…) selon votre configuration Stripe.
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 text-xs">
                        @if ($stripeEnabled ?? false)
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-medium bg-emerald-50 text-emerald-600">
                                Actif
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-medium bg-neutral-100 text-neutral-600">
                                Inactif
                            </span>
                        @endif
                        <a href="{{ route('admin.settings.payments.stripe') }}"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs bg-white hover:bg-neutral-50">
                            Configurer
                        </a>
                    </div>
                </div>

                {{-- Boucle sur les moyens de paiement venant des PaymentProvider (modules, etc.) --}}
                @forelse($paymentProviders as $provider)
                    @php
                        $config = $provider->config ?? [];
                        $description = $config['description'] ?? 'Moyen de paiement fourni par un module.';
                        $moduleName = $config['module_name'] ?? null;
                        $configRoute = $config['config_route'] ?? null;
                    @endphp

                    <div class="px-4 py-3 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-neutral-100 flex items-center justify-center">
                                {{-- icône générique ou issue du module plus tard --}}
                                <x-lucide-badge-dollar-sign class="w-4 h-4 text-neutral-600" />
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-neutral-900 flex items-center gap-2">
                                    {{ $provider->name }}
                                    <span
                                        class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xxs font-medium bg-indigo-50 text-indigo-600">
                                        Module
                                    </span>
                                </div>
                                <div class="text-xxs text-neutral-500">
                                    {{ $description }}
                                </div>
                                @if ($moduleName)
                                    <div class="text-xxs text-neutral-400 mt-0.5">
                                        Module : {{ $moduleName }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-3 text-xs">
                            @if ($provider->enabled)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-medium bg-emerald-50 text-emerald-600">
                                    Actif
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-medium bg-neutral-100 text-neutral-600">
                                    Inactif
                                </span>
                            @endif

                            <div class="flex items-center gap-2">
                                @if ($configRoute)
                                    <a href="{{ route($configRoute) }}"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs bg-white hover:bg-neutral-50">
                                        Configurer
                                    </a>
                                @endif

                                <form method="POST"
                                    action="{{ route('admin.settings.payments.toggle', $provider->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs {{ $provider->enabled ? 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' }}">
                                        {{ $provider->enabled ? 'Désactiver' : 'Activer' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-xxs text-neutral-500">
                        Aucun module de paiement n’est installé pour le moment.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
