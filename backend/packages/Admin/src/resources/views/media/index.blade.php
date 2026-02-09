@extends('admin::layout')

@section('title', 'Galerie d\'images')
@section('page-title', 'Galerie d\'images')

@section('content')
<div x-data="mediaLibrary()">
    <div class="flex items-center justify-between mb-3">
        <div>
            <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                <x-lucide-images class="w-3 h-3" />
                Galerie d'images
            </div>
            <div class="text-xs text-gray-500">
                <nav class="flex items-center gap-1" aria-label="Breadcrumb">
                    <a href="{{ route('admin.apparence.media.index') }}" class="hover:text-gray-700">Racine</a>
                    @foreach($breadcrumbs as $crumb)
                        <span class="text-gray-400">/</span>
                        <a href="{{ route('admin.apparence.media.index', ['folder_id' => $crumb->id]) }}" class="hover:text-gray-700">
                            {{ $crumb->name }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>

        <div class="flex gap-2">
            <button
                @click="openCreateFolderModal()"
                class="rounded-lg border border-gray-200 px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
            >
                Nouveau dossier
            </button>
            <button
                @click="$refs.fileInput.click()"
                class="rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black"
            >
                Ajouter des images
            </button>
        </div>
    </div>

    <form x-ref="uploadForm" action="{{ route('admin.apparence.media.store') }}" method="POST" enctype="multipart/form-data" class="hidden">
        @csrf
        <input type="hidden" name="folder_id" value="{{ $folder?->id }}">
        <input
            type="file"
            name="images[]"
            multiple
            accept="image/*"
            x-ref="fileInput"
            @change="submitUpload()"
        >
    </form>

    <!-- Loading indicator -->
    <div x-show="uploading" x-cloak class="mb-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-700">
        <div class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Upload en cours...</span>
        </div>
    </div>

    @if($folders->isEmpty() && $items->isEmpty())
        <div class="border border-dashed border-slate-200 rounded-xl p-6 text-center text-xs text-slate-500 bg-slate-50/40">
            <x-lucide-image class="w-12 h-12 mx-auto mb-3 text-slate-400" />
            <p class="font-medium">Aucune image dans ce dossier</p>
            <p class="mt-1">Commencez par ajouter des images ou créer des dossiers</p>
        </div>
    @else
        <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden p-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @if($folder && $folder->parent_id)
                    <a href="{{ route('admin.apparence.media.index', ['folder_id' => $folder->parent_id]) }}"
                       class="border border-dashed border-slate-300 rounded-lg p-4 hover:border-slate-400 hover:bg-slate-50/50 transition flex flex-col items-center justify-center h-32">
                        <x-lucide-arrow-left class="w-8 h-8 text-slate-400" />
                        <span class="mt-2 text-xxxs text-slate-600 font-medium">Retour</span>
                    </a>
                @else
                    @if($folder)
                        <a href="{{ route('admin.apparence.media.index') }}"
                           class="border border-dashed border-slate-300 rounded-lg p-4 hover:border-slate-400 hover:bg-slate-50/50 transition flex flex-col items-center justify-center h-32">
                            <x-lucide-arrow-left class="w-8 h-8 text-slate-400" />
                            <span class="mt-2 text-xxxs text-slate-600 font-medium">Retour</span>
                        </a>
                    @endif
                @endif

                @foreach($folders as $subFolder)
                    <div class="border border-slate-200 rounded-lg hover:border-slate-300 transition flex flex-col items-center justify-between h-32 relative group bg-slate-50/30">
                        <a href="{{ route('admin.apparence.media.index', ['folder_id' => $subFolder->id]) }}" class="flex-1 flex flex-col items-center justify-center w-full p-3">
                            <x-lucide-folder class="w-10 h-10 text-gray-500" />
                            <span class="mt-2 text-xxxs text-gray-700 text-center font-medium line-clamp-2">{{ $subFolder->name }}</span>
                        </a>
                        <button type="button"
                                @click="$dispatch('open-modal', { name: 'delete-folder-{{ $subFolder->id }}' })"
                                class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition rounded-full border border-gray-100 px-1.5 py-0.5 text-xxxs text-gray-500 hover:bg-gray-50">
                            <x-lucide-trash-2 class="w-3 h-3" />
                        </button>
                    </div>

                    {{-- Modal de confirmation de suppression du dossier --}}
                    <x-admin::modal name="delete-folder-{{ $subFolder->id }}"
                        :title="'Supprimer le dossier « ' . $subFolder->name . ' » ?'"
                        description="Cette action supprimera également tout le contenu du dossier."
                        size="max-w-md">
                        <p class="text-xs text-gray-600">
                            Voulez-vous vraiment supprimer le dossier
                            <span class="font-semibold">{{ $subFolder->name }}</span> et son contenu ?
                        </p>

                        <div class="flex justify-end gap-2 pt-3">
                            <button type="button"
                                class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                                @click="open = false">
                                Annuler
                            </button>

                            <form action="{{ route('admin.apparence.media.folders.destroy', $subFolder) }}" method="POST">
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
                @endforeach

                @foreach($items as $item)
                    <div class="border border-slate-200 rounded-lg overflow-hidden hover:shadow-md transition relative group bg-white">
                        <div class="aspect-square bg-slate-100 flex items-center justify-center">
                            <img src="{{ $item->url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                        </div>
                        <div class="p-2 bg-white border-t border-slate-100">
                            <p class="text-xxxs text-gray-700 truncate font-medium" title="{{ $item->name }}">{{ $item->name }}</p>
                            <div class="flex items-center justify-between mt-0.5">
                                <p class="text-xxxs text-gray-400">{{ $item->size_formatted }}</p>
                                @if($item->width && $item->height)
                                    <p class="text-xxxs text-gray-400">{{ $item->width }}×{{ $item->height }}</p>
                                @endif
                            </div>
                        </div>
                        <button type="button"
                                @click="$dispatch('open-modal', { name: 'delete-media-{{ $item->id }}' })"
                                class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition rounded-full border border-gray-100 px-1.5 py-0.5 bg-white/90 backdrop-blur text-xxxs text-gray-500 hover:bg-gray-50">
                            <x-lucide-trash-2 class="w-3 h-3" />
                        </button>
                    </div>

                    {{-- Modal de confirmation de suppression de l'image --}}
                    <x-admin::modal name="delete-media-{{ $item->id }}"
                        :title="'Supprimer l\'image « ' . $item->name . ' » ?'"
                        description="Cette action est définitive et ne peut pas être annulée."
                        size="max-w-md">
                        <p class="text-xs text-gray-600">
                            Voulez-vous vraiment supprimer l'image
                            <span class="font-semibold">{{ $item->name }}</span> ?
                        </p>

                        <div class="flex justify-end gap-2 pt-3">
                            <button type="button"
                                class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                                @click="open = false">
                                Annuler
                            </button>

                            <form action="{{ route('admin.apparence.media.destroy', $item) }}" method="POST">
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
                @endforeach
            </div>
        </div>
    @endif

    <!-- Modal Create Folder -->
    <div x-show="showCreateFolderModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="showCreateFolderModal = false"></div>

            <div class="relative bg-white rounded-2xl border border-black/5 shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900">Créer un nouveau dossier</h3>
                    <button @click="showCreateFolderModal = false" class="text-gray-400 hover:text-gray-600">
                        <x-lucide-x class="w-4 h-4" />
                    </button>
                </div>

                <form action="{{ route('admin.apparence.media.folders.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $folder?->id }}">

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-700 mb-2">Nom du dossier</label>
                        <input
                            type="text"
                            name="name"
                            required
                            class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300"
                            placeholder="Ex: Bannières, Produits..."
                        >
                    </div>

                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showCreateFolderModal = false"
                            class="rounded-lg border border-gray-200 px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black"
                        >
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
