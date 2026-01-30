{{--
    Reusable Spacing Control Component (Simplified)

    Usage:
    @include('admin::builder.partials.spacing-control', [
        'label' => 'Padding',
        'type' => 'padding',
        'model' => 'currentSection().settings.padding'
    ])

    Stores data as: { all: 'md' } or { top: 'xs', right: 'sm', bottom: 'md', left: 'lg' }
--}}

@php
    $type = $type ?? 'padding';
    $label = $label ?? ucfirst($type);
    $model = $model ?? '';
    $uniqueId = 'spacing_' . $type . '_' . uniqid();
@endphp

<div class="spacing-control border-t border-neutral-100 pt-3 mt-3" x-data="{
    {{ $uniqueId }}_linked: true,
    get spacingObj() {
        const obj = {{ $model }} || {};
        return typeof obj === 'object' ? obj : {};
    },
    set spacingObj(val) {
        {{ $model }} = val;
    },
    init() {
        // Initialize linked state based on existing data
        const obj = this.spacingObj;
        if (obj.all !== undefined) {
            // If 'all' is defined, it's linked
            this.{{ $uniqueId }}_linked = true;
        } else if (obj.top !== undefined || obj.right !== undefined || obj.bottom !== undefined || obj.left !== undefined) {
            // If individual sides are defined, it's unlinked
            this.{{ $uniqueId }}_linked = false;
        } else {
            // Default to linked
            this.{{ $uniqueId }}_linked = true;
        }
    },
    updateAll(value) {
        this.spacingObj = { all: value };
        sync();
    },
    updateSide(side, value) {
        const obj = { ...this.spacingObj };
        delete obj.all; // Remove 'all' when using individual sides
        obj[side] = value;
        this.spacingObj = obj;
        sync();
    }
}">
    {{-- Header with toggle --}}
    <div class="flex items-center justify-between mb-2">
        <label class="text-xs font-medium text-neutral-700 flex items-center gap-1.5">
            @if($type === 'padding')
                <x-lucide-square-dashed class="w-3.5 h-3.5" />
            @else
                <x-lucide-maximize-2 class="w-3.5 h-3.5" />
            @endif
            {{ $label }}
        </label>

        <label class="flex items-center gap-1.5 cursor-pointer">
            <input
                type="checkbox"
                x-model="{{ $uniqueId }}_linked"
                class="w-3 h-3 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-500"
            >
            <span class="text-xs text-neutral-600">Lier tous les côtés</span>
        </label>
    </div>

    {{-- Unified control (when linked) --}}
    <div x-show="{{ $uniqueId }}_linked">
        <select
            :value="spacingObj.all || ''"
            @change="updateAll($event.target.value)"
            class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-lg text-xs focus:ring-2 focus:ring-neutral-900 focus:border-neutral-900"
        >
            <option value="">Aucun (0px)</option>
            <option value="xs">Très petit (4px)</option>
            <option value="sm">Petit (8px)</option>
            <option value="md">Moyen (16px)</option>
            <option value="lg">Grand (24px)</option>
            <option value="xl">Très grand (32px)</option>
            <option value="2xl">Extra grand (48px)</option>
        </select>
    </div>

    {{-- Individual controls (when unlinked) --}}
    <div x-show="!{{ $uniqueId }}_linked" class="space-y-2">
        <div class="grid grid-cols-2 gap-2">
            {{-- Top --}}
            <div>
                <label class="text-xs text-neutral-600 mb-1 block">Haut</label>
                <select
                    :value="spacingObj.top || ''"
                    @change="updateSide('top', $event.target.value)"
                    class="w-full px-2 py-1.5 bg-white border border-neutral-200 rounded text-xs focus:ring-1 focus:ring-neutral-900 focus:border-neutral-900"
                >
                    <option value="">0px</option>
                    <option value="xs">4px</option>
                    <option value="sm">8px</option>
                    <option value="md">16px</option>
                    <option value="lg">24px</option>
                    <option value="xl">32px</option>
                    <option value="2xl">48px</option>
                </select>
            </div>

            {{-- Right --}}
            <div>
                <label class="text-xs text-neutral-600 mb-1 block">Droite</label>
                <select
                    :value="spacingObj.right || ''"
                    @change="updateSide('right', $event.target.value)"
                    class="w-full px-2 py-1.5 bg-white border border-neutral-200 rounded text-xs focus:ring-1 focus:ring-neutral-900 focus:border-neutral-900"
                >
                    <option value="">0px</option>
                    <option value="xs">4px</option>
                    <option value="sm">8px</option>
                    <option value="md">16px</option>
                    <option value="lg">24px</option>
                    <option value="xl">32px</option>
                    <option value="2xl">48px</option>
                </select>
            </div>

            {{-- Bottom --}}
            <div>
                <label class="text-xs text-neutral-600 mb-1 block">Bas</label>
                <select
                    :value="spacingObj.bottom || ''"
                    @change="updateSide('bottom', $event.target.value)"
                    class="w-full px-2 py-1.5 bg-white border border-neutral-200 rounded text-xs focus:ring-1 focus:ring-neutral-900 focus:border-neutral-900"
                >
                    <option value="">0px</option>
                    <option value="xs">4px</option>
                    <option value="sm">8px</option>
                    <option value="md">16px</option>
                    <option value="lg">24px</option>
                    <option value="xl">32px</option>
                    <option value="2xl">48px</option>
                </select>
            </div>

            {{-- Left --}}
            <div>
                <label class="text-xs text-neutral-600 mb-1 block">Gauche</label>
                <select
                    :value="spacingObj.left || ''"
                    @change="updateSide('left', $event.target.value)"
                    class="w-full px-2 py-1.5 bg-white border border-neutral-200 rounded text-xs focus:ring-1 focus:ring-neutral-900 focus:border-neutral-900"
                >
                    <option value="">0px</option>
                    <option value="xs">4px</option>
                    <option value="sm">8px</option>
                    <option value="md">16px</option>
                    <option value="lg">24px</option>
                    <option value="xl">32px</option>
                    <option value="2xl">48px</option>
                </select>
            </div>
        </div>
    </div>
</div>
