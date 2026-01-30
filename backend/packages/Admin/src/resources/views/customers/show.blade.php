@extends('admin::layout')

@section('title', 'Client #' . $customer->id)
@section('page-title', 'Client #' . $customer->id)

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                <x-lucide-user class="w-3 h-3" />
                {{ $customer->fullname }}
            </div>
            <div class="text-xs text-gray-500">
                {{ $customer->email }}
            </div>
        </div>

        <a href="{{ route('admin.customers') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
            ← Retour à la liste
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {{-- Infos client --}}
        <div class="lg:col-span-2 space-y-3">
            <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div
                        class="h-10 w-10 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-semibold">
                        {{ strtoupper(mb_substr($customer->fullname, 0, 1)) }}
                    </div>
                    <div class="leading-tight">
                        <div class="text-sm font-semibold text-slate-900">
                            {{ $customer->fullname }}
                        </div>
                        <div class="text-xs text-slate-500">
                            #{{ $customer->id }}
                        </div>
                    </div>
                </div>

                <dl class="text-xs text-slate-600 space-y-1">
                    <div class="flex justify-between">
                        <dt>Créé le</dt>
                        <dd>{{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Email</dt>
                        <dd>{{ $customer->email }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Vérifié</dt>
                        <dd>
                            @if ($customer->email_verified_at)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 px-2 py-0.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Vérifié
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-slate-50 text-slate-600 px-2 py-0.5">
                                    Non vérifié
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Placeholder commandes / stats --}}
            <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
                <div class="text-xs font-semibold text-slate-800 mb-2">
                    Commandes
                </div>
                @if (count($orders) > 0)
                    <div class="text-xs text-slate-500">
                        Intégration à faire avec ta table <code>orders</code>.
                    </div>
                @else
                    <div class="text-xs text-slate-500">
                        Pas de commandes pour le moment.
                    </div>
                @endif
            </div>
        </div>

        {{-- Adresses --}}
        <div class="lg:col-span-2 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-xs font-semibold text-slate-800">
                    Adresses
                </div>
                <a href="{{ route('admin.customers.addresses.create', $customer) }}"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-800 hover:bg-slate-50">
                    <x-lucide-plus class="w-3 h-3" />
                    Ajouter une adresse
                </a>
            </div>

            @if ($addresses->isEmpty())
                <div
                    class="border border-dashed border-slate-200 rounded-xl p-4 text-center text-xs text-slate-500 bg-slate-50/40">
                    Aucune adresse pour ce client.
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($addresses as $address)
                        <div class="rounded-xl border border-slate-200 bg-white p-3 text-xs flex justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-900">
                                        {{ $address->label }}
                                    </span>

                                    @if ($address->is_default_billing)
                                        <span
                                            class="inline-flex items-center rounded-full bg-amber-50 text-amber-700 px-2 py-0.5">
                                            Facturation par défaut
                                        </span>
                                    @endif
                                    @if ($address->is_default_shipping)
                                        <span
                                            class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 px-2 py-0.5">
                                            Livraison par défaut
                                        </span>
                                    @endif
                                </div>

                                <div class="text-slate-700">
                                    {{ $address->first_name }} {{ $address->last_name }}
                                    @if ($address->company)
                                        · {{ $address->company }}
                                    @endif
                                </div>
                                <div class="text-slate-600">
                                    {{ $address->line1 }}@if ($address->line2)
                                        , {{ $address->line2 }}
                                    @endif
                                    <br>
                                    {{ $address->postcode }} {{ $address->city }}@if ($address->state)
                                        , {{ $address->state }}
                                    @endif
                                    <br>
                                    {{ $address->country }}
                                </div>
                                @if ($address->phone)
                                    <div class="text-slate-500">
                                        Tél : {{ $address->phone }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-col items-end gap-1">
                                <div class="flex gap-1">
                                    <a href="{{ route('admin.customers.addresses.edit', [$customer, $address]) }}"
                                        class="rounded-lg border border-slate-200 px-2 py-1 text-xxs text-slate-700 hover:bg-slate-50">
                                        Modifier
                                    </a>

                                    <form method="POST"
                                        action="{{ route('admin.customers.addresses.destroy', [$customer, $address]) }}"
                                        onsubmit="return confirm('Supprimer cette adresse ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded-lg border border-red-200 px-2 py-1 text-xxs text-red-700 hover:bg-red-50">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>

                                <div class="flex gap-1 mt-1">
                                    @if (!$address->is_default_shipping)
                                        <form method="POST"
                                            action="{{ route('admin.customers.addresses.default-shipping', [$customer, $address]) }}">
                                            @csrf
                                            <button type="submit"
                                                class="rounded-full border border-slate-200 px-2 py-0.5 text-xxs text-slate-700 hover:bg-slate-50">
                                                Définir livraison par défaut
                                            </button>
                                        </form>
                                    @endif

                                    @if (!$address->is_default_billing)
                                        <form method="POST"
                                            action="{{ route('admin.customers.addresses.default-billing', [$customer, $address]) }}">
                                            @csrf
                                            <button type="submit"
                                                class="rounded-full border border-slate-200 px-2 py-0.5 text-xxs text-slate-700 hover:bg-slate-50">
                                                Définir facturation par défaut
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
