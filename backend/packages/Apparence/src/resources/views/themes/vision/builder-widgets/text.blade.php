{{-- Widget: Text --}}
<template x-if="currentWidget().type === 'text'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0" :key="'text-widget-' + selected.widgetId">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" x-data="{
            quillInstance: null,
            currentHtml: '',
            initQuill() {
                this.$nextTick(() => {
                    if (this.$refs.quillEditor) {
                        const Quill = window.Quill;
                        if (!Quill) return;

                        const toolbarOptions = [
                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                            [{ 'font': [] }],
                            [{ 'size': ['small', false, 'large', 'huge'] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'script': 'sub'}, { 'script': 'super' }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'list': 'check' }],
                            [{ 'indent': '-1'}, { 'indent': '+1' }],
                            [{ 'align': [] }],
                            ['blockquote', 'code-block'],
                            ['link', 'image', 'video'],
                            ['clean']
                        ];

                        this.quillInstance = new Quill(this.$refs.quillEditor, {
                            theme: 'snow',
                            modules: {
                                toolbar: toolbarOptions
                            },
                            placeholder: 'Écrivez votre contenu...',
                        });

                        // Set initial content from the widget
                        this.updateContent();

                        // Listen for changes from the editor
                        this.quillInstance.on('text-change', () => {
                            const html = this.quillInstance.root.innerHTML;
                            this.currentHtml = html;
                            currentWidget().props.html = html;
                            sync();
                        });

                        // Watch for widget changes and update Quill content
                        this.$watch('selected.widgetId', () => {
                            this.updateContent();
                        });
                    }
                });
            },
            updateContent() {
                if (this.quillInstance) {
                    const widget = currentWidget();
                    const html = (widget && widget.props && widget.props.html) ? widget.props.html : '';

                    // Only update if content is different
                    if (this.currentHtml !== html) {
                        this.currentHtml = html;
                        this.quillInstance.root.innerHTML = html;
                    }
                }
            }
        }"
        x-init="initQuill()">
            <span class="text-xs font-medium text-neutral-700 block mb-1.5">Texte</span>
            <div x-ref="quillEditor" class="bg-white"></div>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
