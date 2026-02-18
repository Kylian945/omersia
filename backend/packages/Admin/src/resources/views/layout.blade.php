<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin') • Omersia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Breeze / Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Additional styles loaded by child views --}}
    @stack('styles')

    {{-- Additional scripts loaded by child views --}}
    @yield('head-scripts')

    <style>
        /* Optionnel : police plus clean si tu veux pousser le style */
        body {
            font-feature-settings: "cv02", "cv03", "cv04", "cv11";
        }

        .scrollbar-thin::-webkit-scrollbar {
            width: 4px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 253, 0.25);
            border-radius: 9999px;
        }

        [x-cloak] {
            display: none !important;
        }

        .bo-ai-assistant-message {
            display: flex;
            margin-bottom: 0.75rem;
        }

        .bo-ai-assistant-message--user {
            justify-content: flex-end;
        }

        .bo-ai-assistant-message--assistant {
            justify-content: flex-start;
        }

        .bo-ai-assistant-bubble {
            max-width: 90%;
            white-space: pre-wrap;
            word-break: break-word;
            border-radius: 0.75rem;
            border: 1px solid rgba(226, 232, 240, 1);
            padding: 0.625rem 0.75rem;
            font-size: 0.75rem;
            line-height: 1.2rem;
            color: #0f172a;
            background: #f8fafc;
        }

        .bo-ai-assistant-message--user .bo-ai-assistant-bubble {
            color: #ffffff;
            border-color: rgb(71, 71, 71);
            background: #000000;
        }
    </style>
</head>

@php
    $hasConfiguredAiProvider = (bool) ($hasConfiguredAiProvider ?? false);
    $aiProviderSettingsUrl = (string) ($aiProviderSettingsUrl ?? route('admin.settings.ai.index'));
@endphp

<body class="bg-[#f6f6f7] text-[#111827] antialiased"
    data-ai-provider-ready="{{ $hasConfiguredAiProvider ? '1' : '0' }}"
    data-ai-provider-settings-url="{{ $aiProviderSettingsUrl }}">

    <div class="min-h-screen flex">

        {{-- SIDEBAR --}}
        <aside
            class="sticky top-0 hidden md:flex md:flex-col w-64 max-h-[100vh] bg-[#f9fafb] text-slate-800 border-r border-slate-200/80">
            {{-- BRAND --}}
            <div class="flex items-center gap-2 px-4 h-14 border-b border-slate-200/80 bg-white/70 backdrop-blur-sm">
                <div
                    class="h-7 w-7 rounded-xl bg-black flex items-center justify-center font-black text-white shadow-sm">
                    O
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[.18em] text-slate-800">Omersia</div>
                    <div class="text-xs text-slate-500">Admin console</div>
                </div>
            </div>

            {{-- NAV --}}
            <nav x-data="{ open: {} }" class="flex-1 px-3 py-4 space-y-1 text-xs overflow-y-auto scrollbar-thin">
                @php
                    $groups = [
                        [
                            'label' => null,
                            'items' => [
                                [
                                    'label' => 'Tableau de bord',
                                    'icon' => 'layout-dashboard',
                                    'route' => 'admin.dashboard',
                                    'match' => 'admin.dashboard',
                                    'permission' => null, // Accessible à tous
                                ],
                            ],
                        ],
                        [
                            'label' => 'Clients',
                            'icon' => 'user',
                            'key' => 'customer',
                            'permission' => 'customers.view',
                            'items' => [
                                [
                                    'label' => 'Clients',
                                    // 'icon' => 'user',
                                    'route' => 'admin.customers',
                                    'match' => 'admin.customers',
                                    'permission' => 'customers.view',
                                ],
                                [
                                    'label' => 'Groupes',
                                    // 'icon' => 'users',
                                    'route' => 'customer-groups.index',
                                    'match' => 'customer-groups.*',
                                    'permission' => 'customers.view',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Commandes',
                            'icon' => 'shopping-cart',
                            'key' => 'orders',
                            'permission' => 'orders.view',
                            'items' => [
                                [
                                    'label' => 'Toutes les commandes',
                                    // 'icon' => 'shopping-cart',
                                    'route' => 'admin.orders.index',
                                    'match' => ['admin.orders.index', 'admin.orders.show'],
                                    'permission' => 'orders.view',
                                ],
                                [
                                    'label' => 'Paniers abandonnés',
                                    // 'icon' => 'shopping-bag',
                                    'route' => 'admin.orders.drafts',
                                    'match' => 'admin.orders.drafts',
                                    'permission' => 'orders.view',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Catalogue',
                            'icon' => 'shopping-bag',
                            'key' => 'products',
                            'permission' => 'products.view',
                            'items' => [
                                [
                                    'label' => 'Produits',
                                    // 'icon' => 'package',
                                    'route' => 'products.index',
                                    'match' => 'products.*',
                                    'permission' => 'products.view',
                                ],
                                [
                                    'label' => 'Catégories',
                                    // 'icon' => 'folders',
                                    'route' => 'categories.index',
                                    'match' => 'categories.*',
                                    'permission' => 'categories.view',
                                ],
                            ],
                        ],
                        [
                            'label' => null,
                            'items' => [
                                [
                                    'label' => 'Réductions',
                                    'icon' => 'badge-percent',
                                    'route' => 'discounts.index',
                                    'match' => 'discounts.*',
                                    'permission' => 'discounts.view',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Contenu',
                            'icon' => 'file-text',
                            'key' => 'content',
                            'permission' => 'pages.view',
                            'items' => [
                                [
                                    'label' => 'Pages E-commerce',
                                    // 'icon' => 'shopping-bag',
                                    'route' => 'admin.apparence.ecommerce-pages.index',
                                    'match' => 'admin.apparence.ecommerce-pages.*',
                                    'permission' => 'pages.view',
                                ],
                                [
                                    'label' => 'Pages CMS',
                                    // 'icon' => 'file-text',
                                    'route' => 'pages.index',
                                    'match' => 'pages.*',
                                    'permission' => 'pages.view',
                                ],
                                [
                                    'label' => 'Galerie d\'images',
                                    // 'icon' => 'image',
                                    'route' => 'admin.apparence.media.index',
                                    'match' => 'admin.apparence.media.*',
                                    'permission' => 'media.view',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Apparence',
                            'icon' => 'palette',
                            'key' => 'apparence',
                            'permission' => 'themes.view',
                            'items' => [
                                [
                                    'label' => 'Menu',
                                    // 'icon' => 'list',
                                    'route' => 'admin.apparence.menus.index',
                                    'match' => 'admin.apparence.menus.*',
                                    'permission' => 'menu.view',
                                ],
                                [
                                    'label' => 'Thème',
                                    // 'icon' => 'brush',
                                    'route' => 'admin.apparence.theme.index',
                                    'match' => 'admin.apparence.theme.*',
                                    'permission' => 'themes.view',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Modules',
                            'icon' => 'plug-2',
                            'key' => 'modules',
                            'permission' => 'modules.view',
                            'items' => [
                                [
                                    'label' => 'Gestionnaire de modules',
                                    // 'icon' => 'layers',
                                    'route' => 'admin.modules.index',
                                    'match' => 'admin.modules.index',
                                    'permission' => 'modules.view',
                                ],
                                [
                                    'label' => 'Positions',
                                    // 'icon' => 'layers',
                                    'route' => 'admin.modules.positions',
                                    'match' => 'admin.modules.positions*',
                                    'permission' => 'modules.view',
                                ],
                            ],
                        ],
                    ];
                @endphp

                @foreach ($groups as $group)
                    @php
                        // Tous les items sont visibles, on ne filtre plus
                        $visibleItems = collect($group['items']);

                        // Vérifier si la permission du groupe est valide
                        $groupHasPermission = !isset($group['permission']) || $group['permission'] === null || auth()->user()->can($group['permission']);

                        $hasActive = $visibleItems->contains(
                            fn($item) => request()->routeIs($item['match']),
                        );
                        $key = $group['key'] ?? $loop->index;
                    @endphp

                    {{-- Divider avant les modules installés --}}
                    @if (($group['key'] ?? null) === 'installed-modules')
                        <div class="mt-4 pt-4 border-b border-slate-200"></div>
                    @endif

                    {{-- Groupe avec label = section pliable --}}
                    @if ($group['label'])
                        <div class="relative">
                            <button @click="open['{{ $key }}'] = !open['{{ $key }}']"
                                x-init="open['{{ $key }}'] = {{ $hasActive ? 'true' : 'false' }}"
                                class="w-full flex items-center justify-between px-2.5 py-2 rounded-lg
                               {{ $groupHasPermission
                                   ? 'text-slate-600 hover:text-slate-900 hover:bg-white hover:border-slate-200'
                                   : 'text-slate-400 cursor-not-allowed opacity-60' }}
                               border border-transparent
                               transition text-xs font-medium"
                                {{ $groupHasPermission ? '' : 'disabled' }}>
                                <div class="flex items-center gap-2">
                                    <x-dynamic-component :component="'lucide-' . $group['icon']" class="w-4 h-4" />
                                    <span>{{ $group['label'] }}</span>
                                </div>
                                @if ($groupHasPermission)
                                    <svg x-bind:class="open['{{ $key }}'] ? 'rotate-90 text-black' : 'text-slate-400'"
                                        class="w-3 h-3 transform transition-transform duration-200" fill="none"
                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                @else
                                    <x-lucide-lock class="w-3 h-3 text-slate-400" />
                                @endif
                            </button>

                            {{-- Trait vertical sous l'icône parent --}}
                            <div x-show="open['{{ $key }}']"
                                class="absolute left-[18px] top-[40px] bottom-0 w-[2px] bg-gradient-to-b from-slate-300 to-transparent"
                                style="height: calc(100% - 40px);"></div>

                            <div x-show="open['{{ $key }}']" x-collapse class="relative">
                                @foreach ($visibleItems as $item)
                                    @php
                                        $active = request()->routeIs($item['match']);
                                        $hasItemPermission = (!isset($item['permission']) || $item['permission'] === null || auth()->user()->can($item['permission'])) && $groupHasPermission;
                                    @endphp
                                    <a href="{{ $hasItemPermission ? route($item['route']) : '#' }}"
                                        class="flex items-center gap-2 pl-7 pr-2.5 py-1.5 rounded-lg transition
                                      {{ !$hasItemPermission
                                          ? 'text-slate-300 cursor-not-allowed opacity-50 pointer-events-none'
                                          : ($active
                                              ? 'bg-white text-black font-semibold border border-gray-200 shadow-sm mb-1'
                                              : 'text-slate-500 hover:text-slate-900 hover:bg-white hover:border-slate-200 border border-transparent') }}">
                                        <span class="text-sm">
                                            @if (isset($item['icon']))
                                                <x-dynamic-component :component="'lucide-' . ($item['icon'] ?? 'folder')" class="w-4 h-4" />
                                            @endif
                                        </span>
                                        <span>{{ $item['label'] }}</span>
                                        @if (!$hasItemPermission)
                                            <x-lucide-lock class="w-3 h-3 ml-auto" />
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Groupe sans label = lien direct (Dashboard) --}}
                    @if (!$group['label'])
                        @foreach ($visibleItems as $item)
                            @php
                                $active = request()->routeIs($item['match']);
                                $hasItemPermission = !isset($item['permission']) || $item['permission'] === null || auth()->user()->can($item['permission']);
                            @endphp
                            <a href="{{ $hasItemPermission ? route($item['route']) : '#' }}"
                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg transition
                              {{ !$hasItemPermission
                                  ? 'text-slate-400 cursor-not-allowed opacity-50 pointer-events-none border border-transparent'
                                  : ($active
                                      ? 'bg-white text-black border border-gray-200 font-semibold shadow-sm'
                                      : 'text-slate-600 hover:text-slate-900 hover:bg-white hover:border-slate-200 border border-transparent text-xs font-medium') }}">
                                <span class="text-sm">
                                    <x-dynamic-component :component="'lucide-' . ($item['icon'] ?? 'dot')" class="w-4 h-4" />
                                </span>
                                <span>{{ $item['label'] }}</span>
                                @if (!$hasItemPermission)
                                    <x-lucide-lock class="w-3 h-3 ml-auto" />
                                @endif
                            </a>
                        @endforeach
                    @endif
                @endforeach

                {{-- Canaux --}}

                <div class="mt-4 pt-4 border-t border-slate-200 text-xs text-slate-500">
                    <div class="mb-1 font-semibold text-slate-600 text-xs uppercase tracking-[.14em]">
                        Canaux
                    </div>
                    @if (isset($activeShops) && $activeShops->isNotEmpty())
                        @foreach ($activeShops as $shop)
                            @php
                                $primaryDomain = $shop->domains->firstWhere('is_primary', true);
                                $shopUrl = $primaryDomain ? 'https://' . $primaryDomain->domain : config('storefront.frontend_url');
                            @endphp
                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-white border border-slate-100 mb-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-[#008060]"></span>
                                <span class="text-xs text-slate-700 truncate" title="{{ $shop->display_name ?? $shop->name }}">
                                    {{ $shop->display_name ?? $shop->name }}
                                </span>
                                @if ($shopUrl)
                                    <a href="{{ $shopUrl }}" target="_blank"
                                        class="ml-auto text-xs text-slate-500 hover:text-slate-900">
                                        <x-lucide-eye class="w-4 h-4" />
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    @elseif (config('storefront.frontend_url'))
                        <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-white border border-slate-100">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#008060]"></span>
                            <span class="text-xs text-slate-700">Storefront headless connecté</span>
                            <a href="{{ config('storefront.frontend_url') }}" target="_blank"
                                class="ml-auto text-xs text-slate-500 hover:text-slate-900">
                                <x-lucide-eye class="w-4 h-4" />
                            </a>
                        </div>
                    @else
                        <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                            <span class="text-xs text-slate-500">Aucun canal configuré</span>
                        </div>
                    @endif
                </div>

            </nav>

            {{-- FOOTER USER --}}
            <div>
                <div class="px-3">
                    @php
                        $active = request()->routeIs('admin.settings.*');
                        $hasPermission = auth()->user()->can('manage-roles');
                    @endphp
                    <a href="{{ $hasPermission ? route('admin.settings.index') : '#' }}"
                        class="flex items-center gap-2 px-2.5 py-2 rounded-lg transition mb-3 text-xs
                          {{ !$hasPermission
                              ? 'text-slate-400 cursor-not-allowed opacity-50 pointer-events-none border border-transparent'
                              : ($active
                                  ? 'bg-white text-black border border-gray-200 font-semibold shadow-sm'
                                  : 'text-slate-600 hover:text-slate-900 hover:bg-white hover:border-slate-200 border border-transparent font-medium') }}">
                        <span class="text-sm">
                            <x-lucide-settings class="w-4 h-4" />
                        </span>
                        <span>Paramètres</span>
                        @if (!$hasPermission)
                            <x-lucide-lock class="w-3 h-3 ml-auto" />
                        @endif
                    </a>

                </div>

                <div class="px-3 py-3 border-t border-slate-200 bg-white/80 text-xs text-slate-500">

                    <div class="flex items-center justify-between gap-2">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-xxxs uppercase tracking-[.16em] text-slate-400">Connecté</span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="ml-auto">
                            @csrf
                            <button
                                class="text-xs px-2 py-1 rounded-lg border border-slate-200
                           text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>


        {{-- MAIN --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- TOPBAR --}}
            <header class="h-14 bg-white/80 backdrop-blur border-b border-black/5 flex items-center sticky top-0 z-50">
                <div class="flex-1 flex items-center justify-between px-4 md:px-6 gap-4">
                    <div class="flex flex-col">
                        <div class="text-xs uppercase tracking-[.18em] text-gray-400">
                            Admin • Omersia
                        </div>
                        <div class="text-sm font-semibold text-gray-800">
                            @yield('page-title', 'Tableau de bord')
                        </div>
                    </div>

                    <div class="hidden md:flex items-center gap-3">
                        <div class="relative">
                            <x-lucide-search class="w-4 h-4 absolute left-2.5 top-2 text-gray-500" />
                            <input type="search"
                                placeholder="{{ $hasConfiguredAiProvider ? 'Posez une question sur votre boutique...' : 'Configurez un provider IA dans Paramètres > IA' }}"
                                data-ai-assistant-search-input
                                class="h-8 w-72 rounded-lg border border-gray-200 bg-[#f9fafb] pl-8 pr-2 text-xs focus:outline-none focus:ring-2 focus:ring-gray-500/40 focus:border-gray-500/40" />
                            
                        </div>

                        <button type="button" data-ai-assistant-toggle
                            class="h-8 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 text-xs font-medium text-gray-700 hover:bg-gray-100 transition">
                            <x-lucide-wand-sparkles class="w-3.5 h-3.5" />
                            {{ $hasConfiguredAiProvider ? 'Assistant IA' : 'Assistant IA (à configurer)' }}
                        </button>
                        @if (!$hasConfiguredAiProvider)
                            <a href="{{ $aiProviderSettingsUrl }}"
                                class="text-xxs text-amber-700 hover:text-amber-800 underline decoration-amber-400/70">
                                Configurer un provider IA
                            </a>
                        @endif
                    </div>

                    <div class="md:hidden">
                        <button type="button" data-ai-assistant-toggle
                            class="h-8 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 text-xs font-medium text-gray-700 hover:bg-gray-100 transition">
                            <x-lucide-wand-sparkles class="w-3.5 h-3.5" />
                            {{ $hasConfiguredAiProvider ? 'IA' : 'IA à configurer' }}
                        </button>
                    </div>
                </div>
            </header>

            {{-- CONTENT --}}
            <main class="flex-1 px-4 md:px-6 py-4 md:py-6 space-y-4">
                {{-- Messages de succès et d'erreur --}}
                @if (session('success'))
                    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <x-lucide-check-circle class="w-4 h-4 text-green-600" />
                            <p class="text-xs text-green-700 font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <x-lucide-alert-circle class="w-4 h-4 text-red-600" />
                            <p class="text-xs text-red-700 font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
                        <div class="flex items-start gap-2">
                            <x-lucide-alert-circle class="w-4 h-4 text-red-600 mt-0.5" />
                            <div class="flex-1">
                                <p class="text-xs text-red-700 font-medium mb-1">Erreurs de validation :</p>
                                <ul class="list-disc list-inside text-xs text-red-600 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div id="bo-ai-assistant" aria-hidden="true" data-endpoint="{{ route('admin.ai.assistant.chat') }}"
        data-provider-ready="{{ $hasConfiguredAiProvider ? '1' : '0' }}"
        data-settings-url="{{ $aiProviderSettingsUrl }}"
        class="fixed inset-0 z-[70] pointer-events-none">
        <div data-ai-assistant-backdrop class="absolute inset-0 bg-slate-900/30 opacity-0 transition-opacity duration-200">
        </div>

        <aside data-ai-assistant-drawer
            class="absolute right-0 top-0 h-full w-full max-w-xl bg-white border-l border-slate-200 shadow-2xl translate-x-full transition-transform duration-200 flex flex-col">
            <div class="h-14 px-4 border-b border-slate-200 flex items-center gap-2">
                <div class="h-8 w-8 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-600">
                    <x-lucide-wand-sparkles class="w-4 h-4" />
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-800">Assistant IA Backoffice</p>
                    <p class="text-[11px] text-slate-500">Analyse ventes, panier moyen, codes promo, etc.</p>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <button type="button" data-ai-assistant-reset
                        class="text-[11px] rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-50 transition">
                        Réinitialiser
                    </button>
                    <button type="button" data-ai-assistant-close
                        class="h-8 w-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition flex items-center justify-center">
                        <x-lucide-x class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div data-ai-assistant-provider-hint
                class="{{ $hasConfiguredAiProvider ? 'hidden' : '' }} border-b border-amber-200 bg-amber-50 px-4 py-2 text-[11px] text-amber-800">
                Aucun provider IA actif. Configurez-en un dans
                <a href="{{ $aiProviderSettingsUrl }}" class="font-semibold underline decoration-amber-500/70">
                    Paramètres > IA
                </a>.
            </div>

            <div data-ai-assistant-messages class="flex-1 overflow-y-auto p-4 bg-slate-50/40">
            </div>

            <div data-ai-assistant-empty class="px-4 pb-3">
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-3 text-xs text-slate-600">
                    Posez une question métier, par exemple:
                    <div class="mt-3 grid gap-2">
                        <button type="button" data-ai-assistant-quick-question="Quel produit est le plus vendu cette année ?"
                            class="text-left rounded-md border border-slate-200 bg-slate-50 px-2.5 py-2 text-[11px] text-slate-700 hover:bg-slate-100 transition">
                            Quel produit est le plus vendu cette année ?
                        </button>
                        <button type="button"
                            data-ai-assistant-quick-question="Donne moi le montant du panier moyen sur les 2 derniers mois."
                            class="text-left rounded-md border border-slate-200 bg-slate-50 px-2.5 py-2 text-[11px] text-slate-700 hover:bg-slate-100 transition">
                            Donne moi le montant du panier moyen sur les 2 derniers mois.
                        </button>
                        <button type="button" data-ai-assistant-quick-question="Est ce que les clients utilisent les code promo ?"
                            class="text-left rounded-md border border-slate-200 bg-slate-50 px-2.5 py-2 text-[11px] text-slate-700 hover:bg-slate-100 transition">
                            Est ce que les clients utilisent les code promo ?
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 bg-white p-4">
                <form data-ai-assistant-form class="space-y-3">
                    <label for="bo-ai-assistant-input" class="sr-only">Question IA</label>
                    <textarea id="bo-ai-assistant-input" data-ai-assistant-input rows="3"
                        placeholder="{{ $hasConfiguredAiProvider ? 'Ex: Quel est le panier moyen des 2 derniers mois ?' : 'Configurez un provider IA dans Paramètres > IA pour utiliser l’assistant.' }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs leading-5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-gray-500/30 focus:border-gray-300 resize-none"></textarea>

                    <div class="flex items-center justify-between gap-3">
                        <p data-ai-assistant-status class="hidden text-xs"></p>

                        <button type="submit" data-ai-assistant-send
                            {{ $hasConfiguredAiProvider ? '' : 'disabled' }}
                            class="ml-auto inline-flex items-center justify-center rounded-lg bg-black px-3 py-2 text-xs font-semibold text-white hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            {{ $hasConfiguredAiProvider ? 'Envoyer' : 'Configurer IA' }}
                        </button>
                    </div>
                </form>
            </div>
        </aside>
    </div>

    @php
        $pusherConfig = config('broadcasting.connections.pusher', []);
        $pusherOptions = $pusherConfig['options'] ?? [];
        $pusherScheme = (string) ($pusherOptions['scheme'] ?? 'http');
        $pusherPort = (int) ($pusherOptions['port'] ?? ($pusherScheme === 'https' ? 443 : 80));
    @endphp
    <script>
        window.omersiaRealtimeConfig = {
            enabled: @json(config('broadcasting.default') === 'pusher' && !empty($pusherConfig['key'])),
            key: @json($pusherConfig['key'] ?? ''),
            cluster: @json($pusherConfig['options']['cluster'] ?? 'mt1'),
            wsHost: @json($pusherOptions['host'] ?? request()->getHost()),
            wsPort: @json($pusherPort),
            wssPort: @json($pusherPort),
            forceTLS: @json($pusherScheme === 'https'),
            authEndpoint: '/broadcasting/auth',
            csrfToken: @json(csrf_token()),
        };
    </script>

    @vite(['packages/Admin/src/resources/js/core/toast.js'])
    @can('orders.view')
        <script>
            window.omersiaOrderSoundConfig = {
                paymentSuccessUrl: @json('/admin/notifications/payment-success-audio'),
            };
        </script>
        @vite(['packages/Admin/src/resources/js/orders/paid-sound-notification.js'])
    @endcan
    @stack('scripts')

</body>

</html>
