{{-- Widget: Button --}}
<template x-if="currentWidget().type === 'button'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Label</span>
                <input type="text" x-model="currentWidget().props.label" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300">
            </label>
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">URL</span>
                <input type="text" x-model="currentWidget().props.url" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300">
            </label>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
