<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Events\Realtime\GdprRequestUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Gdpr\Models\DataRequest;
use Omersia\Gdpr\Services\DataRequestService;

class GdprController extends Controller
{
    public function __construct(
        private readonly DataRequestService $dataRequestService
    ) {}

    /**
     * Page d'accueil GDPR - Liste toutes les demandes
     */
    public function index(Request $request)
    {
        $this->authorize('settings.view');

        $query = DataRequest::with(['customer', 'processedBy'])
            ->orderByDesc('requested_at');

        // Filtrer par statut si fourni
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtrer par type si fourni
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $requests = $query->paginate(20);

        // Statistiques
        $stats = [
            'pending_count' => DataRequest::where('status', 'pending')->count(),
            'processing_count' => DataRequest::where('status', 'processing')->count(),
            'completed_count' => DataRequest::where('status', 'completed')->count(),
            'total_count' => DataRequest::count(),
        ];

        return view('admin::settings.gdpr.index', compact('requests', 'stats'));
    }

    /**
     * Afficher les détails d'une demande
     */
    public function show(DataRequest $request)
    {
        $this->authorize('settings.view');

        $request->load(['customer', 'processedBy']);

        return view('admin::settings.gdpr.show', compact('request'));
    }

    /**
     * Traiter une demande d'accès
     */
    public function processAccess(DataRequest $request)
    {
        $this->authorize('settings.manage');

        if ($request->status !== 'pending') {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $this->dataRequestService->processAccessRequest($request, auth()->id());

        return redirect()
            ->route('admin.settings.gdpr.show', $request)
            ->with('success', 'Demande d\'accès traitée avec succès.');
    }

    /**
     * Traiter une demande d'export
     */
    public function processExport(DataRequest $request)
    {
        $this->authorize('settings.manage');

        if ($request->status !== 'pending') {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        try {
            $this->dataRequestService->processExportRequest($request, auth()->id());

            return redirect()
                ->route('admin.settings.gdpr.show', $request)
                ->with('success', 'Export généré avec succès. Le client peut maintenant télécharger ses données.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la génération de l\'export: '.$e->getMessage());
        }
    }

    /**
     * Traiter une demande de suppression
     */
    public function processDeletion(Request $request, DataRequest $dataRequest)
    {
        $this->authorize('settings.manage');

        if ($dataRequest->status !== 'pending') {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $validated = $request->validate([
            'confirm' => 'required|accepted',
        ], [
            'confirm.accepted' => 'Vous devez confirmer la suppression des données.',
        ]);

        try {
            $this->dataRequestService->processDeletionRequest($dataRequest, auth()->id());

            return redirect()
                ->route('admin.settings.gdpr.index')
                ->with('success', 'Données du client supprimées avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: '.$e->getMessage());
        }
    }

    /**
     * Rejeter une demande
     */
    public function reject(Request $request, DataRequest $dataRequest)
    {
        $this->authorize('settings.manage');

        if ($dataRequest->status !== 'pending') {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'Vous devez fournir une raison pour le rejet.',
        ]);

        $dataRequest->markAsRejected($validated['reason']);
        $dataRequest->refresh();
        event(GdprRequestUpdated::fromModel($dataRequest));

        return redirect()
            ->route('admin.settings.gdpr.show', $dataRequest)
            ->with('success', 'Demande rejetée.');
    }

    /**
     * Ajouter une note admin à une demande
     */
    public function addNote(Request $request, DataRequest $dataRequest)
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $dataRequest->update([
            'admin_notes' => $validated['note'],
        ]);
        $dataRequest->refresh();
        event(GdprRequestUpdated::fromModel($dataRequest));

        return back()->with('success', 'Note ajoutée avec succès.');
    }
}
