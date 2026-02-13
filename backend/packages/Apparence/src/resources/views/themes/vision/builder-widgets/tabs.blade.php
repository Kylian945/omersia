{{-- Widget: Tabs --}}
<template x-if="currentWidget().type === 'tabs'">
    <div x-data="{ activeWidgetTab: 'content', openTabs: {} }" class="space-y-3 mt-0" x-init="initTabsProps()" :key="'tabs-widget-' + selected.widgetId">
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
                <div class="border border-neutral-200 rounded-lg bg-neutral-50">
                    <div class="flex items-center justify-between px-3 py-2">
                        <button type="button"
                            @click="openTabs[index] = !openTabs[index]"
                            class="flex-1 min-w-0 flex items-center gap-2 text-left">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded border border-neutral-200 bg-white text-[11px] font-semibold text-neutral-600 shrink-0"
                                x-text="index + 1"></span>

                            <div class="min-w-0">
                                <div class="text-xs font-medium text-neutral-800 truncate">
                                    <span x-text="currentWidget().props.items[index].title || `Onglet #${index + 1}`"></span>
                                </div>
                                <div class="text-[11px] text-neutral-500">Configurer le titre et le contenu</div>
                            </div>
                        </button>

                        <div class="ml-2 flex items-center gap-1 shrink-0">
                            <button type="button"
                                @click="openTabs[index] = !openTabs[index]"
                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-neutral-200 bg-white text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-700"
                                title="Ouvrir/Fermer">
                                <x-lucide-chevron-down
                                    class="w-3.5 h-3.5 transition-transform"
                                    x-bind:class="openTabs[index] ? 'rotate-180' : ''"
                                />
                            </button>

                            <button type="button" @click="removeTab(index)"
                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-neutral-200 bg-white text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700"
                                title="Supprimer">
                                <x-lucide-trash class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <template x-if="openTabs[index]">
                        <div class="px-3 pb-3 space-y-3">
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
                                        if (!editorEl) return;

                                        const Quill = window.Quill;
                                        if (!Quill) return;

                                        const toolbarOptions = [
                                            [{ 'header': [3, 4, 5, 6, false] }],
                                            ['bold', 'italic', 'underline'],
                                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                                            ['link'],
                                            ['clean']
                                        ];

                                        this.quillInstance = new Quill(editorEl, {
                                            theme: 'snow',
                                            modules: { toolbar: toolbarOptions },
                                            placeholder: 'Contenu de l\'onglet...',
                                        });

                                        const widget = currentWidget();
                                        const content = widget?.props?.items?.[this.currentIndex]?.content || '';
                                        this.quillInstance.root.innerHTML = content;

                                        this.quillInstance.on('text-change', () => {
                                            const html = this.quillInstance.root.innerHTML;
                                            if (currentWidget()?.props?.items?.[this.currentIndex]) {
                                                currentWidget().props.items[this.currentIndex].content = html;
                                                sync();
                                            }
                                        });
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
