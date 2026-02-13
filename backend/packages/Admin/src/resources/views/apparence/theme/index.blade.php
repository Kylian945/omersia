@extends('admin::layout')

@section('title', 'Thème')
@section('page-title', 'Thème')

@section('content')
    <div x-data="{ themeActivation: true }">


        <div class="flex items-center justify-between mb-3">
            <div>
                <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                    <x-lucide-palette class="w-3 h-3" />
                    Thème
                </div>
                <div class="text-xs text-gray-500">
                    Personnalisez l'apparence de votre boutique : logo, nom et thème.
                </div>
            </div>
        </div>

        <div class="space-y-4">
            

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="space-y-4 lg:col-span-1">
                    {{-- Shop Name Section --}}

                    <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <h3 class="text-xs font-semibold text-gray-800">Nom de la boutique</h3>
                            <p class="text-xxxs text-gray-500 mt-0.5">
                                Le nom affiché sur votre boutique en ligne
                            </p>
                        </div>
                        <div class="px-4 py-4">
                            <form action="{{ route('admin.apparence.theme.shop-name.update') }}" method="POST"
                                class="space-y-3">
                                @csrf

                                <div>
                                    <label class="block text-xxxs font-medium text-gray-700 mb-1">
                                        Nom d'affichage
                                    </label>
                                    <input type="text" name="display_name"
                                        value="{{ old('display_name', $shop->display_name ?? ($shop->name ?? '')) }}"
                                        required maxlength="255"
                                        class="w-full rounded-xl border border-gray-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900"
                                        placeholder="Ex : Ma Super Boutique">
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="px-4 py-1.5 rounded-lg font-semibold bg-[#111827] text-xs text-white hover:bg-black">
                                        Mettre à jour le nom
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    {{-- Logo Section --}}
                    <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <h3 class="text-xs font-semibold text-gray-800">Logo de la boutique</h3>
                            <p class="text-xxxs text-gray-500 mt-0.5">
                                Uploadez votre logo (formats acceptés : JPG, PNG, GIF, SVG, WebP - max 2MB)
                            </p>
                        </div>
                        <div class="px-4 py-4">
                            <form action="{{ route('admin.apparence.theme.logo.update') }}" method="POST"
                                enctype="multipart/form-data" class="space-y-3">
                                @csrf

                                @if ($shop && $shop->logo_path)
                                    <div class="mb-3">
                                        <p class="text-xxxs text-gray-600 mb-2">Logo actuel :</p>
                                        <img src="{{ asset('storage/' . $shop->logo_path) }}" alt="Logo actuel"
                                            class="max-h-12">
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-xxxs font-medium text-gray-700 mb-1">
                                        Sélectionner un nouveau logo
                                    </label>
                                    <input type="file" name="logo" accept="image/*" required
                                        class="w-full text-xxxs rounded-xl border border-gray-200 px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-900">
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="px-4 py-1.5 rounded-lg font-semibold bg-[#111827] text-xs text-white hover:bg-black">
                                        Mettre à jour le logo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-2">
                    {{-- Themes Gallery Section --}}
                    <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-semibold text-gray-800">Bibliothèque de thèmes</h3>
                                <p class="text-xxxs text-gray-500 mt-0.5">
                                    Gérez vos thèmes installés et ajoutez-en de nouveaux
                                </p>
                            </div>
                            <button type="button" x-data
                                @click="window.dispatchEvent(new CustomEvent('open-modal', { detail: { name: 'upload-theme' } }))"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 font-semibold text-xs text-black hover:bg-gray-50">
                                + Ajouter un thème
                            </button>
                        </div>
                        <div class="px-4 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @forelse($themes as $theme)
                                    <div
                                        class="relative rounded-xl border-2 {{ $theme->is_active ? 'border-emerald-500' : 'border-gray-200' }} overflow-hidden group hover:border-gray-300 transition">
                                        {{-- Active Badge --}}
                                        @if ($theme->is_active)
                                            <div
                                                class="absolute top-2 right-2 z-10 bg-emerald-500 text-white text-xxxs font-semibold px-2 py-1 rounded-full flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Actif
                                            </div>
                                        @endif

                                        {{-- Theme Preview Image --}}
                                        <div
                                            class="aspect-video bg-gradient-to-br from-gray-50 to-gray-100 relative overflow-hidden">
                                            @if ($theme->preview_image)
                                                <img src="{{ asset('storage/' . $theme->preview_image) }}"
                                                    alt="{{ $theme->name }}" class="w-full h-full object-cover">
                                            @else
                                                {{-- Default mockup for default theme --}}
                                                <div
                                                    class="absolute top-0 left-0 right-0 bg-white/80 backdrop-blur border-b rounded-t-xl border-black/5">
                                                    <div class="flex items-center justify-between px-3 py-2">
                                                        <div class="flex items-center gap-2">
                                                            @if ($shop && $shop->logo_path)
                                                                <img src="{{ asset('storage/' . $shop->logo_path) }}"
                                                                    alt="Logo" class="h-4 w-auto object-contain">
                                                            @else
                                                                <div
                                                                    class="h-3 w-3 rounded-full bg-black text-white flex items-center justify-center text-xxxs font-bold">
                                                                    {{ $shop && $shop->display_name ? substr($shop->display_name, 0, 1) : 'O' }}
                                                                </div>
                                                            @endif
                                                            <span class="text-xxxs font-semibold">
                                                                {{ $shop->display_name ?? $shop->name ?? 'Omersia' }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <div class="h-3 w-3 rounded-full bg-gray-200"></div>
                                                            <div class="h-3 w-3 rounded-full bg-gray-200"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="absolute top-12 left-3 right-3 space-y-2">
                                                    <div class="h-20 bg-white rounded-lg shadow-sm"></div>
                                                    <div class="grid grid-cols-3 gap-2">
                                                        <div class="h-16 bg-white rounded-lg shadow-sm"></div>
                                                        <div class="h-16 bg-white rounded-lg shadow-sm"></div>
                                                        <div class="h-16 bg-white rounded-lg shadow-sm"></div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Theme Info --}}
                                        <div class="p-3 bg-white border-t border-gray-100">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="flex-1">
                                                    <h4 class="text-xs font-semibold text-gray-900">{{ $theme->name }}</h4>
                                                    <p class="text-xxxs text-gray-500 mt-0.5">
                                                        {{ $theme->description ?? 'Aucune description' }}
                                                    </p>
                                                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                                                        @if ($theme->metadata && isset($theme->metadata['technologies']))
                                                            @foreach ($theme->metadata['technologies'] as $tech)
                                                                <span
                                                                    class="inline-flex items-center text-xxxs text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">
                                                                    {{ $tech }}
                                                                </span>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                    @if ($theme->author)
                                                        <p class="text-xxxs text-gray-400 mt-2">Par {{ $theme->author }}</p>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Actions --}}
                                            <div class="mt-3 space-y-2">
                                                <div class="flex items-center gap-2">
                                                    @if (!$theme->is_active)
                                                        <button type="button" data-theme-id="{{ $theme->id }}"
                                                            class="activate-theme-btn flex-1 px-3 py-1.5 rounded-lg font-semibold bg-[#111827] text-xs text-white hover:bg-black transition">
                                                            Activer
                                                        </button>
                                                    @else
                                                        <div
                                                            class="flex-1 px-3 py-1.5 rounded-lg font-semibold bg-emerald-500 text-xs text-white text-center">
                                                            Thème actif
                                                        </div>
                                                    @endif

                                                    @if (!$theme->is_default)
                                                        <button type="button"
                                                            class="px-3 py-1.5 rounded-lg border border-red-200 text-xs text-red-600 hover:bg-red-50 transition"
                                                            @click="$dispatch('open-modal', { name: 'delete-theme-{{ $theme->id }}' })">
                                                            Supprimer
                                                        </button>
                                                    @endif
                                                </div>

                                                {{-- Customize button --}}
                                                <a href="{{ route('admin.apparence.theme.customize', $theme) }}"
                                                    class="block w-full text-center px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50 transition">
                                                    <span class="flex items-center justify-center gap-1.5">
                                                        <x-lucide-palette class="w-3 h-3" />
                                                        Personnaliser
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                @empty
                                    <div class="col-span-2 text-center py-8 text-xs text-gray-500">
                                        Aucun thème installé. Ajoutez votre premier thème !
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    {{-- Modals de suppression (hors grille pour ne pas polluer le listing) --}}
                    @foreach($themes as $theme)
                        @if (!$theme->is_default)
                            <x-admin::modal name="delete-theme-{{ $theme->id }}"
                                :title="'Supprimer le thème « ' . $theme->name . ' » ?'"
                                description="Cette action est définitive et ne peut pas être annulée."
                                size="max-w-md">
                                <p class="text-xs text-gray-600">
                                    Voulez-vous vraiment supprimer le thème
                                    <span class="font-semibold">{{ $theme->name }}</span> ?
                                </p>

                                <div class="flex justify-end gap-2 pt-3">
                                    <button type="button"
                                        class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                                        @click="open = false">
                                        Annuler
                                    </button>

                                    <form action="{{ route('admin.apparence.theme.destroy', $theme) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-xs font-medium text-white hover:bg-neutral-900">
                                            <x-lucide-trash-2 class="h-3 w-3" />
                                            Confirmer la suppression
                                        </button>
                                    </form>
                                </div>
                            </x-admin::modal>
                        @endif
                    @endforeach
                </div>
            </div>


        </div>

        {{-- Modal de confirmation de changement de thème --}}
        <x-admin::modal name="confirm-theme-activation" title="Confirmer le changement de thème" size="max-w-2xl">
            <div id="theme-comparison-content">
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin h-8 w-8 border-4 border-gray-300 border-t-gray-900 rounded-full"></div>
                </div>
            </div>
            <div id="theme-activation-actions" class="hidden pt-4">
                <div class="flex justify-end gap-2">
                <button type="button"
                    class="px-4 py-1.5 font-semibold rounded-lg border border-gray-200 text-xs text-gray-600 hover:bg-gray-50"
                    @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: { name: 'confirm-theme-activation' } }))">
                    Annuler
                </button>
                <form id="theme-activation-form" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        class="px-4 py-1.5 rounded-lg font-semibold bg-red-600 text-xs text-white hover:bg-red-700">
                        Confirmer et activer
                    </button>
                </form>
                </div>
            </div>
        </x-admin::modal>

        {{-- Modal d'upload de thème --}}
        <x-admin::modal name="upload-theme" title="Ajouter un nouveau thème"
            description="Uploadez un fichier .zip contenant votre thème (métadonnées et preview incluses dans le ZIP)." size="max-w-md">
            <form x-data="uploadForm()" action="{{ route('admin.apparence.theme.upload') }}" method="POST"
                enctype="multipart/form-data" class="space-y-3">
                @csrf

                <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                    @drop.prevent="handleDrop?.($event)" class="block cursor-pointer rounded-xl border-2 border-dashed"
                    :class="dragging ? 'border-neutral-400 bg-neutral-50' : 'border-neutral-200 hover:border-neutral-300'">
                    <div class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center">
                        <x-lucide-file-archive class="h-8 w-8 text-neutral-500" />
                        <div class="text-sm">
                            <span class="font-medium">Glissez-déposez votre .zip</span>
                            <span class="text-neutral-500"> ou cliquez pour parcourir</span>
                        </div>
                        <div class="text-xs text-neutral-500">Taille max 50 Mo • Formats acceptés : .zip</div>
                        <input x-ref="file" @change="updateFileName" type="file" name="theme" accept=".zip" required
                            class="sr-only" />
                        <template x-if="fileName">
                            <div
                                class="mt-2 inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs text-neutral-700">
                                <x-lucide-badge-check class="h-4 w-4 text-emerald-600" />
                                <span x-text="fileName"></span>
                            </div>
                        </template>
                    </div>
                </label>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button"
                        class="px-4 py-1.5 font-semibold rounded-lg border border-gray-200 text-xs text-gray-600 hover:bg-gray-50"
                        @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: { name: 'upload-theme' } }))">
                        Annuler
                    </button>
                    <button type="submit"
                        class="px-4 py-1.5 rounded-lg font-semibold bg-[#111827] text-xs text-white hover:bg-black">
                        Ajouter le thème
                    </button>
                </div>
            </form>
        </x-admin::modal>
    </div>
@endsection
