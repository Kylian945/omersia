{{-- Shared tabs navigation for all widgets --}}
{{-- Include this at the top of each widget after the x-data div --}}

<div class="flex border-b border-neutral-200">
    <button type="button"
        @click="activeWidgetTab = 'content'"
        class="px-3 py-2 text-xs font-medium transition-colors border-b-2"
        :class="activeWidgetTab === 'content' ? 'text-neutral-900 border-neutral-900' : 'text-neutral-500 border-transparent hover:text-neutral-700'">
        Contenu
    </button>
    <button type="button"
        @click="activeWidgetTab = 'settings'"
        class="px-3 py-2 text-xs font-medium transition-colors border-b-2"
        :class="activeWidgetTab === 'settings' ? 'text-neutral-900 border-neutral-900' : 'text-neutral-500 border-transparent hover:text-neutral-700'">
        Réglages
    </button>
</div>
