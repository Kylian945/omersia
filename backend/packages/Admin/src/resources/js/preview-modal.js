// Modal de prévisualisation pour le page builder classique
document.addEventListener('DOMContentLoaded', () => {
    // Injecter le modal dans le body
    const modalHTML = `
        <div x-data="{ show: false, loaded: false, url: '' }"
             @preview-open.window="show = true; loaded = false; url = window.previewUrl || ''"
             @preview-close.window="show = false"
             x-show="show"
             x-cloak
             @click.self="show = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
             style="display: none;">
            <div @click.stop class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl h-[90vh] flex flex-col">
                <div class="flex items-center justify-between border-b border-neutral-200 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900">Aperçu de la page</h3>
                    </div>
                    <button type="button" @click="show = false" class="inline-flex items-center justify-center rounded-lg p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-hidden bg-neutral-50 relative">
                    <div x-show="!loaded" class="absolute inset-0 flex items-center justify-center bg-white z-10">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 mb-4">
                                <svg class="animate-spin h-6 w-6 text-neutral-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </div>
                            <p class="text-sm text-neutral-600">Chargement...</p>
                        </div>
                    </div>
                    <iframe x-show="show" @load="loaded = true" :src="url" class="w-full h-full border-0"></iframe>
                </div>
                <div class="flex items-center justify-between border-t border-neutral-200 px-6 py-4 bg-white rounded-b-2xl">
                    <p class="text-xs text-neutral-500">Aperçu de la page</p>
                    <button type="button" @click="show = false" class="inline-flex items-center gap-2 rounded-lg bg-neutral-900 text-white px-4 py-2 text-sm font-medium hover:bg-neutral-800">Fermer</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
});
