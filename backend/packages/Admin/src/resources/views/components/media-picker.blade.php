@props(['name' => 'media-picker'])

<div
    x-data="mediaPicker('{{ $name }}')"
    x-show="isOpen"
    x-cloak
    @open-media-picker.window="handleOpen($event)"
    @keydown.escape.window="close()"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <!-- Backdrop -->
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="close()"
            class="fixed inset-0 transition-opacity bg-gray-900/50 backdrop-blur-sm"
        ></div>

        <!-- Modal -->
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative inline-block w-full max-w-5xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl border border-black/5 shadow-xl sm:my-8 sm:align-middle sm:p-6"
        >
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Sélectionner une image</h3>
                    <nav class="flex items-center gap-1 mt-1 text-xxxs text-gray-500" aria-label="Breadcrumb">
                        <button @click="navigateToFolder(null)" class="hover:text-gray-700">Racine</button>
                        <template x-for="crumb in breadcrumbs" :key="crumb.id">
                            <span>
                                <span class="text-gray-400">/</span>
                                <button @click="navigateToFolder(crumb.id)" class="hover:text-gray-700 ml-1" x-text="crumb.name"></button>
                            </span>
                        </template>
                    </nav>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="$refs.uploadInput.click()"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Upload
                    </button>
                    <button @click="close()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Upload Input (hidden) -->
            <input
                type="file"
                x-ref="uploadInput"
                @change="uploadFiles($event)"
                multiple
                accept="image/*"
                class="hidden"
            >

            <!-- Loading State -->
            <div x-show="loading" class="flex items-center justify-center py-12">
                <div class="flex flex-col items-center gap-3">
                    <svg class="animate-spin h-8 w-8 text-gray-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs text-gray-500">Chargement...</span>
                </div>
            </div>

            <!-- Content -->
            <div x-show="!loading" class="max-h-96 overflow-y-auto">
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2 p-2">
                    <!-- Folders -->
                    <template x-for="folder in folders" :key="'folder-' + folder.id">
                        <button
                            @click="navigateToFolder(folder.id)"
                            class="border border-slate-200 rounded-lg p-2 hover:border-slate-300 hover:bg-slate-50/50 transition flex flex-col items-center justify-center h-24 group"
                        >
                            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <span class="mt-1 text-xxxs text-gray-700 text-center font-medium line-clamp-1" x-text="folder.name"></span>
                        </button>
                    </template>

                    <!-- Images -->
                    <template x-for="item in items" :key="'item-' + item.id">
                        <button
                            @click="selectImage(item)"
                            class="border rounded-lg overflow-hidden hover:ring-2 hover:ring-black transition"
                            :class="{ 'ring-2 ring-black': selectedImage?.id === item.id }"
                        >
                            <div class="aspect-square bg-slate-100 flex items-center justify-center">
                                <img :src="item.thumb" :alt="item.name" class="w-full h-full object-cover">
                            </div>
                            <div class="p-1.5 bg-white border-t border-slate-100" x-show="selectedImage?.id === item.id">
                                <svg class="w-3 h-3 text-gray-600 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </button>
                    </template>
                </div>

                <div x-show="folders.length === 0 && items.length === 0" class="text-center py-12">
                    <svg class="w-12 h-12 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-xs text-slate-500 font-medium">Aucune image dans ce dossier</p>
                    <p class="text-xxxs text-slate-400 mt-1">Uploadez des images pour commencer</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 flex justify-between items-center pt-4 border-t border-slate-100">
                <div class="text-xs text-gray-600">
                    <span x-show="selectedImage">
                        <span class="font-medium" x-text="selectedImage?.name"></span>
                        <span class="text-gray-400 ml-2" x-show="selectedImage?.width && selectedImage?.height">
                            <span x-text="selectedImage?.width"></span>×<span x-text="selectedImage?.height"></span>px
                        </span>
                    </span>
                    <span x-show="!selectedImage" class="text-gray-400">Aucune image sélectionnée</span>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="close()"
                        class="rounded-lg border border-gray-200 px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Annuler
                    </button>
                    <button
                        @click="confirmSelection()"
                        :disabled="!selectedImage"
                        class="rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Sélectionner
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.mediaPickerConfig = {
    mediaRoute: '{{ route('admin.apparence.api.media') }}',
    storeRoute: '{{ route('admin.apparence.media.store') }}'
};
</script>
@vite(['packages/Admin/src/resources/js/media-picker.js'])
