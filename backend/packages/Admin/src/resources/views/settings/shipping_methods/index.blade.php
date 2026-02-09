@extends('admin::settings.layout')

@section('title', 'Méthodes de livraison')
@section('page-title', 'Méthodes de livraison')

@section('settings-content')
<div x-data="{}" class="space-y-4">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-lucide-truck class="w-4 h-4" />
            <h1 class="text-base font-semibold">Méthodes de livraison</h1>
        </div>

        <a href="{{ route('admin.settings.shipping_methods.create') }}"
           class="inline-flex items-center rounded-lg bg-neutral-900 text-white text-xs px-4 py-1.5 hover:bg-black">
            <x-lucide-plus class="w-4 h-4 mr-1" />
            Nouvelle méthode
        </a>
    </div>

    <div class="rounded-2xl bg-white border border-black/5 shadow-sm">
        @if($methods->isEmpty())
            <p class="text-xs text-neutral-500 p-4">
                Aucune méthode de livraison pour le moment.
            </p>
        @else
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-neutral-500 border-b border-neutral-100">
                        <th class="py-2 px-3">Nom</th>
                        <th class="py-2 px-3">Code</th>
                        <th class="py-2 px-3 text-right">Prix</th>
                        <th class="py-2 px-3">Délai</th>
                        <th class="py-2 px-3 text-center">Statut</th>
                        <th class="py-2 px-3 text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($methods as $method)
                        <tr class="border-b border-neutral-50 rounded-2xl">
                            <td class="py-2 px-3">
                                <div class="font-medium text-neutral-900">
                                    {{ $method->name }}
                                </div>
                            </td>
                            <td class="py-2 px-3 text-neutral-500">
                                <code class="text-xxs bg-neutral-50 px-1.5 py-0.5 rounded-md">
                                    {{ $method->code }}
                                </code>
                            </td>
                            <td class="py-2 px-3 text-right">
                                {{ number_format($method->price, 2, ',', ' ') }} €
                            </td>
                            <td class="py-2 px-3 text-neutral-500">
                                {{ $method->delivery_time ?: '—' }}
                            </td>
                            <td class="py-2 px-3 text-center">
                                @if($method->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xxs font-medium gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 text-xxs font-medium">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    
                                    <a href="{{ route('admin.settings.shipping_methods.edit', $method) }}"
                                       class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                        Modifier
                                    </a>

                                    <button type="button"
                                            class="rounded-full border border-red-200 px-2 py-0.5 text-xxxs text-red-700 hover:bg-red-50"
                                            @click="$dispatch('open-modal', { name: 'delete-shipping-{{ $method->id }}' })">
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Modals de confirmation de suppression --}}
    @foreach($methods as $method)
        <x-admin::modal name="delete-shipping-{{ $method->id }}"
            :title="'Supprimer la méthode « ' . $method->name . ' » ?'"
            description="Cette action est définitive et ne peut pas être annulée."
            size="max-w-md">
            <p class="text-xs text-gray-600">
                Voulez-vous vraiment supprimer la méthode de livraison
                <span class="font-semibold">{{ $method->name }}</span>
                (<code class="text-xxxs bg-gray-100 px-1 py-0.5 rounded">{{ $method->code }}</code>) ?
            </p>

            <div class="flex justify-end gap-2 pt-3">
                <button type="button"
                    class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                    @click="open = false">
                    Annuler
                </button>

                <form method="POST" action="{{ route('admin.settings.shipping_methods.destroy', $method) }}">
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
</div>
@endsection
