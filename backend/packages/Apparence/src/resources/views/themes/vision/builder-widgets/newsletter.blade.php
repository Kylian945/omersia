{{-- Widget: Newsletter --}}
<template x-if="currentWidget().type === 'newsletter'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
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
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Placeholder</span>
                <input type="text" x-model="currentWidget().props.placeholder" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
            </label>
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Texte bouton</span>
                <input type="text" x-model="currentWidget().props.buttonText" @input="sync()"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs">
            </label>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
