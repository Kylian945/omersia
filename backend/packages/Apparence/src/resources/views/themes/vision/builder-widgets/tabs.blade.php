{{-- Widget: Tabs --}}
<template x-if="currentWidget().type === 'tabs'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0" x-init="initTabsProps()" :key="'tabs-widget-' + selected.widgetId">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-neutral-700">Onglets</span>
            <button type="button" @click="addTab()"
                class="px-3 py-1.5 text-xs bg-neutral-900 text-white rounded-lg hover:bg-neutral-800">
                + Ajouter
            </button>
        </div>

        <template x-for="(item, index) in currentWidget().props.items" :key="index">
            <div class="p-3 border border-neutral-200 rounded-lg bg-neutral-50 space-y-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-neutral-700">
                        Onglet #<span x-text="index + 1"></span>
                    </span>
                    <button type="button" @click="removeTab(index)"
                        class="text-xs text-neutral-400 hover:text-neutral-700">
                        <x-lucide-trash class="w-3 h-3" />
                    </button>
                </div>

                <label class="block">
                    <span class="text-xs text-neutral-600 block mb-1.5">Titre de l'onglet</span>
                    <input type="text" x-model="currentWidget().props.items[index].title" @input="sync()"
                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400">
                </label>

                <div x-data="{
                    quillInstance: null,
                    currentIndex: index,
                    initQuillForTab() {
                        this.$nextTick(() => {
                            const editorEl = this.$refs.quillEditor;
                            if (editorEl) {
                                const Quill = window.Quill;
                                if (!Quill) return;

                                const toolbarOptions = [
                                    [{ 'header': [3, 4, 5, 6, false] }],
                                    ['bold', 'italic', 'underline'],
                                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                    ['link'],
                                    ['clean']
                                ];

                                this.quillInstance = new Quill(editorEl, {
                                    theme: 'snow',
                                    modules: {
                                        toolbar: toolbarOptions
                                    },
                                    placeholder: 'Contenu de l\'onglet...',
                                });

                                // Set initial content
                                const widget = currentWidget();
                                const content = widget.props.items[this.currentIndex].content || '';
                                this.quillInstance.root.innerHTML = content;

                                // Listen for changes
                                this.quillInstance.on('text-change', () => {
                                    const html = this.quillInstance.root.innerHTML;
                                    currentWidget().props.items[this.currentIndex].content = html;
                                    sync();
                                });
                            }
                        });
                    }
                }" x-init="initQuillForTab()">
                    <label class="block">
                        <span class="text-xs text-neutral-600 block mb-1.5">Contenu</span>
                        <div x-ref="quillEditor" class="bg-white"></div>
                    </label>
                </div>
            </div>
        </template>

        <div x-show="!currentWidget().props.items || currentWidget().props.items.length === 0"
            class="text-center py-4 text-xs text-neutral-400 bg-neutral-50 border border-neutral-200 rounded-lg">
            Aucun onglet. Cliquez sur "+ Ajouter" pour créer un onglet.
        </div>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
