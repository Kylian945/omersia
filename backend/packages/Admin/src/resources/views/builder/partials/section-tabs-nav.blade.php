{{-- Shared tabs navigation for sections --}}

<div class="flex border-b border-neutral-200">
    <button type="button"
        @click="activeSectionTab = 'content'"
        class="px-3 py-2 text-xs font-medium transition-colors border-b-2"
        :class="activeSectionTab === 'content' ? 'text-neutral-900 border-neutral-900' : 'text-neutral-500 border-transparent hover:text-neutral-700'">
        Contenu
    </button>
    <button type="button"
        @click="activeSectionTab = 'settings'"
        class="px-3 py-2 text-xs font-medium transition-colors border-b-2"
        :class="activeSectionTab === 'settings' ? 'text-neutral-900 border-neutral-900' : 'text-neutral-500 border-transparent hover:text-neutral-700'">
        Réglages
    </button>
</div>
