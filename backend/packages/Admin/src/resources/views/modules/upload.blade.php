@extends('admin::layout')

@section('title', 'Modules')
@section('page-title', 'Importer un module')

@section('content')
    <div class="">
        {{-- Banner succès / erreur --}}
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form x-data="uploadForm()" action="{{ route('admin.modules.upload.store') }}" method="post"
            enctype="multipart/form-data" class="rounded-2xl border border-neutral-200 bg-white shadow-sm">
            @csrf

            <div class="border-b border-neutral-100 px-5 py-4">
                <div class="flex items-center gap-2">
                    <x-lucide-file-archive class="h-5 w-5 text-neutral-600" />
                    <h3 class="text-body-15 font-semibold">Importer un module</h3>
                </div>
                <p class="mt-1 text-sm text-neutral-500">Chargez un fichier .zip contenant un module valide (avec <code
                        class="font-mono text-xs">module.json</code> à la racine).</p>
            </div>

            <div class="px-5 py-8 space-y-5 max-w-3xl mx-auto">
                {{-- Dropzone --}}
                <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                    @drop.prevent="handleDrop($event)" class="block cursor-pointer rounded-xl border-2 border-dashed"
                    :class="dragging ? 'border-neutral-400 bg-neutral-50' : 'border-neutral-200 hover:border-neutral-300'">
                    <div class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center">
                        <x-lucide-file-archive class="h-8 w-8 text-neutral-500" />
                        <div class="text-sm">
                            <span class="font-medium">Glissez-déposez votre .zip</span>
                            <span class="text-neutral-500"> ou cliquez pour parcourir</span>
                        </div>
                        <div class="text-xs text-neutral-500">Taille max 50 Mo • Formats acceptés : .zip</div>
                        <input x-ref="file" @change="updateFileName" type="file" name="zip" accept=".zip" required
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

                {{-- Options (toggles façon Shopify) --}}
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="flex items-center justify-between rounded-xl border border-neutral-200 px-4 py-3">
                        <div>
                            <div class="text-sm font-medium">Activer après import</div>
                            <div class="text-xs text-neutral-500">Le provider sera chargé et le module disponible.</div>
                        </div>
                        <label class="relative inline-flex h-6 w-11 items-center">
                            <input type="checkbox" name="activate" value="1" checked class="peer sr-only">
                            <span
                                class="absolute h-6 w-11 rounded-full bg-neutral-300 transition peer-checked:bg-emerald-600"></span>
                            <span
                                class="absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between rounded-xl border border-neutral-200 px-4 py-3">
                        <div>
                            <div class="text-sm font-medium">Lancer les migrations</div>
                            <div class="text-xs text-neutral-500">Exécute les migrations du module si présentes.</div>
                        </div>
                        <label class="relative inline-flex h-6 w-11 items-center">
                            <input type="checkbox" name="migrate" value="1" checked class="peer sr-only">
                            <span
                                class="absolute h-6 w-11 rounded-full bg-neutral-300 transition peer-checked:bg-emerald-600"></span>
                            <span
                                class="absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>

                    <div
                        class="sm:col-span-2 flex items-center justify-between rounded-xl border border-neutral-200 px-4 py-3">
                        <div>
                            <div class="text-sm font-medium">Lancer le seeder (si dispo)</div>
                            <div class="text-xs text-neutral-500">Peut ajouter des données d’exemple ou de
                                configuration.</div>
                        </div>
                        <label class="relative inline-flex h-6 w-11 items-center">
                            <input type="checkbox" name="seed" value="1" class="peer sr-only">
                            <span
                                class="absolute h-6 w-11 rounded-full bg-neutral-300 transition peer-checked:bg-emerald-600"></span>
                            <span
                                class="absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>
                </div>

                {{-- Note sécurité --}}
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                    <div class="flex items-start gap-2">
                        <x-lucide-shield-alert class="mt-0.5 h-4 w-4" />
                        <p>
                            Pour protéger votre back-office, seuls les modules signés et scannés sont recommandés.
                            <a href="#" class="underline">En savoir plus</a>.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-neutral-100 px-5 py-4">
                <a href="{{ route('admin.modules.index') }}" class="text-sm text-neutral-600 hover:text-neutral-800">←
                    Retour à la liste des modules</a>
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-black px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                    :disabled="submitting" x-bind:class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
                    @submit="submitting = true">

                    <x-lucide-upload class="h-4 w-4" x-show="!submitting" x-cloak />

                    <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="submitting" x-cloak />

                    Importer le module
                </button>
            </div>
        </form>
    </div>

@endsection
