@extends('admin::settings.layout')

@section('title', 'Configuration Stripe')
@section('page-title', 'Configuration Stripe')

@section('settings-content')
    <div class="space-y-6">

        {{-- Header de section --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <x-lucide-credit-card class="w-5 h-5" />
                    <h2 class="text-sm font-semibold text-neutral-900">
                        Configuration Stripe
                    </h2>
                </div>
                <p class="mt-1 text-xs text-neutral-500 max-w-xl">
                    Gérez l’activation de Stripe, le mode test/production, vos clés API et le secret de webhook.
                </p>
            </div>

            <div class="text-xxs text-neutral-500 text-right">
                Mode actuel :
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xxs font-semibold
                    {{ $mode === 'live' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                    {{ $mode === 'live' ? 'Production' : 'Test' }}
                </span>
            </div>
        </div>

        {{-- Flash messages --}}
        @if ($errors->any())
            <div class="rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-xxs text-rose-800 space-y-1">
                <div class="font-semibold">Une erreur est survenue :</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Formulaire --}}
        <form method="POST" action="{{ route('admin.settings.payments.stripe.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Bloc activation + mode --}}
            <div class="rounded-2xl border border-black/5 bg-neutral-50/60 p-4 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="space-y-1">
                        <div class="text-xs font-semibold text-neutral-900">Activation</div>
                        <p class="text-xxs text-neutral-500 max-w-md">
                            Stripe doit être actif pour que les paiements par carte bancaire soient proposés au checkout.
                        </p>
                    </div>

                    <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                        {{-- Input réel --}}
                        <input type="checkbox" name="enabled" value="1" class="sr-only peer"
                            @checked(old('enabled', $enabled)) />

                        {{-- Track du toggle (Shopify-like) --}}
                        <div
                            class="w-10 h-5 rounded-full border border-neutral-300 bg-neutral-200
                   flex items-center px-0.5 justify-start
                   transition-all duration-150
                   peer-checked:bg-emerald-500 peer-checked:border-emerald-500 peer-checked:justify-end">
                            {{-- Thumb --}}
                            <div
                                class="w-4 h-4 rounded-full bg-white shadow-sm
                       transition-all duration-150">
                            </div>
                        </div>

                        {{-- Label texte --}}
                        <span class="text-xs text-neutral-800">
                            Activer Stripe
                        </span>
                    </label>
                </div>


                <div class="border-t border-dashed border-neutral-200 pt-3 mt-2 space-y-3">
                    <div>
                        <div class="text-xs font-semibold text-neutral-900 mb-2">Mode</div>
                        <div class="flex flex-col sm:flex-row gap-3 text-xs">
                            <label
                                class="flex-1 inline-flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer
                                {{ old('mode', $mode) === 'test'
                                    ? 'border-amber-300 bg-amber-50/70'
                                    : 'border-neutral-200 bg-white hover:bg-neutral-50' }}">
                                <input type="radio" name="mode" value="test" @checked(old('mode', $mode) === 'test')
                                    class="text-neutral-900 focus:ring-neutral-900">
                                <div class="flex flex-col gap-0.5">
                                    <span class="font-semibold text-xxs uppercase tracking-wide">Mode test</span>
                                    <span class="text-xxs text-neutral-500">
                                        Utilise les clés de test. Idéal pour les tests sans paiements réels.
                                    </span>
                                </div>
                            </label>

                            <label
                                class="flex-1 inline-flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer
                                {{ old('mode', $mode) === 'live'
                                    ? 'border-emerald-300 bg-emerald-50/70'
                                    : 'border-neutral-200 bg-white hover:bg-neutral-50' }}">
                                <input type="radio" name="mode" value="live" @checked(old('mode', $mode) === 'live')
                                    class="text-neutral-900 focus:ring-neutral-900">
                                <div class="flex flex-col gap-0.5">
                                    <span class="font-semibold text-xxs uppercase tracking-wide">Mode production</span>
                                    <span class="text-xxs text-neutral-500">
                                        Utilise les clés live. Les paiements sont réellement débités.
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Devise --}}
                    <div class="max-w-xs">
                        <label class="text-xxs text-neutral-500">Devise principale Stripe (ex : EUR, USD)</label>
                        <input type="text" name="currency" value="{{ old('currency', $currency) }}"
                            class="mt-1 w-full rounded-md border border-neutral-200 px-3 py-2 text-xs uppercase"
                            placeholder="EUR" />
                    </div>
                </div>
            </div>

            {{-- Clés API --}}
            <div class="rounded-2xl border border-black/5 bg-white p-4 space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="text-xs font-semibold text-neutral-900">Clés API</div>
                        <p class="text-xxs text-neutral-500">
                            Clés à récupérer dans votre Dashboard Stripe, section "Developers &gt; API keys".
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- Publishable key --}}
                    <div>
                        <label class="text-xxs text-neutral-500">Publishable key</label>
                        <div class="mt-1 relative">
                            <input id="publishable_key" type="password" name="publishable_key"
                                value="{{ old('publishable_key', $publishable_key) }}"
                                class="w-full rounded-md border border-neutral-200 px-3 py-2 pr-8 text-xs"
                                placeholder="pk_test_..." autocomplete="off" />
                            <button type="button"
                                class="absolute inset-y-0 right-2 flex items-center text-neutral-400 hover:text-neutral-700"
                                data-toggle-password="publishable_key" aria-label="Afficher / masquer la clé publique">
                                <x-lucide-eye class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {{-- Secret key --}}
                    <div>
                        <label class="text-xxs text-neutral-500">Secret key</label>
                        <div class="mt-1 relative">
                            <input id="secret_key" type="password" name="secret_key"
                                value="{{ old('secret_key', $secret_key) }}"
                                class="w-full rounded-md border border-neutral-200 px-3 py-2 pr-8 text-xs"
                                placeholder="sk_test_..." autocomplete="off" />
                            <button type="button"
                                class="absolute inset-y-0 right-2 flex items-center text-neutral-400 hover:text-neutral-700"
                                data-toggle-password="secret_key" aria-label="Afficher / masquer la clé secrète">
                                <x-lucide-eye class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Webhook --}}
            <div class="rounded-2xl border border-black/5 bg-white p-4 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="text-xs font-semibold text-neutral-900">Webhook Stripe</div>
                        <p class="text-xxs text-neutral-500 max-w-xl">
                            Secret du webhook utilisé pour vérifier l’authenticité des notifications envoyées par Stripe.
                            À récupérer dans Stripe &gt; Developers &gt; Webhooks.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <label class="text-xxs text-neutral-500">Webhook secret</label>
                        <div class="mt-1 relative">
                            <input id="webhook_secret" type="password" name="webhook_secret"
                                value="{{ old('webhook_secret', $webhook_secret) }}"
                                class="w-full rounded-md border border-neutral-200 px-3 py-2 pr-8 text-xs"
                                placeholder="whsec_..." autocomplete="off" />
                            <button type="button"
                                class="absolute inset-y-0 right-2 flex items-center text-neutral-400 hover:text-neutral-700"
                                data-toggle-password="webhook_secret" aria-label="Afficher / masquer le secret webhook">
                                <x-lucide-eye class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                @if (config('services.stripe.webhook_url') ?? false)
                    <p class="mt-2 text-xxs text-neutral-500">
                        URL du webhook à renseigner dans Stripe :
                        <span class="font-mono text-xxs bg-neutral-100 px-1.5 py-0.5 rounded">
                            {{ config('services.stripe.webhook_url') }}
                        </span>
                    </p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('admin.settings.payments.index') }}"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border text-xs bg-white hover:bg-neutral-50">
                    Annuler
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg text-xs font-semibold
                               bg-neutral-900 text-white hover:bg-black">
                    Enregistrer
                </button>
            </div>
        </form>

    </div>
@endsection
