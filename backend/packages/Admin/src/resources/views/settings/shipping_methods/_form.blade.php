<form class="w-full" method="POST" action="{{ $action }}">
    @csrf
    @if($method->exists)
        @method('PUT')
    @endif

    <div class="space-y-4">
        {{-- Code --}}
        <div>
            <label class="text-xs font-medium text-neutral-700 mb-1 block">
                Code technique (slug) *
            </label>
            <input
                type="text"
                name="code"
                value="{{ old('code', $method->code) }}"
                class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs"
            >
            <p class="text-xxs text-neutral-500 mt-1">
                Ex : <code>colissimo_48h</code>, <code>chronopost_24h</code>…
            </p>
            @error('code')
                <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Nom --}}
        <div>
            <label class="text-xs font-medium text-neutral-700 mb-1 block">
                Nom affiché *
            </label>
            <input
                type="text"
                name="name"
                value="{{ old('name', $method->name) }}"
                class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs"
            >
            @error('name')
                <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Description --}}
        <div>
            <label class="text-xs font-medium text-neutral-700 mb-1.5 block">Description</label>
            <textarea name="description" rows="2"
                class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:border-transparent transition-all"
                placeholder="Décrivez cette méthode de livraison...">{{ old('description', $method->description) }}</textarea>
            <p class="text-xxs text-neutral-500 mt-1">Cette description sera visible par vos clients lors du checkout</p>
            @error('description')
                <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Prix --}}
        <div>
            <label class="text-xs font-medium text-neutral-700 mb-1 block">
                Prix de base TTC *
            </label>
            <input
                type="number"
                step="0.01"
                min="0"
                name="price"
                value="{{ old('price', $method->price) }}"
                class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs"
            >
            <p class="text-xxs text-neutral-500 mt-1">Ce prix sera utilisé si aucun tarif personnalisé ne s'applique</p>
            @error('price')
                <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Délai --}}
        <div>
            <label class="text-xs font-medium text-neutral-700 mb-1 block">
                Délai indicatif
            </label>
            <input
                type="text"
                name="delivery_time"
                value="{{ old('delivery_time', $method->delivery_time) }}"
                class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs"
            >
            <p class="text-xxs text-neutral-500 mt-1">
                Exemple : "2 à 3 jours ouvrés", "24h", "3-5 jours"…
            </p>
            @error('delivery_time')
                <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Livraison gratuite --}}
        <div class="px-4 py-3 rounded-lg bg-neutral-50 border border-neutral-200">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <x-lucide-gift class="w-4 h-4 text-emerald-600" />
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-neutral-700 block">Livraison gratuite à partir de</label>
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.01" min="0" name="free_shipping_threshold"
                                value="{{ old('free_shipping_threshold', $method->free_shipping_threshold) }}"
                                class="w-32 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                                placeholder="0.00">
                            <span class="text-xs text-neutral-600">€</span>
                        </div>
                    </div>
                    <p class="text-xxs text-neutral-500 mt-1">Laissez vide pour désactiver</p>
                </div>
            </div>
        </div>

        {{-- Options de tarification --}}
        <div class="border-t border-neutral-100 pt-4">
            <h3 class="text-xs font-semibold text-neutral-900 mb-3">Options de tarification avancées</h3>

            <div class="space-y-3">
                <div class="p-4 rounded-lg border border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50 transition-all cursor-pointer">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="use_weight_based_pricing" value="0">
                        <input type="checkbox" id="use_weight_based_pricing" name="use_weight_based_pricing" value="1"
                            {{ old('use_weight_based_pricing', $method->use_weight_based_pricing) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <x-lucide-weight class="w-3.5 h-3.5 text-neutral-500" />
                                <span class="text-xs font-medium text-neutral-700">Tarification par poids</span>
                            </div>
                            <p class="text-xxs text-neutral-500">Définissez des tarifs différents selon le poids total de la commande</p>
                        </div>
                    </label>
                </div>

                <div class="p-4 rounded-lg border border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50 transition-all cursor-pointer">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="use_zone_based_pricing" value="0">
                        <input type="checkbox" id="use_zone_based_pricing" name="use_zone_based_pricing" value="1"
                            {{ old('use_zone_based_pricing', $method->use_zone_based_pricing) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <x-lucide-map-pin class="w-3.5 h-3.5 text-neutral-500" />
                                <span class="text-xs font-medium text-neutral-700">Tarification par zone géographique</span>
                            </div>
                            <p class="text-xxs text-neutral-500">Créez des zones de livraison avec des tarifs personnalisés par pays et code postal</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Actif --}}
        <div class="flex items-center gap-2 border-t border-neutral-100 pt-4">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $method->is_active))
                class="rounded border-neutral-300"
            >
            <span class="text-xs text-neutral-700">
                Méthode active (visible côté front)
            </span>
        </div>
        @error('is_active')
            <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-4 flex justify-end gap-2">
        <a href="{{ route('admin.settings.shipping_methods.index') }}"
           class="px-3 py-1.5 rounded-lg border border-neutral-200 text-xs text-neutral-600 hover:bg-neutral-50">
            Annuler
        </a>
        <button
            type="submit"
            class="px-4 py-1.5 rounded-lg bg-neutral-900 text-white text-xs font-semibold hover:bg-black"
        >
            {{ $submitLabel }}
        </button>
    </div>
</form>
