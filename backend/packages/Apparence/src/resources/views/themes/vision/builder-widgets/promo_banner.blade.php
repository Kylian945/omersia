{{-- Widget: Promo Banner --}}
<template x-if="currentWidget().type === 'promo_banner'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0">
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
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Description</span>
                <textarea x-model="currentWidget().props.description" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs h-16 resize-none"></textarea>
            </label>
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
