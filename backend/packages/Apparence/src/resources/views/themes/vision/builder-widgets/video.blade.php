{{-- Widget: Video --}}
<template x-if="currentWidget().type === 'video'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
            <div class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Type de vidéo</span>
                <div class="flex gap-2">
                    <button type="button" @click="currentWidget().props.type = 'youtube'; sync()"
                        class="flex-1 px-3 py-2 text-xs rounded-lg border transition-all font-medium"
                        :class="currentWidget().props.type === 'youtube' ?
                            'bg-neutral-900 text-white border-neutral-900 shadow-sm' :
                            'bg-white text-neutral-700 border-neutral-300 hover:border-neutral-400'">
                        YouTube
                    </button>
                    <button type="button" @click="currentWidget().props.type = 'vimeo'; sync()"
                        class="flex-1 px-3 py-2 text-xs rounded-lg border transition-all font-medium"
                        :class="currentWidget().props.type === 'vimeo' ?
                            'bg-neutral-900 text-white border-neutral-900 shadow-sm' :
                            'bg-white text-neutral-700 border-neutral-300 hover:border-neutral-400'">
                        Vimeo
                    </button>
                    <button type="button" @click="currentWidget().props.type = 'upload'; sync()"
                        class="flex-1 px-3 py-2 text-xs rounded-lg border transition-all font-medium"
                        :class="currentWidget().props.type === 'upload' ?
                            'bg-neutral-900 text-white border-neutral-900 shadow-sm' :
                            'bg-white text-neutral-700 border-neutral-300 hover:border-neutral-400'">
                        Upload
                    </button>
                </div>
            </div>

            {{-- YouTube / Vimeo URL --}}
            <template x-if="currentWidget().props.type === 'youtube' || currentWidget().props.type === 'vimeo'">
                <label class="block">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">
                        <span x-show="currentWidget().props.type === 'youtube'">URL YouTube</span>
                        <span x-show="currentWidget().props.type === 'vimeo'">URL Vimeo</span>
                    </span>
                    <input type="text" x-model="currentWidget().props.url" @input="sync()"
                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300"
                        :placeholder="currentWidget().props.type === 'youtube' ? 'https://www.youtube.com/watch?v=...' : 'https://vimeo.com/...'">
                </label>
            </template>

            {{-- Upload Video --}}
            <template x-if="currentWidget().props.type === 'upload'">
                <div class="block">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">Fichier vidéo</span>

                    <template x-if="currentWidget().props.url">
                        <div class="mb-2 relative group rounded-lg overflow-hidden border border-neutral-200 bg-neutral-50">
                            <video :src="currentWidget().props.url" class="w-full h-32 object-cover" controls></video>
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

                    <label class="block">
                        <span class="text-xs font-medium text-neutral-600 block mb-1">URL de la vidéo</span>
                        <input type="text" x-model="currentWidget().props.url" @input="sync()"
                            class="w-full px-2.5 py-1.5 bg-white border border-neutral-200 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300"
                            placeholder="https://... ou /storage/videos/...">
                    </label>
                </div>
            </template>

            <hr class="border-neutral-200">

            {{-- Options --}}
            <div class="space-y-2">
                <label class="block">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">Ratio d'aspect</span>
                    <select x-model="currentWidget().props.aspectRatio" @change="sync()"
                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400">
                        <option value="16/9">16:9 (Standard)</option>
                        <option value="4/3">4:3 (Classique)</option>
                        <option value="21/9">21:9 (Cinéma)</option>
                        <option value="1/1">1:1 (Carré)</option>
                        <option value="9/16">9:16 (Portrait)</option>
                    </select>
                </label>

                <template x-if="currentWidget().props.type === 'upload'">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="currentWidget().props.autoplay" @change="sync()"
                            class="rounded border-neutral-300 text-neutral-900">
                        <span class="text-xs text-neutral-700">Lecture automatique</span>
                    </label>
                </template>

                <template x-if="currentWidget().props.type === 'upload'">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="currentWidget().props.loop" @change="sync()"
                            class="rounded border-neutral-300 text-neutral-900">
                        <span class="text-xs text-neutral-700">Lecture en boucle</span>
                    </label>
                </template>

                <template x-if="currentWidget().props.type === 'upload'">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="currentWidget().props.muted" @change="sync()"
                            class="rounded border-neutral-300 text-neutral-900">
                        <span class="text-xs text-neutral-700">Muet par défaut</span>
                    </label>
                </template>
            </div>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
