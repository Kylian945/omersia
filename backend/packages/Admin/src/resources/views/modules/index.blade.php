@extends('admin::layout')

@section('title', 'Modules')
@section('page-title', 'Modules')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                <x-lucide-layers class="w-3 h-3" />
                <span class="font-semibold text-sm">Gestion des modules</span>
            </div>
            <div class="text-xs text-gray-500">Gérez les modules liés à votre boutique : Ajouter, désactiver, réinitialiser,
                supprimer.</div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.modules.positions') }}"
                class="text-xs inline-flex items-center font-semibold gap-2 px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                <x-lucide-layout-grid class="w-3 h-3" />
                Positions
            </a>
            <a href="{{ route('admin.modules.upload') }}"
                class="text-xs inline-flex items-center font-semibold gap-2 px-3 py-2 rounded-lg bg-black text-white hover:opacity-90">
                Importer un module
            </a>
        </div>
    </div>

    <div x-data="{ q: '' }" class="space-y-4">
        <div class="flex items-center gap-3">
            <div class="relative flex-1">
                <input x-model="q" type="text" placeholder="Rechercher un module…"
                    class="w-full rounded-xl border border-neutral-200 bg-white px-10 py-2 text-sm placeholder:text-neutral-400 focus:border-neutral-400 focus:outline-none">
                <x-lucide-search class="absolute left-3 top-2.5 h-4 w-4 text-neutral-400" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3">
            @forelse ($modules as $m)
                <div x-show="q === '' || '{{ Str::of($m['title'])->lower() }}'.includes(q.toLowerCase()) || '{{ $m['slug'] }}'.includes(q.toLowerCase())"
                    class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm hover:shadow transition-shadow">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <img class="w-10 h-10 rounded-lg border border-neutral-200 bg-white object-cover"
                                src="{{ asset('/images/modules/' . $m['slug'] . '.png') }}"
                                onerror="this.src='{{ asset('images/modules/no-icon.png') }}'"
                                alt="Icon du module {{ $m['slug'] }}">
                            <div class="flex items-start gap-2">
                                <div class="flex flex-col">
                                    <span class="text-body-15 font-semibold">{{ $m['title'] }}</span>
                                    @if ($m['author'])
                                        <span class="text-xxs text-neutral-500">par {{ $m['author'] }}</span>
                                    @endif
                                </div>
                                <span
                                    class="rounded-full border border-neutral-200 px-2 py-0.5 text-xs text-neutral-600">v{{ $m['version'] }}</span>
                            </div>
                        </div>


                        {{-- Toggle Actif / Inactif façon Shopify --}}
                        <div class="flex items-center">
                            @if ($m['enabled'])
                                <form method="post" action="{{ route('admin.modules.disable', $m['slug']) }}">
                                    @csrf
                                    <button type="submit"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full bg-emerald-600 transition hover:brightness-95">
                                        <span class="sr-only">Désactiver</span>
                                        <span
                                            class="inline-block h-5 w-5 translate-x-5 rounded-full bg-white shadow transition"></span>
                                    </button>
                                </form>
                            @else
                                <form method="post" action="{{ route('admin.modules.enable', $m['slug']) }}">
                                    @csrf
                                    <button type="submit"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full bg-neutral-300 transition hover:brightness-95">
                                        <span class="sr-only">Activer</span>
                                        <span
                                            class="inline-block h-5 w-5 translate-x-1 rounded-full bg-white shadow transition"></span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>


                    @if ($m['description'])
                        <p class="mt-3 text-sm text-neutral-600 line-clamp-3">{{ $m['description'] }}</p>
                    @else
                        <p class="mt-3 text-sm text-neutral-500 italic">Aucune description fournie.</p>
                    @endif

                    <div class="mt-4 flex items-center gap-2">
                        <form method="post" action="{{ route('admin.modules.migrate', $m['slug']) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-1.5 text-xs hover:bg-neutral-50">
                                <x-lucide-database class="h-4 w-4" /> Migrer
                            </button>
                        </form>

                        <form method="post" action="{{ route('admin.modules.sync', $m['slug']) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-1.5 text-xs hover:bg-neutral-50">
                                <x-lucide-refresh-cw class="h-4 w-4" /> Sync Hooks
                            </button>
                        </form>

                        @php
                            // On essaie de récupérer une éventuelle route de paramètres
                            $settingsRoute = $m['settings_route'] ?? ($m['menu'][0]['route'] ?? null);
                        @endphp

                        @php

                            // On essaie de récupérer une éventuelle route de paramètres
                            $settingsRoute = $m['settings_route'] ?? ($m['menu'][0]['route'] ?? null);
                            $hasSettingsRoute = $settingsRoute && Route::has($settingsRoute);
                        @endphp

                        @if ($hasSettingsRoute)
                            <a href="{{ route($settingsRoute) }}"
                                class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-1.5 text-xs hover:bg-neutral-50">
                                <x-lucide-settings class="h-4 w-4" />
                                Paramètres
                            </a>
                        @else
                            <button disabled
                                class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-1.5 text-xs text-neutral-400">
                                <x-lucide-settings class="h-4 w-4" />
                                Paramètres
                            </button>
                        @endif


                        {{-- Bouton qui ouvre la modal de suppression --}}
                        <button type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-1.5 text-xs text-red-700 hover:bg-red-50"
                            @click="$dispatch('open-modal', { name: 'delete-module-{{ $m['slug'] }}' })">
                            <x-lucide-trash-2 class="h-4 w-4" /> Supprimer
                        </button>

                        {{-- Modal de confirmation de suppression --}}
                        <x-admin::modal name="delete-module-{{ $m['slug'] }}" :title="'Supprimer le module « ' . $m['title'] . ' » ?'"
                            description="Cette action est définitive. Les fichiers et tables associées au module seront supprimés."
                            size="max-w-md">
                            <p class="text-xxs text-gray-600">
                                Voulez-vous vraiment supprimer le module
                                <span class="font-semibold">{{ $m['title'] }}</span> (<code
                                    class="text-xxs bg-gray-100 px-1 py-0.5 rounded">{{ $m['slug'] }}</code>) ?
                            </p>

                            <div class="flex justify-end gap-2 pt-3">
                                <button type="button"
                                    class="px-3 py-1.5 rounded-lg border border-gray-200 text-xxs text-gray-700 hover:bg-gray-50"
                                    @click="open = false">
                                    Annuler
                                </button>

                                <form method="post" action="{{ route('admin.modules.destroy', $m['slug']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-lg bg-black px-3 py-1.5 text-xxs font-medium text-white hover:bg-neutral-900">
                                        <x-lucide-trash-2 class="h-3 w-3" />
                                        Confirmer la suppression
                                    </button>
                                </form>
                            </div>
                        </x-admin::modal>

                    </div>
                </div>
            @empty
                <div
                    class="col-span-full rounded-2xl border border-neutral-200 bg-white p-6 text-center text-gray-500 text-xs">
                    Aucun module pour le moment. Importez un .zip pour commencer.
                </div>
            @endforelse
        </div>
    </div>
@endsection
