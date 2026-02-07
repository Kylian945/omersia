@extends('admin::settings.layout')

@section('settings-content')
    <div class="space-y-4">
        {{-- En-tête avec retour --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.settings.gdpr.index') }}"
               class="p-2 rounded-lg hover:bg-gray-100 transition">
                <x-lucide-arrow-left class="w-5 h-5 text-gray-600" />
            </a>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Demande RGPD #{{ $request->id }}</h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $request->customer->firstname }} {{ $request->customer->lastname }} -
                    <span class="text-gray-500">{{ $request->customer->email }}</span>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Informations principales --}}
            <div class="lg:col-span-2 space-y-4">
                {{-- Détails de la demande --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Détails de la demande</h3>

                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Type de demande</dt>
                            <dd class="mt-1">
                                @php
                                    $typeLabels = [
                                        'access' => ['label' => 'Accès aux données', 'color' => 'blue'],
                                        'export' => ['label' => 'Export des données', 'color' => 'gray'],
                                        'deletion' => ['label' => 'Suppression des données', 'color' => 'red'],
                                        'rectification' => ['label' => 'Rectification', 'color' => 'yellow'],
                                    ];
                                    $type = $typeLabels[$request->type] ?? ['label' => $request->type, 'color' => 'gray'];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-{{ $type['color'] }}-100 text-{{ $type['color'] }}-800">
                                    {{ $type['label'] }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Statut</dt>
                            <dd class="mt-1">
                                @php
                                    $statusLabels = [
                                        'pending' => ['label' => 'En attente', 'color' => 'yellow'],
                                        'processing' => ['label' => 'En cours de traitement', 'color' => 'blue'],
                                        'completed' => ['label' => 'Terminé', 'color' => 'green'],
                                        'rejected' => ['label' => 'Rejeté', 'color' => 'red'],
                                    ];
                                    $status = $statusLabels[$request->status] ?? ['label' => $request->status, 'color' => 'gray'];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-{{ $status['color'] }}-100 text-{{ $status['color'] }}-800">
                                    {{ $status['label'] }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Date de demande</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $request->requested_at->format('d/m/Y à H:i') }}</dd>
                        </div>

                        @if($request->processed_at)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase">Date de traitement</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $request->processed_at->format('d/m/Y à H:i') }}</dd>
                            </div>
                        @endif

                        @if($request->completed_at)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase">Date de finalisation</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $request->completed_at->format('d/m/Y à H:i') }}</dd>
                            </div>
                        @endif

                        @if($request->processedBy)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase">Traité par</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $request->processedBy->name }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if($request->reason)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <dt class="text-xs font-medium text-gray-500 uppercase mb-2">Raison fournie par le client</dt>
                            <dd class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">{{ $request->reason }}</dd>
                        </div>
                    @endif

                    @if($request->admin_notes)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <dt class="text-xs font-medium text-gray-500 uppercase mb-2">Notes administrateur</dt>
                            <dd class="text-sm text-gray-700 bg-yellow-50 p-3 rounded-lg border border-yellow-200">{{ $request->admin_notes }}</dd>
                        </div>
                    @endif
                </div>

                {{-- Informations export --}}
                @if($request->type === 'export' && $request->export_file_path)
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-sm font-bold text-gray-900 mb-4">Fichier d'export</h3>

                        <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-gray-600 rounded-lg">
                                    <x-lucide-file-json class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">Données exportées (JSON)</div>
                                    <div class="text-xs text-gray-600 mt-0.5">
                                        Expire le {{ $request->export_expires_at->format('d/m/Y à H:i') }}
                                    </div>
                                </div>
                            </div>

                            @if($request->isExportAvailable())
                                <span class="text-xs text-green-700 font-medium">Disponible</span>
                            @else
                                <span class="text-xs text-red-700 font-medium">Expiré</span>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Informations suppression --}}
                @if($request->data_deleted && $request->deleted_data_summary)
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-sm font-bold text-gray-900 mb-4">Résumé de suppression</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                <div class="text-xs font-medium text-red-900 uppercase mb-1">Tables supprimées</div>
                                <div class="text-2xl font-bold text-red-900">{{ $request->deleted_data_summary['total_deleted'] ?? 0 }}</div>
                                <div class="text-xs text-red-700 mt-2">
                                    {{ implode(', ', $request->deleted_data_summary['deleted_tables'] ?? []) }}
                                </div>
                            </div>

                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <div class="text-xs font-medium text-yellow-900 uppercase mb-1">Tables anonymisées</div>
                                <div class="text-2xl font-bold text-yellow-900">{{ $request->deleted_data_summary['total_anonymized'] ?? 0 }}</div>
                                <div class="text-xs text-yellow-700 mt-2">
                                    {{ implode(', ', $request->deleted_data_summary['anonymized_tables'] ?? []) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="space-y-4">
                {{-- Actions de traitement --}}
                @if($request->status === 'pending')
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-sm font-bold text-gray-900 mb-4">Actions</h3>

                        @if($request->type === 'access')
                            <form action="{{ route('admin.settings.gdpr.process-access', $request) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center justify-center gap-2">
                                    <x-lucide-check class="w-4 h-4" />
                                    Marquer comme traité
                                </button>
                            </form>
                            <p class="text-xs text-gray-500 mt-2">
                                Les données sont déjà accessibles via le compte client
                            </p>
                        @endif

                        @if($request->type === 'export')
                            <form action="{{ route('admin.settings.gdpr.process-export', $request) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 flex items-center justify-center gap-2">
                                    <x-lucide-download class="w-4 h-4" />
                                    Générer l'export
                                </button>
                            </form>
                            <p class="text-xs text-gray-500 mt-2">
                                Génère un fichier JSON avec toutes les données du client (expire après 72h)
                            </p>
                        @endif

                        @if($request->type === 'deletion')
                            <form action="{{ route('admin.settings.gdpr.process-deletion', $request) }}" method="POST"
                                  onsubmit="return confirm('⚠️ ATTENTION : Cette action est irréversible et supprimera/anonymisera définitivement toutes les données du client. Êtes-vous absolument sûr ?');">
                                @csrf
                                <input type="hidden" name="confirm" value="1">
                                <button type="submit"
                                        class="w-full px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 flex items-center justify-center gap-2">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                    Supprimer les données
                                </button>
                            </form>
                            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-xs text-red-900 font-medium">⚠️ Action irréversible</p>
                                <p class="text-xs text-red-700 mt-1">
                                    Cette action supprimera/anonymisera définitivement les données
                                </p>
                            </div>
                        @endif

                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <button type="button"
                                    onclick="document.getElementById('reject-modal').classList.remove('hidden')"
                                    class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center justify-center gap-2">
                                <x-lucide-x class="w-4 h-4" />
                                Rejeter la demande
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Ajouter une note --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Ajouter une note</h3>

                    <form action="{{ route('admin.settings.gdpr.add-note', $request) }}" method="POST">
                        @csrf
                        <textarea name="note"
                                  rows="4"
                                  class="w-full rounded-lg border-gray-300 text-sm"
                                  placeholder="Ajouter des informations complémentaires...">{{ $request->admin_notes }}</textarea>
                        <button type="submit"
                                class="mt-3 w-full px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
                            Enregistrer la note
                        </button>
                    </form>
                </div>

                {{-- Informations client --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Informations client</h3>

                    <dl class="space-y-3">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Nom</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $request->customer->firstname }} {{ $request->customer->lastname }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $request->customer->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Membre depuis</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $request->customer->created_at->format('d/m/Y') }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('admin.customers.show', $request->customer) }}"
                       class="mt-4 block text-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                        Voir le profil client →
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de rejet --}}
    <div id="reject-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Rejeter la demande</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Veuillez fournir une raison pour le rejet de cette demande RGPD
                </p>

                <form action="{{ route('admin.settings.gdpr.reject', $request) }}" method="POST">
                    @csrf
                    <textarea name="reason"
                              rows="4"
                              required
                              class="w-full rounded-lg border-gray-300 text-sm"
                              placeholder="Raison du rejet..."></textarea>

                    <div class="flex gap-3 mt-4">
                        <button type="button"
                                onclick="document.getElementById('reject-modal').classList.add('hidden')"
                                class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                            Annuler
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">
                            Rejeter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.omersiaGdprRealtimeConfig = {
            requestId: @json($request->id),
        };
    </script>
    @vite(['packages/Admin/src/resources/js/gdpr-realtime.js'])
@endpush
