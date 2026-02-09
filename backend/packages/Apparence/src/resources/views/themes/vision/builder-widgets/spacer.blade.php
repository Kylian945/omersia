{{-- Widget: Spacer --}}
<template x-if="currentWidget().type === 'spacer'">
    <div x-data="{ activeWidgetTab: 'content' }" class="space-y-3 mt-0">
        @include('admin::builder.partials.widget-tabs-nav')

        {{-- Content tab --}}
        <div x-show="activeWidgetTab === 'content'" class="space-y-3">
            <label class="block">
                <span class="text-xs font-medium text-neutral-700 block mb-1.5">Hauteur (px)</span>
                <input type="number"
                    x-model.number="currentWidget().props.size"
                    @input="sync()"
                    min="0"
                    max="500"
                    step="8"
                    class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900/5 focus:border-neutral-400 hover:border-neutral-300">
            </label>

            <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-3">
                <div class="text-xxs text-neutral-500 mb-2">Aperçu</div>
                <div class="bg-neutral-200 rounded"
                    :style="`height: ${currentWidget().props.size || 32}px`">
                </div>
                <div class="text-center text-xxs text-neutral-500 mt-1">
                    <span x-text="(currentWidget().props.size || 32) + 'px'"></span>
                </div>
            </div>

            <div class="space-y-1">
                <div class="text-xxs font-medium text-neutral-600 mb-2">Tailles rapides</div>
                <div class="grid grid-cols-4 gap-1">
                    <button type="button" @click="currentWidget().props.size = 16; sync()"
                        class="px-2 py-1.5 text-xxs rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700">
                        16px
                    </button>
                    <button type="button" @click="currentWidget().props.size = 32; sync()"
                        class="px-2 py-1.5 text-xxs rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700">
                        32px
                    </button>
                    <button type="button" @click="currentWidget().props.size = 48; sync()"
                        class="px-2 py-1.5 text-xxs rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700">
                        48px
                    </button>
                    <button type="button" @click="currentWidget().props.size = 64; sync()"
                        class="px-2 py-1.5 text-xxs rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700">
                        64px
                    </button>
                </div>
            </div>
        </div>

        {{-- Settings tab --}}
        <div x-show="activeWidgetTab === 'settings'" class="space-y-3">
            @include('admin::builder.partials.widget-settings-tab')
        </div>
    </div>
</template>
