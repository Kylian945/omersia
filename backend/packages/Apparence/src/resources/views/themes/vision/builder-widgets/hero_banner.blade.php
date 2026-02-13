{{-- Widget: Hero Banner --}}
<template x-if="currentWidget().type === 'hero_banner'" x-init="initHeroBannerProps(); sync()">
    <div x-data="{
            activeWidgetTab: 'content',
            quillInstance: null,
            currentDescriptionHtml: '',
            initDescriptionQuill() {
                this.$nextTick(() => {
                    if (!this.$refs.heroDescriptionEditor || this.quillInstance) return;

                    const Quill = window.Quill;
                    if (!Quill) return;

                    this.quillInstance = new Quill(this.$refs.heroDescriptionEditor, {
                        theme: 'snow',
                        modules: {
                            toolbar: [
                                [{ 'header': [1, 2, 3, false] }],
                                ['bold', 'italic', 'underline'],
                                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                                ['link'],
                                ['clean']
                            ]
                        },
                        placeholder: 'Décrivez votre offre...',
                    });

                    this.syncDescriptionFromWidget();

                    this.quillInstance.on('text-change', () => {
                        const html = this.quillInstance.root.innerHTML;
                        this.currentDescriptionHtml = html;
                        currentWidget().props.description = html;
                        sync();
                    });

                    this.$watch('selected.widgetId', () => {
                        this.syncDescriptionFromWidget();
                    });
                });
            },
            syncDescriptionFromWidget() {
                if (!this.quillInstance) return;

                const widget = currentWidget();
                const html = (widget && widget.props && widget.props.description) ? widget.props.description : '';

                if (this.currentDescriptionHtml !== html) {
                    this.currentDescriptionHtml = html;
                    this.quillInstance.root.innerHTML = html;
                }
            }
        }"
        x-init="initDescriptionQuill()"
        class="space-y-3 mt-0">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Badge</span>
                <input type="text" x-model="currentWidget().props.badge" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300">
            </label>
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Titre</span>
                <input type="text" x-model="currentWidget().props.title" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300">
            </label>
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Type de titre</span>
                <select x-model="currentWidget().props.titleTag" @change="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300">
                    <option value="h1">H1</option>
                    <option value="h2">H2</option>
                    <option value="h3">H3</option>
                    <option value="h4">H4</option>
                    <option value="h5">H5</option>
                    <option value="h6">H6</option>
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Sous-titre</span>
                <input type="text" x-model="currentWidget().props.subtitle" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300">
            </label>
            <div class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Description</span>
                <div x-ref="heroDescriptionEditor" class="bg-white"></div>
            </div>

            <div class="border-t border-neutral-100 pt-2">
                <span class="text-xs font-semibold text-neutral-700">CTA Principal</span>
                <label class="block mt-1.5">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">Texte</span>
                    <input type="text" x-model="currentWidget().props.primaryCta.text"
                        @input="sync()"
                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300">
                </label>
                <label class="block mt-1.5">
                    <span class="text-xs font-medium text-neutral-700 block mb-1.5">URL</span>
                    <input type="text" x-model="currentWidget().props.primaryCta.href"
                        @input="sync()"
                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300">
                </label>
            </div>

            <div class="border-t border-neutral-100 pt-2 space-y-2.5">
                <span class="text-xs font-semibold text-neutral-700">Image bloc de droite</span>

                <template x-if="currentWidget().props.image">
                    <div class="mb-2 relative group rounded-lg overflow-hidden border border-neutral-200">
                        <img :src="currentWidget().props.image" alt="Preview" class="w-full h-32 object-cover">
                        <button
                            @click="currentWidget().props.image = ''; sync();"
                            class="absolute top-1.5 right-1.5 p-1 bg-white/90 backdrop-blur text-red-500 rounded-md opacity-0 group-hover:opacity-100 transition hover:bg-white"
                            type="button"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>

                <template x-if="!currentWidget().props.image">
                    <div class="h-24 rounded-lg border border-dashed border-neutral-300 bg-neutral-50"></div>
                </template>

                <div class="grid grid-cols-2 gap-2">
                    <button
                        @click="window.dispatchEvent(new CustomEvent('open-media-picker', { detail: { name: 'media-picker', callback: (image) => { currentWidget().props.image = image.url; sync(); } } }))"
                        class="px-3 py-2 text-xs font-medium bg-[#111827] text-white rounded-lg hover:bg-black transition"
                        type="button"
                    >
                        Galerie
                    </button>
                    <button
                        @click="$refs.heroBannerUpload.click()"
                        class="px-3 py-2 text-xs font-medium border border-neutral-200 text-neutral-700 rounded-lg hover:bg-neutral-50 transition"
                        type="button"
                    >
                        Upload
                    </button>
                </div>

                <input
                    type="file"
                    x-ref="heroBannerUpload"
                    @change="uploadImageDirect($event, 'image')"
                    accept="image/*"
                    class="hidden"
                >

                <label class="block">
                    <span class="text-xs font-medium text-neutral-600 block mb-1">URL de l'image</span>
                    <input type="text" x-model="currentWidget().props.image" @input="sync()"
                        class="w-full px-2.5 py-1.5 bg-white border border-neutral-200 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300"
                        placeholder="https://...">
                </label>
            </div>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
