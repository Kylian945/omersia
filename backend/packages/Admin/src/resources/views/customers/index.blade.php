@extends('admin::layout')

@section('title', 'Clients')
@section('page-title', 'Clients')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                <x-lucide-user class="w-3 h-3" />
                Clients
            </div>
            <div class="text-xs text-gray-500">
                Gérez vos clients, leurs adresses et leurs commandes.
            </div>
        </div>
        <button
            class="bg-black rounded-lg px-4 py-1.5 text-xs text-white hover:bg-neutral-800 shadow-sm border border-black font-semibold">
            Ajouter un client
        </button>
    </div>

    <div class="px-4 py-2 rounded-xl bg-white w-full mb-4 border border-gray-200 shadow-sm">
        @if ($customers->total() > 0)
            <span class="text-xs text-black font-semibold">
                {{ $customers->total() }} <span class="text-gray-600">client{{ $customers->total() > 1 ? 's' : '' }}</span>
            </span>
        @endif
    </div>

    @if ($customers->count() === 0)
        <div class="border border-dashed border-slate-200 rounded-xl p-6 text-center text-xs text-slate-500 bg-slate-50/40">
            Aucun client pour le moment. Les nouveaux comptes apparaîtront automatiquement ici.
        </div>
    @else
        <div x-data="{ q: '' }" class="overflow-x-auto border border-slate-200 rounded-xl bg-white">
            <div class="flex items-center gap-3 p-1.5">
                <div class="relative flex-1">
                    <input x-model="q" type="text" placeholder="Rechercher un client…"
                        class="w-full rounded-xl border-0 bg-white px-10 py-2 text-sm placeholder:text-neutral-400 focus:border-neutral-400 focus:outline-none">
                    <x-lucide-search class="absolute left-3 top-2.5 h-4 w-4 text-neutral-400" />
                </div>
            </div>
            <table class="min-w-full text-xs">
                <thead class="bg-slate-50 border-b border-t border-slate-200">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-slate-600">Client</th>
                        <th class="px-3 py-2 text-left font-medium text-slate-600">Email</th>
                        <th class="px-3 py-2 text-left font-medium text-slate-600">Créé le</th>
                        <th class="px-3 py-2 text-right font-medium text-slate-600">Commandes</th>
                        <th class="px-3 py-2 text-right font-medium text-slate-600">Total dépensé</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr x-show="
                            q === '' ||
                            '{{ Str::of($customer->fullname)->lower() }}'.includes(q.toLowerCase()) ||
                            '{{ $customer->email }}'.toLowerCase().includes(q.toLowerCase())
                        " class="border-b border-slate-100 hover:bg-slate-50/60 transition">
                            <td class="px-3 py-2 align-middle">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-7 w-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xxxs font-semibold">
                                        {{ strtoupper(mb_substr($customer->fullname, 0, 1)) }}
                                    </div>
                                    <div class="flex flex-col leading-tight">
                                        <a href="{{ route('admin.customers.show', $customer) }}"
                                           class="font-medium text-slate-900 hover:underline">
                                            {{ $customer->fullname }}
                                        </a>
                                        <span class="text-xs text-slate-400">
                                            #{{ $customer->id }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2 align-middle text-slate-700">
                                {{ $customer->email }}
                            </td>
                            <td class="px-3 py-2 align-middle text-slate-500">
                                {{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-3 py-2 align-middle text-right text-slate-500">
                                {{count($customer->orders) ?? 0}}
                            </td>
                            <td class="px-3 py-2 align-middle text-right text-slate-500">
                                {{$customer->ordersTotal()}} €
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $customers->links() }}
        </div>
    @endif
@endsection
