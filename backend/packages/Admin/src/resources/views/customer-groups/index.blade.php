@extends('admin::layout')

@section('title', 'Groupes clients')
@section('page-title', 'Groupes clients')

@section('content')
    <div x-data="{}" class="space-y-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                    <x-lucide-users class="w-3 h-3" />
                    Groupes clients
                </div>
                <div class="text-xs text-gray-500">
                    Organisez vos clients par segments pour vos campagnes et remises.
                </div>
            </div>
            <a href="{{ route('customer-groups.create') }}"
               class="bg-black rounded-lg px-4 py-1.5 text-xs text-white hover:bg-neutral-800 shadow-sm border border-black font-semibold">
                Nouveau groupe
            </a>
        </div>

        <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
            <table class="min-w-full text-xs">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">Nom</th>
                        <th class="px-3 py-2 text-left font-medium">Nb de clients</th>
                        <th class="px-3 py-2 text-left font-medium">Code</th>
                        <th class="px-3 py-2 text-left font-medium">Par défaut</th>
                        <th class="px-3 py-2 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                        <tr class="border-t border-neutral-100">
                            <td class="px-3 py-2">{{ $group->name }}</td>
                            <td class="px-3 py-2">{{ count($group->customers) }} {{count($group->customers) > 1 ? "clients" : "client"}}</td>
                            <td class="px-3 py-2 text-neutral-500">{{ $group->code ?? '—' }}</td>
                            <td class="px-3 py-2">
                                @if ($group->is_default)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-xxs">
                                        Oui
                                    </span>
                                @else
                                    <span class="text-xxs text-neutral-400">Non</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('customer-groups.edit', $group) }}"
                                        class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                        Modifier
                                    </a>
                                    <button type="button"
                                            class="rounded-full border border-red-100 px-2 py-0.5 text-xxxs text-red-500 hover:bg-red-50"
                                            @click="$dispatch('open-modal', { name: 'delete-customer-group-{{ $group->id }}' })">
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-xs text-neutral-400">
                                Aucun groupe client pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $groups->links() }}

        {{-- Modals de confirmation de suppression --}}
        @foreach($groups as $group)
            <x-admin::modal name="delete-customer-group-{{ $group->id }}"
                :title="'Supprimer le groupe « ' . $group->name . ' » ?'"
                description="Cette action est définitive et ne peut pas être annulée."
                size="max-w-md">
                <p class="text-xs text-gray-600">
                    Voulez-vous vraiment supprimer le groupe
                    <span class="font-semibold">{{ $group->name }}</span> ?
                </p>

                <div class="flex justify-end gap-2 pt-3">
                    <button type="button"
                        class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                        @click="open = false">
                        Annuler
                    </button>

                    <form action="{{ route('customer-groups.destroy', $group) }}" method="POST">
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
