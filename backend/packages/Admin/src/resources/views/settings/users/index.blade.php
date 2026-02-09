@extends('admin::settings.layout')

@section('settings-content')

    <div class="flex items-center justify-between mb-4">
        <div class="space-y-0.5">
            <div class="text-sm font-semibold text-gray-900">Utilisateurs</div>
            <div class="text-xs text-gray-500">
                Gérez les utilisateurs et attribuez-leur des rôles.
            </div>
        </div>
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
        @if ($users->isEmpty())
            <div class="px-4 py-6 text-center">
                <div class="text-xs text-gray-500">
                    Aucun utilisateur pour le moment.
                </div>
            </div>
        @else
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Nom</th>
                        <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Email</th>
                        <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Rôles</th>
                        <th class="px-4 py-2 text-xxxs font-semibold text-gray-500 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($users as $user)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-4 py-2 align-middle">
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium text-gray-900">
                                        {{ $user->firstname }} {{ $user->lastname }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-2 align-middle">
                                <span class="text-xs text-gray-600">
                                    {{ $user->email }}
                                </span>
                            </td>

                            <td class="px-4 py-2 align-middle">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xxxs text-gray-700">
                                            {{ $role->display_name }}
                                            <form action="{{ route('admin.settings.users.roles.remove', [$user, $role]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="hover:text-red-600">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </span>
                                    @empty
                                        <span class="text-xxxs text-gray-400">Aucun rôle</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="px-4 py-2 align-middle">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button onclick="openAssignRoleModal({{ $user->id }}, '{{ $user->firstname }} {{ $user->lastname }}')"
                                        class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                        Attribuer un rôle
                                    </button>

                                    <a href="{{ route('admin.settings.users.edit', $user) }}"
                                        class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                        Gérer les rôles
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($users->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Modal Attribution de rôle -->
    <div id="assignRoleModal" class="hidden fixed inset-0 bg-black/50 z-50 flex justify-center items-center">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md m-4">
            <div class="sticky top-0 rounded-t-2xl bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Attribuer un rôle</h3>
                <button onclick="closeAssignRoleModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="assignRoleForm" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                        Utilisateur
                    </label>
                    <div id="userName" class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2"></div>
                </div>

                <div>
                    <label for="role_id" class="block text-xs font-medium text-gray-700 mb-1.5">
                        Rôle
                    </label>
                    <select name="role_id" id="role_id" required
                        class="w-full rounded-lg border-gray-300 text-xs focus:border-gray-900 focus:ring-gray-900">
                        <option value="">Sélectionnez un rôle</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeAssignRoleModal()"
                        class="rounded-lg border border-gray-200 px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                        Attribuer
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
