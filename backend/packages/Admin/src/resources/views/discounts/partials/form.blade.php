@php
    use Illuminate\Support\Str;

    /** @var \App\Models\Discount|null $d */
    $d = $discount ?? null;

    $initialType = $initialType ?? old('type', $d->type ?? 'order');
    $hasPresetType = $hasPresetType ?? false;

    $initialMethod = old('method', $d->method ?? 'code');

    $initialProductScope = old('product_scope', $d->product_scope ?? 'all'); // all|products|collections
    $initialCustomerScope = old('customer_selection', $d->customer_selection ?? 'all'); // all|groups|customers

    $selectedProductIds = old('product_ids', $d?->products?->pluck('id')->all() ?? []);
    $selectedCollectionIds = old('collection_ids', $d?->collections?->pluck('id')->all() ?? []);
    $selectedGroupIds = old('customer_group_ids', $d?->customerGroups?->pluck('id')->all() ?? []);
    $selectedCustomerIds = old('customer_ids', $d?->customers?->pluck('id')->all() ?? []);

    // PRODUITS
    $selectedProductsPayload = [];
    foreach ($products as $product) {
        $imageUrl = $product->mainImage?->url ?? asset('images/modules/no-icon.png');

        if (in_array($product->id, $selectedProductIds)) {
            $t = $product->translation('fr');
            $label = $t->name ?? '#' . $product->id;
            $selectedProductsPayload[] = [
                'id' => $product->id,
                'label' => $label,
                'imageUrl' => $imageUrl,
            ];
        }
    }

    // CATEGORIES
    $selectedCollectionsPayload = [];
    foreach ($collections as $category) {
        if (in_array($category->id, $selectedCollectionIds)) {
            $t = $category->translation('fr');
            $label = $t->name ?? '#' . $category->id;
            $selectedCollectionsPayload[] = [
                'id' => $category->id,
                'label' => $label,
            ];
        }
    }

    // GROUPES CLIENTS
    $selectedCustomerGroupsPayload = [];
    foreach ($customerGroups as $group) {
        if (in_array($group->id, $selectedGroupIds)) {
            $selectedCustomerGroupsPayload[] = [
                'id' => $group->id,
                'label' => $group->name,
            ];
        }
    }

    // CLIENTS
    $selectedCustomersPayload = [];
    foreach ($customers as $customer) {
        if (in_array($customer->id, $selectedCustomerIds)) {
            $label = trim(($customer->lastname ?? '') . ' ' . ($customer->firstname ?? ''));
            $label = $label !== '' ? $label . ' — ' . $customer->email : $customer->email;
            $selectedCustomersPayload[] = [
                'id' => $customer->id,
                'label' => $label,
            ];
        }
    }

    // Afficher ou non le select du type
    $showTypeSelect = !$hasPresetType || $d;
@endphp

<div class="space-y-4" x-data="{
    type: '{{ $initialType }}',
    method: '{{ $initialMethod }}',
    productScope: '{{ $initialProductScope }}',
    customerScope: '{{ $initialCustomerScope }}',

    productModalOpen: false,
    collectionModalOpen: false,
    customerGroupModalOpen: false,
    customerModalOpen: false,

    productSearch: '',
    collectionSearch: '',
    customerGroupSearch: '',
    customerSearch: '',

    selectedProducts: @js($selectedProductsPayload),
    selectedCollections: @js($selectedCollectionsPayload),
    selectedCustomerGroups: @js($selectedCustomerGroupsPayload),
    selectedCustomers: @js($selectedCustomersPayload),

    addProduct(product) {
        if (!this.selectedProducts.find(p => p.id === product.id)) {
            this.selectedProducts.push(product);
        }
    },
    removeProduct(id) {
        this.selectedProducts = this.selectedProducts.filter(p => p.id !== id);
    },

    addCollection(category) {
        if (!this.selectedCollections.find(c => c.id === category.id)) {
            this.selectedCollections.push(category);
        }
    },
    removeCollection(id) {
        this.selectedCollections = this.selectedCollections.filter(c => c.id !== id);
    },

    addCustomerGroup(group) {
        if (!this.selectedCustomerGroups.find(g => g.id === group.id)) {
            this.selectedCustomerGroups.push(group);
        }
    },
    removeCustomerGroup(id) {
        this.selectedCustomerGroups = this.selectedCustomerGroups.filter(g => g.id !== id);
    },

    addCustomer(customer) {
        if (!this.selectedCustomers.find(c => c.id === customer.id)) {
            this.selectedCustomers.push(customer);
        }
    },
    removeCustomer(id) {
        this.selectedCustomers = this.selectedCustomers.filter(c => c.id !== id);
    }
}">

    {{-- Si type pré-sélectionné, on l'envoie en hidden --}}
    @if (!$showTypeSelect)
        <input type="hidden" name="type" value="{{ $initialType }}">
    @endif

    {{-- Détails --}}
    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
        <h2 class="text-xs font-semibold text-neutral-700">Détails</h2>

        <div class="grid grid-cols-1 gap-3">
            {{-- Méthode = onglets Automatique / Code promo --}}
            <div>
                <label class="text-xxs text-neutral-500">Type d’application</label>
                <div class="mt-1 inline-flex rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-xxs">
                    <button type="button" @click="method = 'automatic'"
                        :class="method === 'automatic'
                            ?
                            'px-2.5 py-1 rounded-md bg-black text-white font-semibold' :
                            'px-2.5 py-1 rounded-md text-neutral-600'">
                        Automatique
                    </button>
                    <button type="button" @click="method = 'code'"
                        :class="method === 'code'
                            ?
                            'px-2.5 py-1 rounded-md bg-black text-white font-semibold' :
                            'px-2.5 py-1 rounded-md text-neutral-600'">
                        Code promo
                    </button>
                </div>
                {{-- Champ réel envoyé au backend --}}
                <input type="hidden" name="method" :value="method">
                @error('method')
                    <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            {{-- Nom --}}
            <div>
                <label class="text-xxs text-neutral-500">Nom</label>
                <input type="text" name="name" value="{{ old('name', $d->name ?? '') }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
                @error('name')
                    <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>



        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {{-- Code promo : seulement si method === 'code' --}}
            <div x-show="method === 'code'">
                <label class="text-xxs text-neutral-500">Code promo</label>
                <input type="text" name="code" value="{{ old('code', $d->code ?? '') }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs" placeholder="SUMMER10">
                @error('code')
                    <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xxs text-neutral-400">
                    Le client devra saisir ce code pour obtenir la réduction.
                </p>
            </div>

            {{-- Type de réduction --}}
            <div>
                @if ($showTypeSelect)
                    <label class="text-xxs text-neutral-500">Type de réduction</label>
                    <select name="type" x-model="type" class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
                        <option value="product">Sur les produits</option>
                        <option value="order">Sur la commande</option>
                        <option value="shipping">Sur les frais de port</option>
                        <option value="buy_x_get_y">Achetez X, obtenez Y</option>
                    </select>
                    @error('type')
                        <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        </div>
    </div>

    {{-- Valeur --}}
    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
        <h2 class="text-xs font-semibold text-neutral-700">Valeur</h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xxs text-neutral-500">Type</label>
                <select name="value_type" class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
                    <option value="">—</option>
                    <option value="percentage" @selected(old('value_type', $d->value_type ?? '') === 'percentage')>%</option>
                    <option value="fixed_amount" @selected(old('value_type', $d->value_type ?? '') === 'fixed_amount')>Montant fixe</option>
                    <option value="free_shipping" @selected(old('value_type', $d->value_type ?? '') === 'free_shipping')>Livraison gratuite</option>
                </select>
                @error('value_type')
                    <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="type !== 'shipping'">
                <label class="text-xxs text-neutral-500">Valeur</label>
                <input type="number" step="0.01" name="value" value="{{ old('value', $d->value ?? '') }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
                @error('value')
                    <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-xxs text-neutral-500">Priorité</label>
                <input type="number" name="priority" value="{{ old('priority', $d->priority ?? 0) }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
                @error('priority')
                    <p class="text-xxs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Section Buy X Get Y spécifique --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" x-show="type === 'buy_x_get_y'">
            <div>
                <label class="text-xxs text-neutral-500">Quantité achetée (X)</label>
                <input type="number" name="buy_quantity" value="{{ old('buy_quantity', $d->buy_quantity ?? '') }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xxs text-neutral-500">Quantité offerte (Y)</label>
                <input type="number" name="get_quantity" value="{{ old('get_quantity', $d->get_quantity ?? '') }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xxs text-neutral-500">Y est gratuit ?</label>
                <select name="get_is_free" class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
                    <option value="1" @selected(old('get_is_free', (int) ($d->get_is_free ?? 1)) === 1)>Oui</option>
                    <option value="0" @selected(old('get_is_free', (int) ($d->get_is_free ?? 1)) === 0)>Non</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Applicabilité / ciblage produits & catégories --}}
    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-4">
        <h2 class="text-xs font-semibold text-neutral-700">Applicabilité</h2>

        {{-- Produits concernés --}}
        <div class="space-y-2">
            <p class="text-xxs text-neutral-500 font-semibold">Produits concernés</p>

            <div class="flex flex-col gap-1 text-xs">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="product_scope" value="all" x-model="productScope" class="text-xs">
                    <span>Tous les produits</span>
                </label>

                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="product_scope" value="products" x-model="productScope"
                        class="text-xs">
                    <span>Produits spécifiques</span>
                </label>

                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="product_scope" value="collections" x-model="productScope"
                        class="text-xs">
                    <span>Catégories / collections</span>
                </label>
            </div>

            {{-- Sélection produits : listing + modal --}}
            <div x-show="productScope === 'products'" class="mt-2 space-y-2">
                <div class="flex items-center justify-between border border-gray-100 p-2 rounded-lg">
                    <label class="text-xxs text-neutral-500">Produits sélectionnés</label>
                    <button type="button" @click="productModalOpen = true"
                        class="px-2 py-1 rounded-md border text-xxs">
                        Ajouter des produits
                    </button>
                </div>

                <template x-if="selectedProducts.length === 0">
                    <p class="text-xxs text-neutral-400">
                        Aucun produit sélectionné pour le moment.
                    </p>
                </template>

                <div class="space-y-1" x-show="selectedProducts.length > 0">
                    <template x-for="product in selectedProducts" :key="product.id">
                        <div class="flex items-center justify-between rounded-md border border-neutral-200 px-2 py-2">
                            <div class="flex items-center gap-2">
                                <img class="w-10 h-10 rounded-md" :src="product.imageUrl" alt="image produit" />
                                <span class="text-xs" x-text="product.label"></span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" @click.prevent="removeProduct(product.id)"
                                    class="text-xxs text-gray-500 bg-gray-50 border rounded-lg border-gray-100 hover:underline h-6 w-6 flex items-center justify-center">
                                    <x-lucide-trash class="w-3 h-3" />
                                </button>
                            </div>
                            <input type="hidden" name="product_ids[]" :value="product.id">
                        </div>
                    </template>
                </div>
            </div>

            {{-- Sélection catégories : listing + modal --}}
            <div x-show="productScope === 'collections'" class="mt-2 space-y-2">
                <div class="flex items-center justify-between border border-gray-100 p-2 rounded-lg">
                    <label class="text-xxs text-neutral-500">Catégories sélectionnées</label>
                    <button type="button" @click="collectionModalOpen = true"
                        class="px-2 py-1 rounded-md border text-xxs">
                        Ajouter des catégories
                    </button>
                </div>

                <template x-if="selectedCollections.length === 0">
                    <p class="text-xxs text-neutral-400">
                        Aucune catégorie sélectionnée pour le moment.
                    </p>
                </template>

                <div class="space-y-1" x-show="selectedCollections.length > 0">
                    <template x-for="category in selectedCollections" :key="category.id">
                        <div class="flex items-center justify-between rounded-md border border-neutral-200 px-2 py-1">
                            <span class="text-xs" x-text="category.label"></span>
                            <button type="button" @click.prevent="removeCollection(category.id)"
                                class="text-xxs text-gray-500 bg-gray-50 border rounded-lg border-gray-100 hover:underline h-6 w-6 flex items-center justify-center">
                                <x-lucide-trash class="w-3 h-3" />
                            </button>
                            <input type="hidden" name="collection_ids[]" :value="category.id">
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Audience / clients --}}
    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-4">
        <h2 class="text-xs font-semibold text-neutral-700">Clients concernés</h2>

        <div class="space-y-2">
            <div class="flex flex-col gap-1 text-xs">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="customer_selection" value="all" x-model="customerScope">
                    <span>Tous les clients</span>
                </label>

                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="customer_selection" value="groups" x-model="customerScope">
                    <span>Groupes de clients spécifiques</span>
                </label>

                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="customer_selection" value="customers" x-model="customerScope">
                    <span>Clients spécifiques</span>
                </label>
            </div>

            {{-- Groupes clients : listing + modal --}}
            <div x-show="customerScope === 'groups'" class="mt-2 space-y-2">
                <div class="flex items-center justify-between border border-gray-100 p-2 rounded-lg">
                    <label class="text-xxs text-neutral-500">Groupes sélectionnés</label>
                    <button type="button" @click="customerGroupModalOpen = true"
                        class="px-2 py-1 rounded-md border text-xxs">
                        Ajouter des groupes
                    </button>
                </div>

                <template x-if="selectedCustomerGroups.length === 0">
                    <p class="text-xxs text-neutral-400">
                        Aucun groupe sélectionné pour le moment.
                    </p>
                </template>

                <div class="space-y-1" x-show="selectedCustomerGroups.length > 0">
                    <template x-for="group in selectedCustomerGroups" :key="group.id">
                        <div class="flex items-center justify-between rounded-md border border-neutral-200 px-2 py-1">
                            <span class="text-xs" x-text="group.label"></span>
                            <button type="button" @click.prevent="removeCustomerGroup(group.id)"
                                class="text-xxs text-gray-500 bg-gray-50 border rounded-lg border-gray-100 hover:underline h-6 w-6 flex items-center justify-center">
                                <x-lucide-trash class="w-3 h-3" />
                            </button>
                            <input type="hidden" name="customer_group_ids[]" :value="group.id">
                        </div>
                    </template>
                </div>
            </div>

            {{-- Clients spécifiques : listing + modal --}}
            <div x-show="customerScope === 'customers'" class="mt-2 space-y-2">
                <div class="flex items-center justify-between border border-gray-100 p-2 rounded-lg">
                    <label class="text-xxs text-neutral-500">Clients sélectionnés</label>
                    <button type="button" @click="customerModalOpen = true"
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
                        <div class="flex items-center justify-between rounded-md border border-neutral-200 px-2 py-1">
                            <span class="text-xs" x-text="customer.label"></span>
                            <button type="button" @click.prevent="removeCustomer(customer.id)"
                                class="text-xxs text-gray-500 bg-gray-50 border rounded-lg border-gray-100 hover:underline h-6 w-6 flex items-center justify-center">
                                <x-lucide-trash class="w-3 h-3" />
                            </button>
                            <input type="hidden" name="customer_ids[]" :value="customer.id">
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Conditions de commande (montant / quantité / dates / limites) --}}
    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
        <h2 class="text-xs font-semibold text-neutral-700">Conditions</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="text-xxs text-neutral-500">Montant minimum de commande</label>
                <input type="number" step="0.01" name="min_subtotal"
                    value="{{ old('min_subtotal', $d->min_subtotal ?? '') }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xxs text-neutral-500">Nombre minimum d’articles</label>
                <input type="number" name="min_quantity" value="{{ old('min_quantity', $d->min_quantity ?? '') }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="text-xxs text-neutral-500">Début</label>
                <input type="datetime-local" name="starts_at"
                    value="{{ old('starts_at', optional($d->starts_at ?? null)->format('Y-m-d\TH:i')) }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xxs text-neutral-500">Fin</label>
                <input type="datetime-local" name="ends_at"
                    value="{{ old('ends_at', optional($d->ends_at ?? null)->format('Y-m-d\TH:i')) }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xxs text-neutral-500">Limite d’utilisation globale</label>
                <input type="number" name="usage_limit" value="{{ old('usage_limit', $d->usage_limit ?? '') }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xxs text-neutral-500">Limite par client</label>
                <input type="number" name="usage_limit_per_customer"
                    value="{{ old('usage_limit_per_customer', $d->usage_limit_per_customer ?? '') }}"
                    class="mt-1 w-full rounded-md border px-3 py-1.5 text-xs">
            </div>
            <div class="flex items-center gap-2 mt-5">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $d->is_active ?? 1))>
                <span class="text-xxs text-neutral-700">Réduction active</span>
            </div>
        </div>
    </div>

    {{-- Compatibilité --}}
    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-3">
        <h2 class="text-xs font-semibold text-neutral-700">Compatibilité</h2>

        <p class="text-xxs text-neutral-500">
            Définissez quelles réductions peuvent être combinées ensemble (comme sur Shopify).
        </p>

        <div class="grid grid-cols-1 gap-3 text-xs">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="combines_with_product_discounts" value="1"
                    @checked(old('combines_with_product_discounts', $d->combines_with_product_discounts ?? false))>
                <span>Avec les réductions produits</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="combines_with_order_discounts" value="1"
                    @checked(old('combines_with_order_discounts', $d->combines_with_order_discounts ?? false))>
                <span>Avec les réductions commande</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="combines_with_shipping_discounts" value="1"
                    @checked(old('combines_with_shipping_discounts', $d->combines_with_shipping_discounts ?? false))>
                <span>Avec les réductions livraison</span>
            </label>
        </div>
    </div>

    {{-- MODAL PRODUITS --}}
    <div x-cloak x-show="productModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="productModalOpen = false"></div>

        <div @click.stop
            class="relative z-10 w-full max-w-3xl rounded-2xl bg-white shadow-xl border border-black/5 p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-xs font-semibold">Ajouter des produits</h3>
                    <p class="text-xxs text-neutral-500">
                        Recherchez et ajoutez un ou plusieurs produits à cette réduction.
                    </p>
                </div>
                <button type="button" @click="productModalOpen = false"
                    class="p-1 rounded-full hover:bg-neutral-100">
                    <x-lucide-x class="w-4 h-4 text-neutral-500" />
                </button>
            </div>

            <div>
                <input type="text" x-model="productSearch" placeholder="Rechercher un produit…"
                    class="w-full rounded-md border px-3 py-1.5 text-xs">
            </div>

            <div class="max-h-80 overflow-y-auto border border-neutral-100 rounded-xl">
                <table class="min-w-full text-xs">
                    <tbody>
                        @foreach ($products as $product)
                            @php
                                $t = $product->translation('fr');
                                $label = $t->name ?? '#' . $product->id;
                                $imageUrl = $product->mainImage?->url ?? asset('images/modules/no-icon.png');
                            @endphp
                            <tr class="border-b border-neutral-100"
                                x-show="productSearch === '' || {{ json_encode(Str::lower($label)) }}.includes(productSearch.toLowerCase())">
                                <td class="px-3 py-2 align-middle">
                                    <div class="flex items-center gap-2">
                                        <img class="w-10 h-10 rounded-md" src="{{ $imageUrl }}"
                                            alt="image produit" />
                                        <div>
                                            <div class="font-medium text-xs">{{ $label }}</div>
                                            <div class="text-xxs text-neutral-400">
                                                SKU : {{ $product->sku }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <button type="button" @click.prevent="addProduct(@js(['id' => $product->id, 'label' => $label, 'imageUrl' => $imageUrl]))"
                                        class="px-2 py-1 rounded-md border text-xxs">
                                        Ajouter
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        @if ($products->isEmpty())
                            <tr>
                                <td class="px-3 py-4 text-center text-xxs text-neutral-400">
                                    Aucun produit disponible.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="productModalOpen = false"
                    class="text-xxs text-neutral-500 hover:text-neutral-700">
                    Fermer
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL CATEGORIES --}}
    <div x-cloak x-show="collectionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="collectionModalOpen = false"></div>

        <div @click.stop
            class="relative z-10 w-full max-w-3xl rounded-2xl bg-white shadow-xl border border-black/5 p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-xs font-semibold">Ajouter des catégories</h3>
                    <p class="text-xxs text-neutral-500">
                        Recherchez et ajoutez une ou plusieurs catégories à cette réduction.
                    </p>
                </div>
                <button type="button" @click="collectionModalOpen = false"
                    class="p-1 rounded-full hover:bg-neutral-100">
                    <x-lucide-x class="w-4 h-4 text-neutral-500" />
                </button>
            </div>

            <div>
                <input type="text" x-model="collectionSearch" placeholder="Rechercher une catégorie…"
                    class="w-full rounded-md border px-3 py-1.5 text-xs">
            </div>

            <div class="max-h-80 overflow-y-auto border border-neutral-100 rounded-xl">
                <table class="min-w-full text-xs">
                    <tbody>
                        @foreach ($collections as $category)
                            @php
                                $t = $category->translation('fr');
                                $label = $t->name ?? '#' . $category->id;
                            @endphp
                            <tr class="border-b border-neutral-100"
                                x-show="collectionSearch === '' || {{ json_encode(Str::lower($label)) }}.includes(collectionSearch.toLowerCase())">
                                <td class="px-3 py-2 align-middle">
                                    <div class="font-medium text-xs">{{ $label }}</div>
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <button type="button"
                                        @click.prevent="addCollection(@js(['id' => $category->id, 'label' => $label]))"
                                        class="px-2 py-1 rounded-md border text-xxs">
                                        Ajouter
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        @if ($collections->isEmpty())
                            <tr>
                                <td class="px-3 py-4 text-center text-xxs text-neutral-400">
                                    Aucune catégorie disponible.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="collectionModalOpen = false"
                    class="text-xxs text-neutral-500 hover:text-neutral-700">
                    Fermer
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL GROUPES CLIENTS --}}
    <div x-cloak x-show="customerGroupModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="customerGroupModalOpen = false"></div>

        <div @click.stop
            class="relative z-10 w-full max-w-3xl rounded-2xl bg-white shadow-xl border border-black/5 p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-xs font-semibold">Ajouter des groupes de clients</h3>
                    <p class="text-xxs text-neutral-500">
                        Recherchez et ajoutez un ou plusieurs groupes de clients à cette réduction.
                    </p>
                </div>
                <button type="button" @click="customerGroupModalOpen = false"
                    class="p-1 rounded-full hover:bg-neutral-100">
                    <x-lucide-x class="w-4 h-4 text-neutral-500" />
                </button>
            </div>

            <div>
                <input type="text" x-model="customerGroupSearch" placeholder="Rechercher un groupe…"
                    class="w-full rounded-md border px-3 py-1.5 text-xs">
            </div>

            <div class="max-h-80 overflow-y-auto border border-neutral-100 rounded-xl">
                <table class="min-w-full text-xs">
                    <tbody>
                        @foreach ($customerGroups as $group)
                            @php
                                $label = $group->name;
                            @endphp
                            <tr class="border-b border-neutral-100"
                                x-show="customerGroupSearch === '' || {{ json_encode(Str::lower($label)) }}.includes(customerGroupSearch.toLowerCase())">
                                <td class="px-3 py-2 align-middle">
                                    <div class="font-medium text-xs">{{ $label }}</div>
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <button type="button"
                                        @click.prevent="addCustomerGroup(@js(['id' => $group->id, 'label' => $label]))"
                                        class="px-2 py-1 rounded-md border text-xxs">
                                        Ajouter
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        @if ($customerGroups->isEmpty())
                            <tr>
                                <td class="px-3 py-4 text-center text-xxs text-neutral-400">
                                    Aucun groupe de clients disponible.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="customerGroupModalOpen = false"
                    class="text-xxs text-neutral-500 hover:text-neutral-700">
                    Fermer
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL CLIENTS --}}
    <div x-cloak x-show="customerModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="customerModalOpen = false"></div>

        <div @click.stop
            class="relative z-10 w-full max-w-3xl rounded-2xl bg-white shadow-xl border border-black/5 p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-xs font-semibold">Ajouter des clients</h3>
                    <p class="text-xxs text-neutral-500">
                        Recherchez et ajoutez un ou plusieurs clients à cette réduction.
                    </p>
                </div>
                <button type="button" @click="customerModalOpen = false"
                    class="p-1 rounded-full hover:bg-neutral-100">
                    <x-lucide-x class="w-4 h-4 text-neutral-500" />
                </button>
            </div>

            <div>
                <input type="text" x-model="customerSearch" placeholder="Rechercher un client (nom, email)…"
                    class="w-full rounded-md border px-3 py-1.5 text-xs">
            </div>

            <div class="max-h-80 overflow-y-auto border border-neutral-100 rounded-xl">
                <table class="min-w-full text-xs">
                    <tbody>
                        @foreach ($customers as $customer)
                            @php
                                $name = trim(($customer->lastname ?? '') . ' ' . ($customer->firstname ?? ''));
                                $label = $name !== '' ? $name . ' — ' . $customer->email : $customer->email;
                            @endphp
                            <tr class="border-b border-neutral-100"
                                x-show="customerSearch === '' || {{ json_encode(Str::lower($label)) }}.includes(customerSearch.toLowerCase())">
                                <td class="px-3 py-2 align-middle">
                                    <div class="font-medium text-xs">{{ $label }}</div>
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <button type="button" @click.prevent="addCustomer(@js(['id' => $customer->id, 'label' => $label]))"
                                        class="px-2 py-1 rounded-md border text-xxs">
                                        Ajouter
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        @if ($customers->isEmpty())
                            <tr>
                                <td class="px-3 py-4 text-center text-xxs text-neutral-400">
                                    Aucun client disponible.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="customerModalOpen = false"
                    class="text-xxs text-neutral-500 hover:text-neutral-700">
                    Fermer
                </button>
            </div>
        </div>
    </div>

</div>
