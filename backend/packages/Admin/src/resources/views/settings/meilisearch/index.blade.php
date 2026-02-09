@extends('admin::settings.layout')

@section('settings-content')
    <div x-data="{}" class="space-y-6">
        
        {{-- État de la configuration --}}
        <div class="rounded-2xl border border-black/5 bg-white p-6 space-y-4">
            <div class="flex items-center gap-3">
                <x-lucide-server class="w-4 h-4 text-gray-700" />
                <h3 class="text-sm font-semibold text-gray-900">Configuration Meilisearch</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 mb-1">Statut</div>
                    <div class="flex items-center gap-2">
                        @if ($meilisearchConfigured)
                            <div class="h-2 w-2 bg-emerald-500 rounded-full"></div>
                            <span class="text-xs font-semibold text-gray-900">Configuré</span>
                        @else
                            <div class="h-2 w-2 bg-red-500 rounded-full"></div>
                            <span class="text-xs font-semibold text-gray-900">Non configuré</span>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 mb-1">Host</div>
                    <div class="text-xs font-semibold text-gray-900">
                        {{ $meilisearchHost ?: 'Non défini' }}
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 mb-1">Produits actifs</div>
                    <div class="text-xs font-semibold text-gray-900">{{ number_format($totalProducts, 0, ',', ' ') }}</div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 mb-1">Driver Scout</div>
                    <div class="text-xs font-semibold text-gray-900">{{ config('scout.driver') }}</div>
                </div>
            </div>
        </div>

        {{-- Actions d'indexation --}}
        <div class="rounded-2xl border border-black/5 bg-white p-6 space-y-6">
            <div class="flex items-center gap-3">
                <x-lucide-zap class="w-4 h-4 text-gray-700" />
                <h3 class="text-sm font-semibold text-gray-900">Actions d'indexation</h3>
            </div>

            <div class="grid grid-cols-1 gap-4">
                {{-- Configurer l'index --}}
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 hover:border-blue-300 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-settings class="w-4 h-4 text-blue-700" />
                                <h4 class="text-xs font-semibold text-blue-900">Configurer l'index Meilisearch</h4>
                            </div>
                            <p class="text-xs text-blue-700">
                                Lance la commande <code
                                    class="px-1.5 py-0.5 bg-blue-100 rounded text-xxxs font-mono">products:meili-config</code>
                                pour définir les attributs filtrables, triables et recherchables. <strong>À faire lors de la première installation.</strong>
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.meilisearch.configure-index') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition">
                                Configurer
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Indexer les produits --}}
                <div class="rounded-xl border border-black/5 p-4 hover:border-black/20 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-refresh-cw class="w-4 h-4 text-gray-700" />
                                <h4 class="text-xs font-semibold text-gray-900">Indexer les produits actifs</h4>
                            </div>
                            <p class="text-xs text-gray-600">
                                Lance la commande <code
                                    class="px-1.5 py-0.5 bg-gray-100 rounded text-xxxs font-mono">products:index</code>
                                pour indexer tous les produits actifs dans Meilisearch.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.meilisearch.index-products') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-1.5 bg-black text-white text-xs font-semibold rounded-lg hover:bg-gray-900 transition">
                                Lancer
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Importer tous les produits --}}
                <div class="rounded-xl border border-black/5 p-4 hover:border-black/20 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-upload class="w-4 h-4 text-gray-700" />
                                <h4 class="text-xs font-semibold text-gray-900">Importer tous les produits</h4>
                            </div>
                            <p class="text-xs text-gray-600">
                                Lance la commande Scout <code
                                    class="px-1.5 py-0.5 bg-gray-100 rounded text-xxxs font-mono">scout:import</code>
                                pour importer tous les produits dans l'index Meilisearch.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.meilisearch.import-all') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-1.5 bg-gray-700 text-white text-xs font-semibold rounded-lg hover:bg-gray-800 transition">
                                Importer
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Vider l'index --}}
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 hover:border-red-300 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-trash-2 class="w-4 h-4 text-red-700" />
                                <h4 class="text-xs font-semibold text-red-900">Vider l'index Meilisearch</h4>
                            </div>
                            <p class="text-xs text-red-700">
                                Lance la commande Scout <code
                                    class="px-1.5 py-0.5 bg-red-100 rounded text-xxxs font-mono">scout:flush</code>
                                pour supprimer tous les produits de l'index. <strong>Cette action est
                                    irréversible.</strong>
                            </p>
                        </div>
                        <button type="button"
                                class="px-4 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition"
                                @click="$dispatch('open-modal', { name: 'flush-meilisearch-index' })">
                            Vider
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Informations --}}
        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <x-lucide-info class="w-5 h-5 text-gray-600" />
                </div>
                <div class="flex-1">
                    <h4 class="text-xs font-semibold text-gray-900 mb-1">Informations</h4>
                    <ul class="text-xs text-gray-700 space-y-1">
                        <li>• Les produits sont automatiquement indexés lors de leur création ou modification.</li>
                        <li>• Utilisez "Indexer les produits actifs" pour une synchronisation complète.</li>
                        <li>• L'import Scout est utile en cas de réinitialisation de l'index.</li>
                        <li>• La configuration Meilisearch se trouve dans le fichier <code
                                class="px-1 py-0.5 bg-gray-100 rounded font-mono">.env</code></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Modal de confirmation pour vider l'index --}}
        <x-admin::modal name="flush-meilisearch-index"
            title="Vider l'index Meilisearch ?"
            description="Cette action supprimera tous les produits de l'index. Cette action est irréversible."
            size="max-w-md">
            <p class="text-xs text-gray-600">
                Êtes-vous sûr de vouloir vider complètement l'index Meilisearch ?
                <br><br>
                Cela lancera la commande <code class="px-1.5 py-0.5 bg-gray-100 rounded text-xxxs font-mono">scout:flush</code>
                qui supprimera <span class="font-semibold">tous les produits indexés</span>.
            </p>

            <div class="flex justify-end gap-2 pt-3">
                <button type="button"
                    class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                    @click="open = false">
                    Annuler
                </button>

                <form method="POST" action="{{ route('admin.settings.meilisearch.flush-index') }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-1.5 text-xxxs font-medium text-white hover:bg-red-700">
                        <x-lucide-trash-2 class="h-3 w-3" />
                        Confirmer et vider
                    </button>
                </form>
            </div>
        </x-admin::modal>
    </div>
@endsection
