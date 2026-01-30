{{-- Widget: Hero Banner --}}
<template x-if="currentWidget().type === 'hero_banner'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0">
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
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Sous-titre</span>
                <input type="text" x-model="currentWidget().props.subtitle" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300">
            </label>
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Description</span>
                <textarea x-model="currentWidget().props.description" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300 h-16 resize-none"></textarea>
            </label>

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
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
