@extends('admin::settings.layout')

@section('settings-content')

        <div class="flex items-center justify-between mb-4">
            <div class="space-y-0.5">
                <div class="text-sm font-semibold text-gray-900">Clés API</div>
                <div class="text-xs text-gray-500">
                    Gérez les clés utilisées par votre frontend, vos intégrations ou partenaires.
                </div>
            </div>

            <a href="{{ route('admin.settings.api-keys.create') }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                Ajouter une clé API
            </a>
        </div>

        @if (session('success'))
            <div class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xxxs text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Bloc affichant la dernière clé générée en clair (une seule fois) --}}
        @if (session('new_api_key'))
            <div class="mb-4 rounded-2xl border border-yellow-200 bg-yellow-50 px-3 py-3 flex flex-col gap-2">
                <div class="text-xxxs font-semibold text-gray-800">
                    Voici votre nouvelle clé API (visible une seule fois) :
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ session('new_api_key') }}"
                        class="flex-1 rounded-lg border border-yellow-200 bg-white px-3 py-1.5 text-xxxs font-mono text-gray-800"
                        id="new-api-key-input">
                    <button type="button" onclick="copyNewApiKey()"
                        class="rounded-full bg-[#111827] px-3 py-1.5 text-xxxs font-medium text-white hover:bg-black">
                        Copier
                    </button>
                </div>
                <div class="text-xxxs text-gray-500">
                    Conservez-la dans un gestionnaire de mots de passe. Vous ne pourrez plus la retrouver en clair.
                </div>
            </div>
        @endif

        <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
            @if ($apiKeys->isEmpty())
                <div class="px-4 py-6 text-center">
                    <div class="text-xs text-gray-500">
                        Aucune clé API pour le moment.
                    </div>
                    <div class="mt-2">
                        <a href="{{ route('admin.settings.api-keys.create') }}"
                            class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1.5 text-xxxs text-gray-800 hover:bg-gray-50">
                            Créer votre première clé API
                        </a>
                    </div>
                </div>
            @else
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Nom</th>
                            <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Clé (hash)</th>
                            <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Statut</th>
                            <th class="px-4 py-2 text-xxxs font-semibold text-gray-500">Créée le</th>
                            <th class="px-4 py-2 text-xxxs font-semibold text-gray-500 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($apiKeys as $key)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-4 py-2 align-middle">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-medium text-gray-900">
                                            {{ $key->name }}
                                        </span>
                                        @if (!empty($key->description))
                                            <span class="text-xxxs text-gray-500 line-clamp-1">
                                                {{ $key->description }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-2 align-middle">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xxxs font-mono text-gray-500">
                                            {{ Str::limit($key->key, 28) }}
                                        </span>
                                        <button type="button"
                                            class="text-xxxs px-1.5 py-0.5 border border-gray-200 rounded-full text-gray-600 hover:bg-gray-50"
                                            onclick="copyToClipboard('{{ $key->key }}')">
                                            Copier
                                        </button>
                                    </div>
                                </td>

                                <td class="px-4 py-2 align-middle">
                                    @if ($key->active)
                                        <span
                                            class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xxxs text-emerald-700 border border-emerald-100">
                                            ● Active
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xxxs text-red-600 border border-red-100">
                                            ● Désactivée
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 align-middle">
                                    <span class="text-xxxs text-gray-500">
                                        {{ $key->created_at?->format('d/m/Y H:i') }}
                                    </span>
                                </td>

                                <td class="px-4 py-2 align-middle">
                                    <div class="flex items-center justify-end gap-1.5">

                                        {{-- Toggle actif / inactif --}}
                                        <form action="{{ route('admin.settings.api-keys.toggle', $key) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                                {{ $key->active ? 'Désactiver' : 'Activer' }}
                                            </button>
                                        </form>

                                        {{-- Regénérer --}}
                                        <button type="button"
                                            class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50"
                                            @click="$dispatch('open-modal', { name: 'regenerate-api-key-{{ $key->id }}' })">
                                            Regénérer
                                        </button>

                                        {{-- Supprimer --}}
                                        <button type="button"
                                            class="rounded-full border border-red-100 px-2 py-0.5 text-xxxs text-red-500 hover:bg-red-50"
                                            @click="$dispatch('open-modal', { name: 'delete-api-key-{{ $key->id }}' })">
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

    {{-- Modals de confirmation de régénération et suppression --}}
    @foreach($apiKeys as $key)
        <x-admin::modal name="regenerate-api-key-{{ $key->id }}"
            :title="'Regénérer la clé « ' . $key->name . ' » ?'"
            description="L'ancienne clé ne sera plus valide après cette action."
            size="max-w-md">
            <p class="text-xs text-gray-600">
                Voulez-vous vraiment regénérer la clé API
                <span class="font-semibold">{{ $key->name }}</span> ?
            </p>

            <div class="flex justify-end gap-2 pt-3">
                <button type="button"
                    class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                    @click="open = false">
                    Annuler
                </button>

                <form action="{{ route('admin.settings.api-keys.regenerate', $key) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-xs font-medium text-white hover:bg-neutral-900">
                        Confirmer la régénération
                    </button>
                </form>
            </div>
        </x-admin::modal>

        <x-admin::modal name="delete-api-key-{{ $key->id }}"
            :title="'Supprimer la clé « ' . $key->name . ' » ?'"
            description="Cette action est définitive et ne peut pas être annulée."
            size="max-w-md">
            <p class="text-xs text-gray-600">
                Voulez-vous vraiment supprimer la clé API
                <span class="font-semibold">{{ $key->name }}</span> ?
            </p>

            <div class="flex justify-end gap-2 pt-3">
                <button type="button"
                    class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                    @click="open = false">
                    Annuler
                </button>

                <form action="{{ route('admin.settings.api-keys.destroy', $key) }}" method="POST">
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

        {{-- Zone des toasts --}}
        <div id="toast-container" class="fixed bottom-4 left-1/2 -translate-x-1/2 flex flex-col gap-2 z-50"></div>

        @push('styles')
            @vite('packages/Admin/src/resources/css/settings/api-keys.css')
        @endpush
@endsection
