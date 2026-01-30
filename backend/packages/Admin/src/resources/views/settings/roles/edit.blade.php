<form action="{{ route('admin.settings.roles.update', $role) }}" method="POST" class="space-y-4">
    @csrf
    @method('PUT')

    @include('admin::settings.roles._form', ['role' => $role, 'permissions' => $permissions])

    <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
        <button type="button" onclick="closeEditModal()"
            class="rounded-lg border border-gray-200 px-4 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
            Annuler
        </button>
        <button type="submit"
            class="rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
            Mettre à jour
        </button>
    </div>
</form>
