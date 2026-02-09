{{-- Widget: Image --}}
<template x-if="currentWidget().type === 'image'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
            <div class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Image</span>

                <template x-if="currentWidget().props.url">
                    <div class="mb-2 relative group rounded-lg overflow-hidden border border-neutral-200">
                        <img :src="currentWidget().props.url" alt="Preview" class="w-full h-32 object-cover">
                        <button
                            @click="currentWidget().props.url = ''; sync();"
                            class="absolute top-1.5 right-1.5 p-1 bg-white/90 backdrop-blur text-red-500 rounded-md opacity-0 group-hover:opacity-100 transition hover:bg-white"
                            type="button"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>

                <div class="grid grid-cols-2 gap-2">
                    <button
                        @click="window.dispatchEvent(new CustomEvent('open-media-picker', { detail: { name: 'media-picker', callback: (image) => { currentWidget().props.url = image.url; sync(); } } }))"
                        class="px-3 py-2 text-xs font-medium bg-[#111827] text-white rounded-lg hover:bg-black transition"
                        type="button"
                    >
                        Galerie
                    </button>
                    <button
                        @click="$refs.directUpload.click()"
                        class="px-3 py-2 text-xs font-medium border border-neutral-200 text-neutral-700 rounded-lg hover:bg-neutral-50 transition"
                        type="button"
                    >
                        Upload
                    </button>
                </div>

                <input
                    type="file"
                    x-ref="directUpload"
                    @change="uploadImageDirect($event)"
                    accept="image/*"
                    class="hidden"
                >
            </div>

            <div class="pt-3 border-t border-neutral-100 space-y-2.5">
                <label class="block">
                    <span class="text-xs font-medium text-neutral-600 block mb-1">URL de l'image</span>
                    <input type="text" x-model="currentWidget().props.url" @input="sync()"
                        class="w-full px-2.5 py-1.5 bg-white border border-neutral-200 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300"
                        placeholder="https://...">
                </label>

                <label class="block">
                    <span class="text-xs font-medium text-neutral-600 block mb-1">Texte alternatif</span>
                    <input type="text" x-model="currentWidget().props.alt" @input="sync()"
                        class="w-full px-2.5 py-1.5 bg-white border border-neutral-200 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300"
                        placeholder="Description de l'image">
                </label>
            </div>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">

            {{-- Section Style --}}
            <div class="border-b border-neutral-100 pb-3">
                <span class="text-xs font-semibold text-neutral-700 block mb-3">
                    <x-lucide-palette class="w-3.5 h-3.5 inline-block mr-1" />
                    Style de l'image
                </span>

                {{-- Aspect Ratio --}}
                <label class="block mb-3">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                        Ratio d'aspect
                    </span>
                    <select x-model="currentWidget().props.aspectRatio" @change="sync()"
                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
                        <option value="">Auto (original)</option>
                        <option value="1:1">Carré (1:1)</option>
                        <option value="4:3">Standard (4:3)</option>
                        <option value="16:9">Paysage (16:9)</option>
                        <option value="2:1">Bannière (2:1)</option>
                        <option value="21:9">Ultra-large (21:9)</option>
                    </select>
                </label>

                {{-- Object Fit --}}
                <label class="block mb-3">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                        Ajustement de l'image
                    </span>
                    <select x-model="currentWidget().props.objectFit" @change="sync()"
                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
                        <option value="">Couvrir (défaut)</option>
                        <option value="cover">Remplir - Crop si nécessaire</option>
                        <option value="contain">Contenir - Tout visible</option>
                        <option value="fill">Étirer - Peut déformer</option>
                        <option value="scale-down">Réduire seulement</option>
                    </select>
                </label>

                {{-- Position du crop --}}
                <label class="block mb-3">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                        Position du crop
                    </span>
                    <select x-model="currentWidget().props.objectPosition" @change="sync()"
                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
                        <option value="">Centre (défaut)</option>
                        <option value="top">Haut</option>
                        <option value="center">Centre</option>
                        <option value="bottom">Bas</option>
                        <option value="left">Gauche</option>
                        <option value="right">Droite</option>
                    </select>
                </label>

                {{-- Taille personnalisée --}}
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <label class="block">
                        <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                            Hauteur (px)
                        </span>
                        <input type="number" x-model.number="currentWidget().props.height"
                            @input="sync()" min="0" max="2000" placeholder="Auto"
                            class="w-full px-2.5 py-1.5 bg-white border border-neutral-200 rounded-md text-xs">
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                            Largeur (px)
                        </span>
                        <input type="number" x-model.number="currentWidget().props.width"
                            @input="sync()" min="0" max="2000" placeholder="Auto"
                            class="w-full px-2.5 py-1.5 bg-white border border-neutral-200 rounded-md text-xs">
                    </label>
                </div>

                {{-- Info helper --}}
                <div class="p-2 bg-blue-50 border border-blue-100 rounded-md">
                    <p class="text-xs text-blue-800">
                        <x-lucide-info class="w-3 h-3 inline-block mr-1" />
                        Si aspect ratio est défini, il prime sur hauteur/largeur.
                    </p>
                </div>
            </div>

            {{-- Section Visibilité --}}
            <div class="pt-3">
                <span class="text-xs font-semibold text-neutral-700 block mb-3">
                    <x-lucide-eye class="w-3.5 h-3.5 inline-block mr-1" />
                    Visibilité
                </span>
                @include('admin::builder.partials.widget-settings-tab')
            </div>
        </div>
    </div>
</template>
