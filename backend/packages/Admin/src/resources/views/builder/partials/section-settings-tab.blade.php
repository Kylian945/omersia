{{-- Shared settings tab content for sections --}}
{{-- Include this in the settings tab of sections --}}

<div>
    <span class="text-xs font-semibold text-neutral-700 block mb-2">Visibilité responsive</span>
    <div class="space-y-2 px-2">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox"
                x-model="currentSection().visibility.desktop"
                @change="sync()"
                class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900 focus:ring-offset-0">
            <span class="flex items-center gap-1.5 text-xs text-neutral-700">
                <x-lucide-monitor class="w-3.5 h-3.5" />
                <span>Desktop</span>
            </span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox"
                x-model="currentSection().visibility.tablet"
                @change="sync()"
                class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900 focus:ring-offset-0">
            <span class="flex items-center gap-1.5 text-xs text-neutral-700">
                <x-lucide-tablet class="w-3.5 h-3.5" />
                <span>Tablette</span>
            </span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox"
                x-model="currentSection().visibility.mobile"
                @change="sync()"
                class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900 focus:ring-offset-0">
            <span class="flex items-center gap-1.5 text-xs text-neutral-700">
                <x-lucide-smartphone class="w-3.5 h-3.5" />
                <span>Mobile</span>
            </span>
        </label>
    </div>
</div>

<div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5">
    <p class="text-xxs font-medium text-blue-900 mb-1">💡 Astuce</p>
    <p class="text-xxs text-blue-700">Décochez un appareil pour masquer la section sur cette taille d'écran.</p>
</div>

{{-- Espacement Section --}}
<div class="border border-neutral-200 rounded-lg p-3 mt-3 space-y-0">
    <div class="text-xs font-semibold text-neutral-700 mb-3 flex items-center gap-1.5">
        <x-lucide-square-dashed class="w-3.5 h-3.5" />
        Espacement
    </div>

    @include('admin::builder.partials.spacing-control', [
        'label' => 'Padding intérieur',
        'type' => 'padding',
        'model' => 'currentSection().settings.padding'
    ])

    @include('admin::builder.partials.spacing-control', [
        'label' => 'Margin extérieur',
        'type' => 'margin',
        'model' => 'currentSection().settings.margin'
    ])
</div>
