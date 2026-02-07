@extends('admin::settings.layout')

@section('settings-content')
    <div class="space-y-4">
        {{-- En-tête avec statistiques --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Demandes RGPD / GDPR</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Gérez les demandes d'accès, d'export et de suppression des données personnelles
                    </p>
                </div>
            </div>

            {{-- Statistiques --}}
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                    <div class="flex items-center gap-2">
                        <x-lucide-clock class="w-4 h-4 text-yellow-600" />
                        <div class="text-xs font-medium text-yellow-900">En attente</div>
                    </div>
                    <div class="text-2xl font-bold text-yellow-900 mt-2">{{ $stats['pending_count'] }}</div>
                </div>

                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <div class="flex items-center gap-2">
                        <x-lucide-loader class="w-4 h-4 text-blue-600" />
                        <div class="text-xs font-medium text-blue-900">En cours</div>
                    </div>
                    <div class="text-2xl font-bold text-blue-900 mt-2">{{ $stats['processing_count'] }}</div>
                </div>

                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <div class="flex items-center gap-2">
                        <x-lucide-check-circle class="w-4 h-4 text-green-600" />
                        <div class="text-xs font-medium text-green-900">Terminées</div>
                    </div>
                    <div class="text-2xl font-bold text-green-900 mt-2">{{ $stats['completed_count'] }}</div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center gap-2">
                        <x-lucide-database class="w-4 h-4 text-gray-600" />
                        <div class="text-xs font-medium text-gray-900">Total</div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['total_count'] }}</div>
                </div>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <form method="GET" action="{{ route('admin.settings.gdpr.index') }}" class="flex gap-3">
                <div class="flex-1">
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Tous les statuts</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>En cours</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminé</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejeté</option>
                    </select>
                </div>

                <div class="flex-1">
                    <select name="type" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="all" {{ request('type') === 'all' ? 'selected' : '' }}>Tous les types</option>
                        <option value="access" {{ request('type') === 'access' ? 'selected' : '' }}>Accès aux données</option>
                        <option value="export" {{ request('type') === 'export' ? 'selected' : '' }}>Export des données</option>
                        <option value="deletion" {{ request('type') === 'deletion' ? 'selected' : '' }}>Suppression</option>
                        <option value="rectification" {{ request('type') === 'rectification' ? 'selected' : '' }}>Rectification</option>
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
                    Filtrer
                </button>
            </form>
        </div>

        {{-- Liste des demandes --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($requests as $request)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-mono text-gray-600">#{{ $request->id }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $request->customer->firstname }} {{ $request->customer->lastname }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $request->customer->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $typeLabels = [
                                            'access' => ['label' => 'Accès', 'color' => 'blue'],
                                            'export' => ['label' => 'Export', 'color' => 'purple'],
                                            'deletion' => ['label' => 'Suppression', 'color' => 'red'],
                                            'rectification' => ['label' => 'Rectification', 'color' => 'yellow'],
                                        ];
                                        $type = $typeLabels[$request->type] ?? ['label' => $request->type, 'color' => 'gray'];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $type['color'] }}-100 text-{{ $type['color'] }}-800">
                                        {{ $type['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusLabels = [
                                            'pending' => ['label' => 'En attente', 'color' => 'yellow'],
                                            'processing' => ['label' => 'En cours', 'color' => 'blue'],
                                            'completed' => ['label' => 'Terminé', 'color' => 'green'],
                                            'rejected' => ['label' => 'Rejeté', 'color' => 'red'],
                                        ];
                                        $status = $statusLabels[$request->status] ?? ['label' => $request->status, 'color' => 'gray'];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $status['color'] }}-100 text-{{ $status['color'] }}-800">
                                        {{ $status['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $request->requested_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.settings.gdpr.show', $request) }}"
                                       class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                        Voir détails →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                    <x-lucide-inbox class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                                    <p class="text-sm">Aucune demande RGPD trouvée</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.omersiaGdprRealtimeConfig = {
            requestId: null,
        };
    </script>
    @vite(['packages/Admin/src/resources/js/gdpr-realtime.js'])
@endpush
