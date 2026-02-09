@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
        <label class="text-xs font-medium text-slate-700">Surnom</label>
        <input type="text" name="label" value="{{ old('label', $address->label) }}"
               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
    </div>

    <div>
        <label class="text-xs font-medium text-slate-700">Société</label>
        <input type="text" name="company" value="{{ old('company', $address->company) }}"
               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
    </div>

    <div>
        <label class="text-xs font-medium text-slate-700">Prénom</label>
        <input type="text" name="first_name" value="{{ old('first_name', $address->first_name) }}"
               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
    </div>

    <div>
        <label class="text-xs font-medium text-slate-700">Nom</label>
        <input type="text" name="last_name" value="{{ old('last_name', $address->last_name) }}"
               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
    </div>

    <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-700">Adresse</label>
        <input type="text" name="line1" value="{{ old('line1', $address->line1) }}"
               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs mb-1">
        <input type="text" name="line2" value="{{ old('line2', $address->line2) }}"
               class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs" placeholder="Complément">
    </div>

    <div>
        <label class="text-xs font-medium text-slate-700">Code postal</label>
        <input type="text" name="postcode" value="{{ old('postcode', $address->postcode) }}"
               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
    </div>

    <div>
        <label class="text-xs font-medium text-slate-700">Ville</label>
        <input type="text" name="city" value="{{ old('city', $address->city) }}"
               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
    </div>

    <div>
        <label class="text-xs font-medium text-slate-700">Région / État</label>
        <input type="text" name="state" value="{{ old('state', $address->state) }}"
               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
    </div>

    <div>
        <label class="text-xs font-medium text-slate-700">Pays</label>
        <input type="text" name="country" value="{{ old('country', $address->country ?? 'FR') }}"
               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
    </div>

    <div>
        <label class="text-xs font-medium text-slate-700">Téléphone</label>
        <input type="text" name="phone" value="{{ old('phone', $address->phone) }}"
               class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs">
    </div>

    <div class="md:col-span-2 flex gap-4 mt-2">
        <label class="inline-flex items-center gap-1 text-xs text-slate-700">
            <input type="checkbox" name="is_default_shipping" value="1"
                   @checked(old('is_default_shipping', $address->is_default_shipping ?? false))
                   class="rounded border-slate-300">
            <span>Adresse de livraison par défaut</span>
        </label>

        <label class="inline-flex items-center gap-1 text-xs text-slate-700">
            <input type="checkbox" name="is_default_billing" value="1"
                   @checked(old('is_default_billing', $address->is_default_billing ?? false))
                   class="rounded border-slate-300">
            <span>Adresse de facturation par défaut</span>
        </label>
    </div>
</div>



<div class="mt-4 flex justify-end gap-2">
    <a href="{{ route('admin.customers.show', $customer) }}"
       class="text-xs text-slate-500 hover:text-slate-800 px-4 py-1.5 border border-gray-300 rounded-lg">
        Annuler
    </a>
    <button type="submit"
        class="bg-black rounded-lg px-4 py-1.5 text-xs text-white hover:bg-neutral-800 shadow-sm border border-black font-semibold">
        Enregistrer
    </button>
</div>
