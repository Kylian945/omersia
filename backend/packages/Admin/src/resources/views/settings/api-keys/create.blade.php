@extends('admin::settings.layout')

@section('title', 'Nouvelle clé API')
@section('page-title', 'Créer une clé API')


@section('settings-content')

<form action="{{ route('admin.settings.api-keys.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    @csrf

    {{-- Colonne principale --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Informations de la clé --}}
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div>
                <div class="text-xs font-semibold text-gray-800">Détails de la clé</div>
                <div class="text-xxxs text-gray-500">
                    Donne un nom à ta clé API et configure ses permissions.  
                    Exemple : <span class="italic">Frontend Next.js</span>, <span class="italic">Intégration externe</span>…
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Nom de la clé</label>
                <input type="text" name="name"
                       placeholder="ex: Frontend Next.js"
                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs"
                       required>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-700">Description</label>
                <textarea name="description"
                          placeholder="Utilisée par le front Next.js pour authentifier les appels API internes."
                          class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs h-20 resize-none"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="active" value="1" checked
                       class="h-3 w-3 rounded border-gray-300">
                <span class="text-xs text-gray-700">Clé active dès la création</span>
            </div>
        </div>

        {{-- Permissions éventuelles --}}
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div class="text-xs font-semibold text-gray-800">Permissions</div>
            <div class="text-xxxs text-gray-500">Optionnel — utile si tu veux gérer différents niveaux d’accès.</div>

            <div class="grid grid-cols-2 gap-2 pt-2">
                <label class="flex items-center gap-2 text-xs text-gray-700">
                    <input type="checkbox" name="scopes[]" value="read_themes" class="h-3 w-3 border-gray-300 rounded">
                    Lecture des thèmes
                </label>

                <label class="flex items-center gap-2 text-xs text-gray-700">
                    <input type="checkbox" name="scopes[]" value="write_themes" class="h-3 w-3 border-gray-300 rounded">
                    Modification des thèmes
                </label>

                <label class="flex items-center gap-2 text-xs text-gray-700">
                    <input type="checkbox" name="scopes[]" value="read_orders" class="h-3 w-3 border-gray-300 rounded">
                    Lecture des commandes
                </label>

                <label class="flex items-center gap-2 text-xs text-gray-700">
                    <input type="checkbox" name="scopes[]" value="write_orders" class="h-3 w-3 border-gray-300 rounded">
                    Écriture des commandes
                </label>
            </div>
        </div>
    </div>

    {{-- Colonne droite --}}
    <div class="space-y-4">

        {{-- Résumé --}}
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
            <div class="text-xs font-semibold text-gray-800">Résumé</div>
            <div class="text-xxxs text-gray-500">Un aperçu rapide avant création.</div>

            <ul class="text-xs text-gray-700 space-y-1 pt-2">
                <li>🔑 Génération automatique d’une clé sécurisée (64 caractères)</li>
                <li>⚙️ Stockage hashé (SHA-256)</li>
                <li>🚀 Clé utilisable immédiatement</li>
            </ul>
        </div>

        {{-- Actions --}}
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 flex flex-col gap-2">
            <button
                class="w-full rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                Créer la clé API
            </button>
            <a href="{{ route('admin.settings.api-keys.index') }}"
               class="w-full text-center rounded-lg border border-gray-200 px-4 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Annuler
            </a>
        </div>
    </div>
</form>
@endsection
