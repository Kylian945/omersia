<div class="space-y-4">
    <div>
        <label for="name" class="block text-xxxs font-semibold text-gray-700 mb-1">
            Nom technique <span class="text-red-500">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name', $permission->name ?? '') }}" required
            class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:border-gray-400 focus:outline-none"
            placeholder="ex: manage-users, edit-products">
        @error('name')
            <div class="mt-1 text-xxxs text-red-500">{{ $message }}</div>
        @enderror
        <div class="mt-1 text-xxxs text-gray-500">
            Nom unique, en minuscules, avec tirets (utilisé en interne).
        </div>
    </div>

    <div>
        <label for="display_name" class="block text-xxxs font-semibold text-gray-700 mb-1">
            Nom d'affichage <span class="text-red-500">*</span>
        </label>
        <input type="text" id="display_name" name="display_name"
            value="{{ old('display_name', $permission->display_name ?? '') }}" required
            class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:border-gray-400 focus:outline-none"
            placeholder="ex: Gérer les utilisateurs">
        @error('display_name')
            <div class="mt-1 text-xxxs text-red-500">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="group" class="block text-xxxs font-semibold text-gray-700 mb-1">
            Groupe
        </label>
        <input type="text" id="group" name="group" value="{{ old('group', $permission->group ?? '') }}"
            class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:border-gray-400 focus:outline-none"
            placeholder="ex: Utilisateurs, Produits, Commandes">
        @error('group')
            <div class="mt-1 text-xxxs text-red-500">{{ $message }}</div>
        @enderror
        <div class="mt-1 text-xxxs text-gray-500">
            Permet de regrouper les permissions par catégorie.
        </div>
    </div>
</div>
