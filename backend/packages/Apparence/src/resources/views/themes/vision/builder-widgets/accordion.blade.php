{{-- Widget: Accordion --}}
<template x-if="currentWidget().type === 'accordion'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
            <div class="text-xs text-neutral-500 bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                Configuration avancée - Modifiez les données dans le JSON si nécessaire.
            </div>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
