{{-- Zones géographiques --}}
<div id="zones-section" class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold flex items-center gap-2 text-neutral-900">
                    <x-lucide-map class="w-4 h-4" />
                    Zones géographiques
                </h2>
                <p class="text-xxs text-neutral-500 mt-0.5">Définissez les zones de livraison et leurs tarifs</p>
            </div>
            @if($method->exists)
                <button onclick="openZoneModal()"
                    class="inline-flex items-center rounded-lg bg-neutral-900 text-white text-xs px-3 py-1.5 hover:bg-black transition-colors shadow-sm">
                    <x-lucide-plus class="w-3 h-3 mr-1" />
                    Créer une zone
                </button>
            @endif
        </div>

        <div class="p-6">
            @if(!$method->exists)
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral-100 mb-4">
                        <x-lucide-info class="w-8 h-8 text-neutral-400" />
                    </div>
                    <h3 class="text-sm font-medium text-neutral-900 mb-1">Enregistrez d'abord la méthode</h3>
                    <p class="text-xs text-neutral-500 mb-4 max-w-sm mx-auto">
                        Vous pourrez configurer les zones géographiques après avoir créé la méthode de livraison.
                    </p>
                </div>
            @elseif ($method->zones->isEmpty())
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral-100 mb-4">
                        <x-lucide-map class="w-8 h-8 text-neutral-400" />
                    </div>
                    <h3 class="text-sm font-medium text-neutral-900 mb-1">Aucune zone configurée</h3>
                    <p class="text-xs text-neutral-500 mb-4 max-w-sm mx-auto">
                        Créez des zones géographiques pour définir des tarifs de livraison différents selon les pays
                        et régions.
                    </p>
                    <button onclick="openZoneModal()"
                        class="inline-flex items-center rounded-lg bg-neutral-900 text-white text-xs px-4 py-2 hover:bg-black transition-colors shadow-sm">
                        <x-lucide-plus class="w-3 h-3 mr-1.5" />
                        Créer votre première zone
                    </button>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($method->zones as $zone)
                        <div
                            class="group p-4 rounded-xl border border-neutral-200 hover:border-neutral-300 hover:shadow-md transition-all bg-white">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                        <x-lucide-map-pin class="w-4 h-4 text-blue-600" />
                                    </div>
                                    <div>
                                        @if ($zone->is_active)
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-xxxs font-medium">
                                                Active
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-neutral-100 text-neutral-600 text-xxxs font-medium mt-0.5">
                                                Inactive
                                            </span>
                                        @endif
                                        <h4 class="text-xs font-semibold text-neutral-900">{{ $zone->name }}</h4>

                                    </div>
                                </div>
                                <button type="button"
                                    onclick="confirmDelete('zone', {{ $zone->id }}, '{{ $zone->name }}')"
                                    class="opacity-0 group-hover:opacity-100 text-gray-600 hover:text-gray-700 hover:bg-gray-50 p-2 rounded-lg transition-all">
                                    <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                </button>
                                <form id="delete-zone-{{ $zone->id }}" method="POST"
                                    action="{{ route('admin.settings.shipping_methods.zones.destroy', [$method, $zone]) }}"
                                    class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                            <div class="space-y-2">
                                @if ($zone->countries)
                                    <div class="flex items-start gap-2">
                                        <x-lucide-globe class="w-3 h-3 text-neutral-400 mt-0.5 flex-shrink-0" />
                                        <div class="flex-1">
                                            <p class="text-xxxs font-medium text-neutral-600 mb-0.5">Pays</p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($zone->countries as $country)
                                                    <span
                                                        class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-neutral-100 text-neutral-700 text-xxxs font-medium">
                                                        {{ $country }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <x-lucide-globe class="w-3 h-3 text-neutral-400" />
                                        <p class="text-xxxs text-neutral-500">Tous les pays</p>
                                    </div>
                                @endif
                                @if ($zone->postal_codes)
                                    <div class="flex items-start gap-2">
                                        <x-lucide-mail class="w-3 h-3 text-neutral-400 mt-0.5" />
                                        <div>
                                            <p class="text-xxxs font-medium text-neutral-600 mb-0.5">Codes postaux
                                            </p>
                                            <p class="text-xxxs text-neutral-500">{{ $zone->postal_codes }}</p>
                                        </div>
                                    </div>
                                @endif
                                @if ($zone->rates->isNotEmpty())
                                    <div class="flex items-center gap-2 pt-2 mt-2 border-t border-neutral-100">
                                        <x-lucide-tag class="w-3 h-3 text-neutral-400" />
                                        <p class="text-xxxs text-neutral-600">
                                            <span class="font-semibold">{{ $zone->rates->count() }}</span>
                                            tarif(s) configuré(s)
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Tarifs --}}
<div id="rates-section" class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold flex items-center gap-2 text-neutral-900">
                    <x-lucide-calculator class="w-4 h-4" />
                    Tarifs de livraison
                </h2>
                <p class="text-xxs text-neutral-500 mt-0.5">Configurez vos grilles tarifaires</p>
            </div>
            @if($method->exists)
                <button onclick="openRateModal()"
                    class="inline-flex items-center rounded-lg bg-neutral-900 text-white text-xs px-3 py-1.5 hover:bg-black transition-colors shadow-sm">
                    <x-lucide-plus class="w-3 h-3 mr-1" />
                    Ajouter un tarif
                </button>
            @endif
        </div>

        <div class="p-6">
            @if(!$method->exists)
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral-100 mb-4">
                        <x-lucide-info class="w-8 h-8 text-neutral-400" />
                    </div>
                    <h3 class="text-sm font-medium text-neutral-900 mb-1">Enregistrez d'abord la méthode</h3>
                    <p class="text-xs text-neutral-500 mb-4 max-w-sm mx-auto">
                        Vous pourrez configurer les tarifs personnalisés après avoir créé la méthode de livraison.
                    </p>
                </div>
            @else
                {{-- Liste des tarifs --}}
                @php
                    $rates = $method->rates()->with('shippingZone')->orderBy('priority', 'desc')->get();
                @endphp

                @if ($rates->isEmpty())
                <div class="text-center py-12">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral-100 mb-4">
                        <x-lucide-euro class="w-8 h-8 text-neutral-400" />
                    </div>
                    <h3 class="text-sm font-medium text-neutral-900 mb-1">Aucun tarif personnalisé</h3>
                    <p class="text-xs text-neutral-500 mb-1 max-w-sm mx-auto">
                        Le prix de base de <span
                            class="font-semibold">{{ number_format($method->price, 2, ',', ' ') }} €</span> sera
                        utilisé.
                    </p>
                    <p class="text-xs text-neutral-500 mb-4 max-w-sm mx-auto">
                        Créez des tarifs personnalisés pour affiner vos prix selon le poids ou la zone.
                    </p>
                    <button onclick="openRateModal()"
                        class="inline-flex items-center rounded-lg bg-neutral-900 text-white text-xs px-4 py-2 hover:bg-black transition-colors shadow-sm">
                        <x-lucide-plus class="w-3 h-3 mr-1.5" />
                        Créer votre premier tarif
                    </button>
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($rates as $rate)
                        <div
                            class="group p-4 rounded-xl border border-neutral-200 hover:border-neutral-300 hover:shadow-md transition-all bg-white">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                                                <x-lucide-euro class="w-4 h-4 text-emerald-600" />
                                            </div>
                                            <span
                                                class="text-base font-bold text-neutral-900">{{ number_format($rate->price, 2, ',', ' ') }}
                                                €</span>
                                        </div>
                                        @if ($rate->priority > 0)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-xxs font-medium">
                                                <x-lucide-arrow-up class="w-3 h-3 mr-1" />
                                                Priorité {{ $rate->priority }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-3 text-xxs">
                                        @if ($rate->shippingZone)
                                            <div class="flex items-center gap-1.5 text-neutral-600">
                                                <x-lucide-map-pin class="w-3 h-3 text-neutral-400" />
                                                <span class="font-medium">{{ $rate->shippingZone->name }}</span>
                                            </div>
                                        @endif
                                        @if ($rate->min_weight || $rate->max_weight)
                                            <div class="flex items-center gap-1.5 text-neutral-600">
                                                <x-lucide-weight class="w-3 h-3 text-neutral-400" />
                                                <span>
                                                    @if ($rate->min_weight && $rate->max_weight)
                                                        {{ number_format($rate->min_weight, 2) }} -
                                                        {{ number_format($rate->max_weight, 2) }} kg
                                                    @elseif($rate->min_weight)
                                                        À partir de {{ number_format($rate->min_weight, 2) }} kg
                                                    @else
                                                        Jusqu'à {{ number_format($rate->max_weight, 2) }} kg
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <button type="button"
                                    onclick="confirmDelete('rate', {{ $rate->id }}, '{{ number_format($rate->price, 2, ',', ' ') }} €')"
                                    class="opacity-0 group-hover:opacity-100 text-gray-600 hover:text-gray-700 hover:bg-gray-50 p-2 rounded-lg transition-all">
                                    <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                </button>
                                <form id="delete-rate-{{ $rate->id }}" method="POST"
                                    action="{{ route('admin.settings.shipping_methods.rates.destroy', [$method, $rate]) }}"
                                    class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
            @endif
        </div>
    </div>
</div>

@if($method->exists)
{{-- Modal Zone --}}
<div id="zoneModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-neutral-900">Nouvelle zone de livraison</h3>
            <button onclick="closeZoneModal()" class="text-neutral-400 hover:text-neutral-600 transition-colors">
                <x-lucide-x class="w-5 h-5" />
            </button>
        </div>
        <form method="POST" action="{{ route('admin.settings.shipping_methods.zones.store', $method) }}"
            class="p-6 space-y-4">
            @csrf
            <div>
                <label class="text-xs font-medium text-neutral-700 mb-1.5 block">Nom de la zone *</label>
                <input type="text" name="name" required
                    class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:border-transparent transition-all"
                    placeholder="Ex: France métropolitaine, Europe, DOM-TOM">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-neutral-700 mb-1.5 block">Pays (codes ISO) *</label>
                    <input type="text" name="countries_input"
                        class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:border-transparent transition-all"
                        placeholder="Ex: FR, BE, CH">
                    <p class="text-xxs text-neutral-500 mt-1">Séparez par des virgules ou utilisez * pour tous</p>
                </div>
                <div>
                    <label class="text-xs font-medium text-neutral-700 mb-1.5 block">Codes postaux (optionnel)</label>
                    <input type="text" name="postal_codes"
                        class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:border-transparent transition-all"
                        placeholder="Ex: 75*, 13000-13999">
                    <p class="text-xxs text-neutral-500 mt-1">Utilisez * pour des patterns (75* pour Paris)</p>
                </div>
            </div>
            <div class="flex items-center gap-2 p-3 rounded-lg bg-neutral-50">
                <input type="checkbox" name="is_active" value="1" checked
                    class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900">
                <label class="text-xs font-medium text-neutral-700">Zone active dès la création</label>
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-neutral-200">
                <button type="button" onclick="closeZoneModal()"
                    class="px-4 py-2 rounded-lg border border-neutral-300 text-xs font-medium text-neutral-600 hover:bg-neutral-50 transition-colors">
                    Annuler
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-semibold hover:bg-black transition-colors shadow-sm">
                    Créer la zone
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Tarif --}}
<div id="rateModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-neutral-900">Nouveau tarif</h3>
            <button onclick="closeRateModal()" class="text-neutral-400 hover:text-neutral-600 transition-colors">
                <x-lucide-x class="w-5 h-5" />
            </button>
        </div>
        <form method="POST" action="{{ route('admin.settings.shipping_methods.rates.store', $method) }}"
            class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                @if ($method->use_zone_based_pricing && $method->zones->isNotEmpty())
                    <div>
                        <label class="text-xs font-medium text-neutral-700 mb-1.5 flex items-center gap-1">
                            <x-lucide-map-pin class="w-3 h-3" />
                            Zone géographique
                        </label>
                        <select name="shipping_zone_id"
                            class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:border-transparent transition-all">
                            <option value="">Toutes les zones</option>
                            @foreach ($method->zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="text-xs font-medium text-neutral-700 mb-1.5 flex items-center gap-1">
                        <x-lucide-euro class="w-3 h-3" />
                        Prix (€) *
                    </label>
                    <input type="number" step="0.01" min="0" name="price" required
                        class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:border-transparent transition-all"
                        placeholder="0.00">
                </div>
            </div>
            @if ($method->use_weight_based_pricing)
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-neutral-700 mb-1.5 block">Poids min (kg)</label>
                        <input type="number" step="0.01" min="0" name="min_weight"
                            class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:border-transparent transition-all"
                            placeholder="0.00">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-neutral-700 mb-1.5 block">Poids max (kg)</label>
                        <input type="number" step="0.01" min="0" name="max_weight"
                            class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:border-transparent transition-all"
                            placeholder="10.00">
                    </div>
                </div>
            @endif
            <div class="p-4 rounded-lg bg-blue-50 border border-blue-100">
                <label class="text-xs font-medium text-neutral-700 mb-1.5 flex items-center gap-1">
                    <x-lucide-arrow-up class="w-3 h-3" />
                    Priorité
                </label>
                <input type="number" min="0" name="priority" value="0"
                    class="w-32 rounded-lg border border-blue-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                <p class="text-xxs text-neutral-600 mt-1.5">Une priorité plus élevée sera appliquée en premier</p>
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-neutral-200">
                <button type="button" onclick="closeRateModal()"
                    class="px-4 py-2 rounded-lg border border-neutral-300 text-xs font-medium text-neutral-600 hover:bg-neutral-50 transition-colors">
                    Annuler
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-semibold hover:bg-black transition-colors shadow-sm">
                    Créer le tarif
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Confirmation --}}
<div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <x-lucide-alert-triangle class="w-6 h-6 text-red-600" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-neutral-900 mb-1">Confirmer la suppression</h3>
                    <p class="text-sm text-neutral-600" id="confirmMessage"></p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 bg-neutral-50 rounded-b-2xl flex justify-end gap-2">
            <button type="button" onclick="closeConfirmModal()"
                class="px-4 py-2 rounded-lg border border-neutral-300 text-xs font-medium text-neutral-600 hover:bg-white transition-colors">
                Annuler
            </button>
            <button type="button" onclick="submitDelete()"
                class="px-4 py-2 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 transition-colors">
                Supprimer
            </button>
        </div>
    </div>
</div>
@endif
