@props([
    'name',                // identifiant unique: ex "create-menu"
    'title' => null,
    'description' => null,
    'size' => 'max-w-sm',  // ex: max-w-sm, max-w-md, max-w-lg
])

<div
    x-data="{ open: false }"
    x-on:open-modal.window="
        if ($event.detail && $event.detail.name === '{{ $name }}') {
            open = true
        }
    "
    x-on:close-modal.window="
        if (!$event.detail || $event.detail.name === '{{ $name }}') {
            open = false
        }
    "
>
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm"
            @click.self="open = false"
            @keydown.escape.window="open = false"
        >
            <div class="bg-white rounded-2xl shadow-xl border border-black/5 w-full {{ $size }} p-4" @click.stop>
                @if($title || $description)
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            @if($title)
                                <div class="text-xs font-semibold text-gray-900">
                                    {{ $title }}
                                </div>
                            @endif
                            @if($description)
                                <div class="text-xxxs text-gray-500">
                                    {{ $description }}
                                </div>
                            @endif
                        </div>
                        <button type="button"
                                class="text-xs text-gray-400 hover:text-gray-700"
                                @click="open = false">
                            ✕
                        </button>
                    </div>
                @endif

                <div class="mt-2 space-y-2">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </template>
</div>
