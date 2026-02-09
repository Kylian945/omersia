@extends('admin::settings.layout')

@section('settings-content')
    <div class="space-y-6">

        {{-- État du cache --}}
        <div class="rounded-2xl border border-black/5 bg-white p-6 space-y-4">
            <div class="flex items-center gap-3">
                <x-lucide-gauge class="w-4 h-4 text-gray-700" />
                <h3 class="text-sm font-semibold text-gray-900">État du cache</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 mb-1">Cache Driver</div>
                    <div class="text-xs font-semibold text-gray-900">
                        {{ config('cache.default') }}
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 mb-1">Queue Driver</div>
                    <div class="text-xs font-semibold text-gray-900">
                        {{ config('queue.default') }}
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 mb-1">Session Driver</div>
                    <div class="text-xs font-semibold text-gray-900">
                        {{ config('session.driver') }}
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4">
                    <div class="text-xs text-gray-500 mb-1">Environment</div>
                    <div class="text-xs font-semibold text-gray-900">
                        {{ config('app.env') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Action globale --}}
        <div class="rounded-2xl border border-black/5 bg-white p-6 space-y-6">
            <div class="flex items-center gap-3">
                <x-lucide-zap class="w-4 h-4 text-gray-700" />
                <h3 class="text-sm font-semibold text-gray-900">Action globale</h3>
            </div>

            <div class="grid grid-cols-1 gap-4">
                {{-- Vider tous les caches --}}
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 hover:border-gray-300 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-trash-2 class="w-4 h-4 text-gray-700" />
                                <h4 class="text-xs font-semibold text-gray-900">Vider tous les caches</h4>
                            </div>
                            <p class="text-xs text-gray-700">
                                Vide tous les caches en une seule action : cache application, config, routes, views, et optimizations. <strong>Recommandé après un déploiement.</strong>
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.performance.clear-all') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-1.5 bg-black text-white text-xs font-semibold rounded-lg hover:bg-gray-800 transition">
                                Tout vider
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions de cache --}}
        <div class="rounded-2xl border border-black/5 bg-white p-6 space-y-6">
            <div class="flex items-center gap-3">
                <x-lucide-layers class="w-4 h-4 text-gray-700" />
                <h3 class="text-sm font-semibold text-gray-900">Gestion des caches</h3>
            </div>

            <div class="grid grid-cols-1 gap-4">
                {{-- Cache application --}}
                <div class="rounded-xl border border-black/5 p-4 hover:border-black/20 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-database class="w-4 h-4 text-gray-700" />
                                <h4 class="text-xs font-semibold text-gray-900">Cache application</h4>
                            </div>
                            <p class="text-xs text-gray-600">
                                Lance la commande <code
                                    class="px-1.5 py-0.5 bg-gray-100 rounded text-xxxs font-mono">cache:clear</code>
                                pour vider le cache de l'application (données mises en cache via Cache::put()).
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.performance.clear-cache') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-1.5 bg-gray-700 text-white text-xs font-semibold rounded-lg hover:bg-gray-900 transition">
                                Vider
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Cache de configuration --}}
                <div class="rounded-xl border border-black/5 p-4 hover:border-black/20 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-settings class="w-4 h-4 text-gray-700" />
                                <h4 class="text-xs font-semibold text-gray-900">Cache de configuration</h4>
                            </div>
                            <p class="text-xs text-gray-600">
                                Lance la commande <code
                                    class="px-1.5 py-0.5 bg-gray-100 rounded text-xxxs font-mono">config:clear</code>
                                pour vider le cache de configuration (config/*.php).
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.performance.clear-config') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-1.5 bg-gray-700 text-white text-xs font-semibold rounded-lg hover:bg-gray-800 transition">
                                Vider
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Cache des routes --}}
                <div class="rounded-xl border border-black/5 p-4 hover:border-black/20 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-route class="w-4 h-4 text-gray-700" />
                                <h4 class="text-xs font-semibold text-gray-900">Cache des routes</h4>
                            </div>
                            <p class="text-xs text-gray-600">
                                Lance la commande <code
                                    class="px-1.5 py-0.5 bg-gray-100 rounded text-xxxs font-mono">route:clear</code>
                                pour vider le cache des routes (routes/*.php).
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.performance.clear-route') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-1.5 bg-gray-700 text-white text-xs font-semibold rounded-lg hover:bg-gray-800 transition">
                                Vider
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Cache des vues --}}
                <div class="rounded-xl border border-black/5 p-4 hover:border-black/20 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-eye class="w-4 h-4 text-gray-700" />
                                <h4 class="text-xs font-semibold text-gray-900">Cache des vues</h4>
                            </div>
                            <p class="text-xs text-gray-600">
                                Lance la commande <code
                                    class="px-1.5 py-0.5 bg-gray-100 rounded text-xxxs font-mono">view:clear</code>
                                pour vider le cache des vues Blade compilées.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.performance.clear-view') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-1.5 bg-gray-700 text-white text-xs font-semibold rounded-lg hover:bg-gray-800 transition">
                                Vider
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Cache optimisé --}}
                <div class="rounded-xl border border-black/5 p-4 hover:border-black/20 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-cpu class="w-4 h-4 text-gray-700" />
                                <h4 class="text-xs font-semibold text-gray-900">Cache optimisé</h4>
                            </div>
                            <p class="text-xs text-gray-600">
                                Lance la commande <code
                                    class="px-1.5 py-0.5 bg-gray-100 rounded text-xxxs font-mono">optimize:clear</code>
                                pour vider tous les fichiers d'optimisation mis en cache.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.performance.clear-optimize') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-1.5 bg-gray-700 text-white text-xs font-semibold rounded-lg hover:bg-gray-800 transition">
                                Vider
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Cache des events --}}
                <div class="rounded-xl border border-black/5 p-4 hover:border-black/20 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <x-lucide-radio class="w-4 h-4 text-gray-700" />
                                <h4 class="text-xs font-semibold text-gray-900">Cache des events</h4>
                            </div>
                            <p class="text-xs text-gray-600">
                                Lance la commande <code
                                    class="px-1.5 py-0.5 bg-gray-100 rounded text-xxxs font-mono">event:clear</code>
                                pour vider le cache des events et listeners découverts.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.performance.clear-event') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-1.5 bg-gray-700 text-white text-xs font-semibold rounded-lg hover:bg-gray-800 transition">
                                Vider
                            </button>
                        </form>
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
                        <li>• Utilisez "Vider tous les caches" après chaque déploiement pour éviter les problèmes de cache.</li>
                        <li>• Le cache de configuration et des routes améliore les performances en production.</li>
                        <li>• En environnement local, le cache est généralement désactivé pour faciliter le développement.</li>
                        <li>• Certains caches se régénèrent automatiquement lors de la prochaine requête.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
