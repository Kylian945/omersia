@extends('admin::settings.layout')

@section('settings-content')

    <div class="flex items-center justify-between mb-4">
        <div class="space-y-0.5">
            <div class="text-sm font-semibold text-gray-900">Rôles</div>
            <div class="text-xs text-gray-500">
                Gérez les rôles et leurs permissions associées.
            </div>
        </div>

        <button onclick="openCreateModal()"
            class="inline-flex items-center gap-1.5 rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
            Créer un rôle
        </button>
    </div>

    @if (session('success'))
        <div class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xxxs text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-3 rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xxxs text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
        @if ($roles->isEmpty())
            <div class="px-4 py-6 text-center">
                <div class="text-xs text-gray-500">
                    Aucun rôle pour le moment.
                </div>
                <div class="mt-2">
                    <a href="{{ route('admin.settings.roles.create') }}"
                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1.5 text-xxxs text-gray-800 hover:bg-gray-50">
                        Créer votre premier rôle
                    </a>
                </div>
            </div>
        @else
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Nom</th>
                        <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Nom d'affichage</th>
                        <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Utilisateurs</th>
                        <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Permissions</th>
                        <th class="px-4 py-2 text-xxxs font-semibold text-gray-500 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($roles as $role)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-4 py-2 align-middle">
                                <span class="text-xs font-mono text-gray-900">
                                    {{ $role->name }}
                                </span>
                            </td>

                            <td class="px-4 py-2 align-middle">
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium text-gray-900">
                                        {{ $role->display_name }}
                                    </span>
                                    @if (!empty($role->description))
                                        <span class="text-xxxs text-gray-500 line-clamp-1">
                                            {{ $role->description }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-2 align-middle">
                                <span class="text-xxxs text-gray-500">
                                    {{ $role->users_count }} utilisateur{{ $role->users_count > 1 ? 's' : '' }}
                                </span>
                            </td>

                            <td class="px-4 py-2 align-middle">
                                <span class="text-xxxs text-gray-500">
                                    {{ $role->permissions_count }} permission{{ $role->permissions_count > 1 ? 's' : '' }}
                                </span>
                            </td>

                            <td class="px-4 py-2 align-middle">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button onclick="openEditModal({{ $role->id }})"
                                        class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                        Modifier
                                    </button>

                                    <button type="button"
                                        class="rounded-full border border-red-100 px-2 py-0.5 text-xxxs text-red-500 hover:bg-red-50"
                                        @click="$dispatch('open-modal', { name: 'delete-role-{{ $role->id }}' })">
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
    @foreach($roles as $role)
        <x-admin::modal name="delete-role-{{ $role->id }}"
            :title="'Supprimer le rôle « ' . $role->display_name . ' » ?'"
            description="Cette action est définitive et ne peut pas être annulée."
            size="max-w-md">
            <p class="text-xs text-gray-600">
                Voulez-vous vraiment supprimer le rôle
                <span class="font-semibold">{{ $role->display_name }}</span>
                (<code class="text-xxxs bg-gray-100 px-1 py-0.5 rounded">{{ $role->name }}</code>) ?
                <br><br>
                Ce rôle est actuellement attribué à <span class="font-semibold">{{ $role->users_count }} utilisateur{{ $role->users_count > 1 ? 's' : '' }}</span>.
            </p>

            <div class="flex justify-end gap-2 pt-3">
                <button type="button"
                    class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                    @click="open = false">
                    Annuler
                </button>

                <form action="{{ route('admin.settings.roles.destroy', $role) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-xs font-medium text-white hover:bg-neutral-900">
                        Confirmer la suppression
                    </button>
                </form>
            </div>
        </x-admin::modal>
    @endforeach

    <div class="space-y-0">
        <!-- Modal Création -->
        <div id="createModal" class="hidden fixed inset-0 bg-black/50 z-50 flex justify-center items-center">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto m-4">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">Créer un rôle</h3>
                    <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.settings.roles.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    @include('admin::settings.roles._form', ['permissions' => $permissions ?? collect(), 'role' => null])

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeCreateModal()"
                            class="rounded-lg border border-gray-200 px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                            Annuler
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                            Créer le rôle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Édition -->
        <div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex justify-center items-center">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto m-4">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">Modifier le rôle</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div id="editModalContent" class="p-6">
                    <!-- Contenu chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>
@endsection
