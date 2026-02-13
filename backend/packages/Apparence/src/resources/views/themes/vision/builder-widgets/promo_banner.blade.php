{{-- Widget: Promo Banner --}}
<template x-if="currentWidget().type === 'promo_banner'" x-init="initPromoBannerProps(); sync()">
    <div x-data="{
            activeWidgetTab: 'content',
            quillInstance: null,
            currentDescriptionHtml: '',
            initDescriptionQuill() {
                this.$nextTick(() => {
                    if (!this.$refs.promoDescriptionEditor || this.quillInstance) return;

                    const Quill = window.Quill;
                    if (!Quill) return;

                    this.quillInstance = new Quill(this.$refs.promoDescriptionEditor, {
                        theme: 'snow',
                        modules: {
                            toolbar: [
                                [{ 'header': [2, 3, 4, false] }],
                                ['bold', 'italic', 'underline'],
                                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                                ['link'],
                                ['clean']
                            ]
                        },
                        placeholder: 'Description de la bannière promo...',
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
        :key="'promo-banner-widget-' + selected.widgetId"
        class="space-y-3 mt-0">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Badge</span>
                <input type="text" x-model="currentWidget().props.badge" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
            </label>
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Titre</span>
                <input type="text" x-model="currentWidget().props.title" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
            </label>
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Type de titre</span>
                <select x-model="currentWidget().props.titleTag" @change="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
                    <option value="h1">H1</option>
                    <option value="h2">H2</option>
                    <option value="h3">H3</option>
                    <option value="h4">H4</option>
                    <option value="h5">H5</option>
                    <option value="h6">H6</option>
                </select>
            </label>
            <div class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Description</span>
                <div x-ref="promoDescriptionEditor" class="bg-white"></div>
            </div>
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Texte CTA</span>
                <input type="text" x-model="currentWidget().props.ctaText" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
            </label>
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">URL CTA</span>
                <input type="text" x-model="currentWidget().props.ctaHref" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
            </label>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
