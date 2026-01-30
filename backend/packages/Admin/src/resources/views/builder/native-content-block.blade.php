{{-- Bloc de contenu natif non-modifiable pour le builder --}}
<div
    class="rounded-2xl border-2 border-dashed border-neutral-300 bg-gradient-to-br from-neutral-50 to-neutral-100 p-8 mb-4 relative overflow-hidden">
    {{-- Pattern background --}}
    <div class="absolute inset-0 backdrop-blur-sm opacity-40"
        style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0, 0.05) 10px, rgba(0,0,0, 0.05) 20px);">
    </div>


    <div class="relative text-center space-y-4">
        {{-- Lock Icon --}}
        <div
            class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white border border-neutral-200 shadow-sm">

            @if ($pageType === 'category')
                <x-lucide-folders class="w-8 h-8 text-neutral-400" />
            @elseif($pageType === 'product')
                <x-lucide-shopping-bag class="w-8 h-8 text-neutral-400" />
            @else
                <x-lucide-lock class="w-8 h-8 text-neutral-400" />
            @endif
        </div>

        {{-- Title --}}
        <div>
            <h3 class="text-lg font-semibold text-neutral-800 mb-1">
                @if ($pageType === 'category')
                    Grille de produits de la catégorie
                @elseif($pageType === 'product')
                    Grille de tous les produits
                @else
                    Contenu natif
                @endif
            </h3>
            <p class="text-sm text-neutral-500">
                Cette section est automatique et non-modifiable
            </p>
        </div>

        {{-- Description --}}
        <div class="max-w-md mx-auto space-y-2 text-xs text-neutral-600">
            <p class="bg-white/60 backdrop-blur-sm rounded-lg px-4 py-2 border border-neutral-200">
                @if ($pageType === 'category')
                    Affiche automatiquement les produits de la catégorie avec :
                @elseif($pageType === 'product')
                    Affiche automatiquement tous les produits avec :
                @endif
            </p>

            <div class="grid grid-cols-2 gap-2 text-left">
                <div class="bg-white/80 backdrop-blur-sm rounded-lg p-3 border border-neutral-200">
                    <div class="flex items-start gap-2">
                        <x-lucide-filter class="w-4 h-4 text-neutral-500 flex-shrink-0 mt-0.5" />
                        <div>
                            <div class="font-medium text-neutral-700">Filtres</div>
                            <div class="text-xxs text-neutral-500 mt-0.5">Prix, catégories, marques</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-lg p-3 border border-neutral-200">
                    <div class="flex items-start gap-2">
                        <x-lucide-arrow-up-down class="w-4 h-4 text-neutral-500 flex-shrink-0 mt-0.5" />
                        <div>
                            <div class="font-medium text-neutral-700">Tri</div>
                            <div class="text-xxs text-neutral-500 mt-0.5">Prix, popularité, date</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-lg p-3 border border-neutral-200">
                    <div class="flex items-start gap-2">
                        <x-lucide-grid class="w-4 h-4 text-neutral-500 flex-shrink-0 mt-0.5" />
                        <div>
                            <div class="font-medium text-neutral-700">Grille responsive</div>
                            <div class="text-xxs text-neutral-500 mt-0.5">2-4 colonnes adaptatives</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-lg p-3 border border-neutral-200">
                    <div class="flex items-start gap-2">
                        <x-lucide-chevrons-right class="w-4 h-4 text-neutral-500 flex-shrink-0 mt-0.5" />
                        <div>
                            <div class="font-medium text-neutral-700">Pagination</div>
                            <div class="text-xxs text-neutral-500 mt-0.5">Navigation entre pages</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info badge --}}
        <div
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-xs text-blue-700">
            <x-lucide-info class="w-3.5 h-3.5" />
            <span>Le contenu s'affichera automatiquement sur le storefront</span>
        </div>
    </div>
</div>
