@extends('admin::settings.layout')

@section('settings-content')

    <div class="flex items-center justify-between mb-4">
        <div class="space-y-0.5">
            <div class="text-sm font-semibold text-gray-900">Permissions</div>
            <div class="text-xs text-gray-500">
                Gérez les permissions disponibles pour vos rôles.
            </div>
        </div>

        <button onclick="openCreateModal()"
            class="inline-flex items-center gap-1.5 rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
            Créer une permission
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
        @if ($permissions->isEmpty())
            <div class="px-4 py-6 text-center">
                <div class="text-xs text-gray-500">
                    Aucune permission pour le moment.
                </div>
                <div class="mt-2">
                    <button onclick="openCreateModal()"
                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1.5 text-xxxs text-gray-800 hover:bg-gray-50">
                        Créer votre première permission
                    </button>
                </div>
            </div>
        @else
            <div class="p-4 space-y-6">
                @foreach ($permissions as $group => $groupPermissions)
                    <div>
                        <div class="text-xs font-semibold text-gray-900 mb-3 pb-2 border-b border-gray-100">
                            {{ $group ?: 'Sans groupe' }}
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach ($groupPermissions as $permission)
                                <div class="rounded-xl border border-gray-200 p-3 hover:bg-gray-50">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="text-xs font-medium text-gray-900">
                                                {{ $permission->display_name }}
                                            </div>
                                            <div class="text-xxxs font-mono text-gray-500 mt-0.5">
                                                {{ $permission->name }}
                                            </div>
                                            <div class="text-xxxs text-gray-400 mt-1">
                                                {{ $permission->roles_count }} rôle{{ $permission->roles_count > 1 ? 's' : '' }}
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <button onclick="openEditModal({{ $permission->id }})"
                                                class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-white">
                                                Modifier
                                            </button>
                                            <button type="button"
                                                class="rounded-full border border-red-100 px-2 py-0.5 text-xxxs text-red-500 hover:bg-red-50"
                                                @click="$dispatch('open-modal', { name: 'delete-permission-{{ $permission->id }}' })">
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Modals de confirmation de suppression --}}
    @foreach($permissions as $group => $groupPermissions)
        @foreach($groupPermissions as $permission)
            <x-admin::modal name="delete-permission-{{ $permission->id }}"
                :title="'Supprimer la permission « ' . $permission->display_name . ' » ?'"
                description="Cette action est définitive et ne peut pas être annulée."
                size="max-w-md">
                <p class="text-xs text-gray-600">
                    Voulez-vous vraiment supprimer la permission
                    <span class="font-semibold">{{ $permission->display_name }}</span>
                    (<code class="text-xxxs bg-gray-100 px-1 py-0.5 rounded">{{ $permission->name }}</code>) ?
                    <br><br>
                    Cette permission est actuellement utilisée par <span class="font-semibold">{{ $permission->roles_count }} rôle{{ $permission->roles_count > 1 ? 's' : '' }}</span>.
                </p>

                <div class="flex justify-end gap-2 pt-3">
                    <button type="button"
                        class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                        @click="open = false">
                        Annuler
                    </button>

                    <form action="{{ route('admin.settings.permissions.destroy', $permission) }}" method="POST">
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
    @endforeach

    <!-- Modal Création -->
    <div id="createModal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                <div class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-gray-900">Créer une permission</h3>
                    <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.settings.permissions.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    @include('admin::settings.permissions._form', ['permission' => null])

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeCreateModal()"
                            class="rounded-lg border border-gray-200 px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                            Annuler
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                            Créer la permission
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Édition -->
    <div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                <div class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-sm font-semibold text-gray-900">Modifier la permission</h3>
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
