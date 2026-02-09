<div class="space-y-4">
    <div>
        <label for="name" class="block text-xxxs font-semibold text-gray-700 mb-1">
            Nom technique <span class="text-red-500">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" required
            class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:border-gray-400 focus:outline-none"
            placeholder="ex: admin, editor, manager">
        @error('name')
            <div class="mt-1 text-xxxs text-red-500">{{ $message }}</div>
        @enderror
        <div class="mt-1 text-xxxs text-gray-500">
            Nom unique, en minuscules, sans espaces (utilisé en interne).
        </div>
    </div>

    <div>
        <label for="display_name" class="block text-xxxs font-semibold text-gray-700 mb-1">
            Nom d'affichage <span class="text-red-500">*</span>
        </label>
        <input type="text" id="display_name" name="display_name" value="{{ old('display_name', $role->display_name ?? '') }}" required
            class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:border-gray-400 focus:outline-none"
            placeholder="ex: Administrateur, Éditeur">
        @error('display_name')
            <div class="mt-1 text-xxxs text-red-500">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="description" class="block text-xxxs font-semibold text-gray-700 mb-1">
            Description
        </label>
        <textarea id="description" name="description" rows="3"
            class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:border-gray-400 focus:outline-none"
            placeholder="Description du rôle...">{{ old('description', $role->description ?? '') }}</textarea>
        @error('description')
            <div class="mt-1 text-xxxs text-red-500">{{ $message }}</div>
        @enderror
    </div>

    <div class="border-t border-gray-100 pt-4">
        <div class="text-xxxs font-semibold text-gray-700 mb-3">
            Permissions
        </div>

        @if ($permissions->isEmpty())
            <div class="text-xs text-gray-500 text-center py-4">
                Aucune permission disponible.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($permissions as $group => $groupPermissions)
                    <div>
                        <div class="text-xxxs font-semibold text-gray-600 mb-2">
                            {{ $group ?: 'Sans groupe' }}
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($groupPermissions as $permission)
                                <label
                                    class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                        {{ in_array($permission->id, old('permissions', isset($role) ? $role->permissions->pluck('id')->toArray() : [])) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                    <div class="flex flex-col">
                                        <span class="text-xxxs font-medium text-gray-900">
                                            {{ $permission->display_name }}
                                        </span>
                                        <span class="text-xxxs text-gray-500">
                                            {{ $permission->name }}
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
