@extends('admin::layout')

@section('title', 'Paramètres')
@section('page-title', 'Paramètres')

@section('content')
    <div class="space-y-4">

        {{-- Header global paramètres --}}


        <div class="grid grid-cols-1 md:grid-cols-[300px_minmax(0,1fr)] gap-6 items-start">
            {{-- Sidebar paramètres --}}
            <div class="rounded-2xl shadow-sm text-xs text-gray-700 border border-black/5 sticky top-20">
                <div class="p-3 bg-gray-50 rounded-t-2xl border-b border-black/5">
                    <div class="text-sm font-semibold text-gray-900">Paramètres</div>
                    <div class="text-xs text-gray-500">
                        Configurez votre boutique, vos intégrations et vos accès API.
                    </div>
                </div>

                <aside class="bg-white   p-3 space-y-1 ">
                    @php
                        $items = [
                            [
                                'label' => 'Général',
                                'route' => 'admin.settings.index',
                                'icon' => 'home',
                                'match' => 'admin.settings.index',
                            ],
                            [
                                'label' => 'Clés API',
                                'route' => 'admin.settings.api-keys.index',
                                'icon' => 'key-round',
                                'match' => 'admin.settings.api-keys.*',
                            ],
                            [
                                'label' => 'Utilisateurs',
                                'route' => 'admin.settings.users.index',
                                'icon' => 'user',
                                'match' => 'admin.settings.users.*',
                            ],
                            [
                                'label' => 'Rôles',
                                'route' => 'admin.settings.roles.index',
                                'icon' => 'shield',
                                'match' => 'admin.settings.roles.*',
                            ],
                            [
                                'label' => 'Permissions',
                                'route' => 'admin.settings.permissions.index',
                                'icon' => 'lock',
                                'match' => 'admin.settings.permissions.*',
                            ],
                            [
                                'label' => 'Meilisearch',
                                'route' => 'admin.settings.meilisearch.index',
                                'icon' => 'search',
                                'match' => 'admin.settings.meilisearch.*',
                            ],
                            [
                                'label' => 'Transporteurs',
                                'route' => 'admin.settings.shipping_methods.index',
                                'icon' => 'truck',
                                'match' => 'admin.settings.shipping_methods.*',
                            ],
                            [
                                'label' => 'Paiements',
                                'route' => 'admin.settings.payments.index',
                                'icon' => 'credit-card',
                                'match' => 'admin.settings.payments.*',
                            ],
                            [
                                'label' => 'IA',
                                'route' => 'admin.settings.ai.index',
                                'icon' => 'cpu',
                                'match' => 'admin.settings.ai.*',
                            ],
                            [
                                'label' => 'Taxes / TVA',
                                'route' => 'admin.settings.taxes.index',
                                'icon' => 'percent',
                                'match' => 'admin.settings.taxes.*',
                            ],
                            [
                                'label' => 'Performance',
                                'route' => 'admin.settings.performance.index',
                                'icon' => 'gauge',
                                'match' => 'admin.settings.performance.*',
                            ],
                            [
                                'label' => 'Demandes RGPD',
                                'route' => 'admin.settings.gdpr.index',
                                'icon' => 'shield-check',
                                'match' => 'admin.settings.gdpr.*',
                            ],
                        ];
                        
                        // Items commentés pour plus tard
                        $commentsItems = [
                            // Tu pourras décommenter/ajouter plus tard :
                            // [
                            //     'label' => 'Général',
                            //     'route' => 'admin.settings.general',
                            //     'icon'  => '🛠️',
                            //     'match' => 'admin.settings.general',
                            // ],
                            // [
                            //     'label' => 'Intégrations & webhooks',
                            //     'route' => 'admin.settings.integrations.index',
                            //     'icon'  => '🔗',
                            //     'match' => 'admin.settings.integrations.*',
                            // ],
                        ];
                    @endphp

                    @foreach ($items as $item)
                        @php
                            $active = request()->routeIs($item['match']);
                        @endphp
                        <a href="{{ route($item['route']) }}"
                            class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg transition-all
                        {{ $active
                            ? 'bg-gray-100 text-black shadow-sm font-medium'
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                            <div class="flex items-center gap-2">
                                <span class="text-xs">
                                    <x-dynamic-component :component="'lucide-' . $item['icon']" class="w-4 h-4" />
                                </span>
                                <span class="text-xs font-medium">
                                    {{ $item['label'] }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </aside>
                <div class="border-t border-black/5 p-3 bg-white rounded-b-2xl">
                    <div class="flex items-center gap-2">
                        <div
                            class="bg-black w-8 h-8 rounded-lg uppercase flex items-center justify-center text-white font-bold text-lg">
                            {{ substr(auth()->user()->firstname, 0, 1) }}
                        </div>
                        <div class="flex flex-col">
                            <span class="text-md font-medium text-black">{{ auth()->user()->name ?? 'admin' }}</span>
                            <span>{{ auth()->user()->email ?? 'admin' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contenu de la page de paramètres --}}
            <div>
                @foreach ($items as $item)
                    @php
                        $active = request()->routeIs($item['match']);
                    @endphp
                    @if ($active)
                        <div class="flex items-center gap-3">
                            <x-dynamic-component :component="'lucide-' . $item['icon']" class="w-5 h-5" />
                            <span class="font-bold text-lg">{{ $item['label'] }}</span>
                        </div>
                    @endif
                @endforeach
                <section class="space-y-4 bg-white p-4 rounded-2xl shadow-sm border border-black/5  mt-3">
                    @yield('settings-content')
                </section>
            </div>
        </div>
    </div>
@endsection
