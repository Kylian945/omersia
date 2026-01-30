<div class="space-y-4">
    {{-- Tax Zone Info --}}
    <div class="rounded-lg bg-neutral-50 border border-neutral-200 px-4 py-3">
        <div class="flex items-center gap-2 text-xs">
            <x-lucide-map-pin class="w-4 h-4 text-neutral-500" />
            <span class="font-medium text-neutral-700">Zone:</span>
            <span class="text-neutral-900">{{ $taxZone->name }}</span>
            <code class="text-xxs bg-white px-2 py-0.5 rounded border border-neutral-200">{{ $taxZone->code }}</code>
        </div>
    </div>

    {{-- Name --}}
    <div>
        <label for="name" class="block text-xs font-medium text-neutral-700 mb-1">
            Nom du taux <span class="text-red-500">*</span>
        </label>
        <input type="text"
               id="name"
               name="name"
               value="{{ old('name', $taxRate->name ?? '') }}"
               class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black/20"
               placeholder="ex: TVA Standard, TVA Réduite, Sales Tax"
               required>
        @error('name')
            <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Type --}}
    <div>
        <label for="type" class="block text-xs font-medium text-neutral-700 mb-1">
            Type de taxe <span class="text-red-500">*</span>
        </label>
        <select id="type"
                name="type"
                class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black/20"
                required>
            <option value="percentage" {{ old('type', $taxRate->type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>
                Pourcentage
            </option>
            <option value="fixed" {{ old('type', $taxRate->type ?? '') === 'fixed' ? 'selected' : '' }}>
                Montant fixe
            </option>
        </select>
        @error('type')
            <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Rate --}}
    <div>
        <label for="rate" class="block text-xs font-medium text-neutral-700 mb-1">
            Taux <span class="text-red-500">*</span>
        </label>
        <div class="relative">
            <input type="number"
                   id="rate"
                   name="rate"
                   value="{{ old('rate', $taxRate->rate ?? '') }}"
                   step="0.01"
                   min="0"
                   class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black/20"
                   placeholder="20.00"
                   required>
            <span id="rate-unit" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-neutral-500">
                %
            </span>
        </div>
        <p class="mt-1 text-xxs text-neutral-500">Pour un pourcentage, entrez le taux (ex: 20 pour 20%). Pour un montant fixe, entrez le montant en euros.</p>
        @error('rate')
            <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Priority --}}
    <div>
        <label for="priority" class="block text-xs font-medium text-neutral-700 mb-1">
            Priorité <span class="text-red-500">*</span>
        </label>
        <input type="number"
               id="priority"
               name="priority"
               value="{{ old('priority', $taxRate->priority ?? 0) }}"
               class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black/20"
               min="0"
               required>
        <p class="mt-1 text-xxs text-neutral-500">Ordre d'application (0 = en premier)</p>
        @error('priority')
            <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Options --}}
    <div class="space-y-2">
        <div class="flex items-start">
            <input type="checkbox"
                   id="shipping_taxable"
                   name="shipping_taxable"
                   value="1"
                   {{ old('shipping_taxable', $taxRate->shipping_taxable ?? true) ? 'checked' : '' }}
                   class="mt-0.5 rounded border-neutral-300 text-black focus:ring-black/20">
            <label for="shipping_taxable" class="ml-2 text-xs text-neutral-700">
                <div class="font-medium">Appliquer aux frais de port</div>
                <div class="text-xxs text-neutral-500 mt-0.5">Cette taxe s'applique également aux frais de livraison</div>
            </label>
        </div>

        <div class="flex items-start">
            <input type="checkbox"
                   id="compound"
                   name="compound"
                   value="1"
                   {{ old('compound', $taxRate->compound ?? false) ? 'checked' : '' }}
                   class="mt-0.5 rounded border-neutral-300 text-black focus:ring-black/20">
            <label for="compound" class="ml-2 text-xs text-neutral-700">
                <div class="font-medium">Taxe composée</div>
                <div class="text-xxs text-neutral-500 mt-0.5">Calculée sur le prix + les autres taxes déjà appliquées (rare)</div>
            </label>
        </div>

        <div class="flex items-start">
            <input type="checkbox"
                   id="is_active"
                   name="is_active"
                   value="1"
                   {{ old('is_active', $taxRate->is_active ?? true) ? 'checked' : '' }}
                   class="mt-0.5 rounded border-neutral-300 text-black focus:ring-black/20">
            <label for="is_active" class="ml-2 text-xs text-neutral-700">
                <div class="font-medium">Taux actif</div>
            </label>
        </div>
    </div>
</div>
