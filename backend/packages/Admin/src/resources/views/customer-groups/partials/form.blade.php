@php
    use Illuminate\Support\Str;

    /** @var \App\Models\CustomerGroup|null $g */
    $g = $group ?? null;

    $selectedCustomerIds = old(
        'customer_ids',
        $g?->customers?->pluck('id')->all() ?? []
    );

    // Prépare les clients sélectionnés pour Alpine (id + label)
    $selectedCustomersPayload = [];
    foreach ($customers as $customer) {
        if (in_array($customer->id, $selectedCustomerIds)) {
            $name = trim(($customer->lastname ?? '').' '.($customer->firstname ?? ''));
            $label = $name !== '' ? $name.' — '.$customer->email : $customer->email;

            $selectedCustomersPayload[] = [
                'id'    => $customer->id,
                'label' => $label,
            ];
        }
    }
@endphp

<div class="space-y-4"
     x-data="{
        customerModalOpen: false,
        customerSearch: '',
        selectedCustomers: @js($selectedCustomersPayload),

        addCustomer(customer) {
            if (!this.selectedCustomers.find(c => c.id === customer.id)) {
                this.selectedCustomers.push(customer);
            }
        },
        removeCustomer(id) {
            this.selectedCustomers = this.selectedCustomers.filter(c => c.id !== id);
        }
     }">

    {{-- Infos de base --}}
    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
        <h2 class="text-xs font-semibold text-neutral-700">Informations du groupe</h2>

        <div class="space-y-3">
            <div>
                <label class="text-xxs text-neutral-500">Nom du groupe</label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $g->name ?? '') }}"
                       class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs"
                       required>
                @error('name')
                    <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-xxs text-neutral-500">Description (optionnelle)</label>
                <textarea name="description"
                          class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs min-h-[80px]">{{ old('description', $g->description ?? '') }}</textarea>
                @error('description')
                    <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox"
                       class="rounded-md "
                       name="is_default"
                       value="1"
                       @checked(old('is_default', $g->is_default ?? false))>
                <span class="text-xxs text-neutral-700">
                    Définir comme groupe par défaut pour les nouveaux clients
                </span>
            </div>
        </div>
    </div>

    {{-- Clients liés --}}
    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
        <h2 class="text-xs font-semibold text-neutral-700">Clients du groupe</h2>

        <p class="text-xxs text-neutral-500">
            Les réductions ou campagnes ciblant ce groupe s’appliqueront uniquement aux clients sélectionnés.
        </p>

        {{-- Bouton + liste des clients sélectionnés --}}
        <div class="space-y-2">
            <div class="flex items-center justify-between border border-gray-100 p-2 rounded-xl">
                <label class="text-xxs text-neutral-500">Clients sélectionnés</label>
                <button type="button"
                        @click="customerModalOpen = true"
                        class="px-2 py-1 rounded-md border text-xxs">
                    Ajouter des clients
                </button>
            </div>

            <template x-if="selectedCustomers.length === 0">
                <p class="text-xxs text-neutral-400">
                    Aucun client sélectionné pour le moment.
                </p>
            </template>

            <div class="space-y-1" x-show="selectedCustomers.length > 0">
                <template x-for="customer in selectedCustomers" :key="customer.id">
                    <div class="flex items-center justify-between rounded-md border border-neutral-200 px-2 py-2">
                        <span class="text-xs" x-text="customer.label"></span>

                        <button type="button"
                                @click.prevent="removeCustomer(customer.id)"
                                class="text-xxs text-gray-500 bg-gray-50 border rounded-lg border-gray-100 hover:underline h-6 w-6 flex items-center justify-center">
                            <x-lucide-trash class="w-3 h-3" />
                        </button>

                        {{-- Input hidden pour l’ID --}}
                        <input type="hidden" name="customer_ids[]" :value="customer.id">
                    </div>
                </template>
            </div>

            @error('customer_ids')
                <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- MODAL CLIENTS --}}
    <div x-cloak x-show="customerModalOpen"
         class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="customerModalOpen = false"></div>

        <div @click.stop
             class="relative z-10 w-full max-w-3xl rounded-2xl bg-white shadow-xl border border-black/5 p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-xs font-semibold">Ajouter des clients</h3>
                    <p class="text-xxs text-neutral-500">
                        Recherchez et ajoutez un ou plusieurs clients à ce groupe.
                    </p>
                </div>
                <button type="button"
                        @click="customerModalOpen = false"
                        class="p-1 rounded-full hover:bg-neutral-100">
                    <x-lucide-x class="w-4 h-4 text-neutral-500" />
                </button>
            </div>

            <div>
                <input type="text"
                       x-model="customerSearch"
                       placeholder="Rechercher un client (nom, email)…"
                       class="w-full rounded-md border px-3 py-1.5 text-xs">
            </div>

            <div class="max-h-80 overflow-y-auto border border-neutral-100 rounded-xl">
                <table class="min-w-full text-xs">
                    <tbody>
                        @forelse($customers as $customer)
                            @php
                                $name = trim(($customer->lastname ?? '').' '.($customer->firstname ?? ''));
                                $label = $name !== '' ? $name.' — '.$customer->email : $customer->email;
                            @endphp
                            <tr class="border-b border-neutral-100"
                                x-show="customerSearch === '' || {{ json_encode(Str::lower($label)) }}.includes(customerSearch.toLowerCase())">
                                <td class="px-3 py-2 align-middle">
                                    <div class="font-medium text-xs">{{ $label }}</div>
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <button type="button"
                                            @click.prevent="addCustomer(@js(['id' => $customer->id, 'label' => $label]))"
                                            class="px-2 py-1 rounded-md border text-xxs">
                                        Ajouter
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-center text-xxs text-neutral-400">
                                    Aucun client disponible.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="button"
                        @click="customerModalOpen = false"
                        class="text-xxs text-neutral-500 hover:text-neutral-700">
                    Fermer
                </button>
            </div>
        </div>
    </div>

</div>
