<div class="space-y-4">
    {{-- Name --}}
    <div>
        <label for="name" class="block text-xs font-medium text-neutral-700 mb-1">
            Nom de la zone <span class="text-red-500">*</span>
        </label>
        <input type="text"
               id="name"
               name="name"
               value="{{ old('name', $taxZone->name ?? '') }}"
               class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black/20"
               placeholder="ex: France, Union Européenne"
               required>
        @error('name')
            <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Code --}}
    <div>
        <label for="code" class="block text-xs font-medium text-neutral-700 mb-1">
            Code de la zone <span class="text-red-500">*</span>
        </label>
        <input type="text"
               id="code"
               name="code"
               value="{{ old('code', $taxZone->code ?? '') }}"
               class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-black/20"
               placeholder="ex: FR, EU, US-CA"
               required>
        <p class="mt-1 text-xxs text-neutral-500">Code unique pour identifier la zone</p>
        @error('code')
            <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Description --}}
    <div>
        <label for="description" class="block text-xs font-medium text-neutral-700 mb-1">
            Description
        </label>
        <textarea id="description"
                  name="description"
                  rows="2"
                  class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black/20"
                  placeholder="Description de la zone">{{ old('description', $taxZone->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Countries --}}
    <div>
        <label for="countries" class="block text-xs font-medium text-neutral-700 mb-1">
            Pays (codes ISO)
        </label>
        <input type="text"
               id="countries"
               name="countries_input"
               value="{{ old('countries_input', isset($taxZone) && $taxZone->countries ? implode(', ', $taxZone->countries) : '') }}"
               class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black/20"
               placeholder="FR, BE, DE, CH">
        <p class="mt-1 text-xxs text-neutral-500">Codes pays séparés par des virgules (ex: FR, BE, DE)</p>
        @error('countries')
            <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Postal Codes --}}
    <div>
        <label for="postal_codes" class="block text-xs font-medium text-neutral-700 mb-1">
            Codes postaux (optionnel)
        </label>
        <input type="text"
               id="postal_codes"
               name="postal_codes_input"
               value="{{ old('postal_codes_input', isset($taxZone) && $taxZone->postal_codes ? implode(', ', $taxZone->postal_codes) : '') }}"
               class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-black/20"
               placeholder="75*, 69001, 13000">
        <p class="mt-1 text-xxs text-neutral-500">Codes postaux séparés par des virgules. Utilisez * comme joker (ex: 75*, 69001)</p>
        @error('postal_codes')
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
               value="{{ old('priority', $taxZone->priority ?? 0) }}"
               class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black/20"
               min="0"
               required>
        <p class="mt-1 text-xxs text-neutral-500">Ordre de priorité si plusieurs zones correspondent (0 = la plus haute)</p>
        @error('priority')
            <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Is Active --}}
    <div class="flex items-center">
        <input type="checkbox"
               id="is_active"
               name="is_active"
               value="1"
               {{ old('is_active', $taxZone->is_active ?? true) ? 'checked' : '' }}
               class="rounded border-neutral-300 text-black focus:ring-black/20">
        <label for="is_active" class="ml-2 text-xs text-neutral-700">
            Zone active
        </label>
    </div>
</div>
