<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Services;

use App\Events\Realtime\GdprRequestUpdated;
use Omersia\Gdpr\DTO\DataRequestDTO;
use Omersia\Gdpr\Models\DataRequest;

/**
 * Service pour gérer les demandes d'accès/suppression/rectification des données (DSAR)
 */
class DataRequestService
{
    public function __construct(
        private readonly DataExportService $dataExportService,
        private readonly DataDeletionService $dataDeletionService
    ) {}

    /**
     * Créer une nouvelle demande RGPD
     */
    public function createRequest(DataRequestDTO $dto): DataRequest
    {
        $request = DataRequest::create($dto->toArray());
        $request->refresh();
        event(GdprRequestUpdated::fromModel($request));

        return $request;
    }

    /**
     * Traiter une demande d'accès aux données
     */
    public function processAccessRequest(DataRequest $request, int $userId): void
    {
        $request->markAsProcessing($userId);
        $request->refresh();
        event(GdprRequestUpdated::fromModel($request));

        // Les données sont déjà accessibles via le compte client
        // On marque juste comme complété
        $request->markAsCompleted();
        $request->refresh();
        event(GdprRequestUpdated::fromModel($request));
    }

    /**
     * Traiter une demande d'export de données
     */
    public function processExportRequest(DataRequest $request, int $userId): void
    {
        $request->markAsProcessing($userId);
        $request->refresh();
        event(GdprRequestUpdated::fromModel($request));

        // Générer le fichier d'export
        $this->dataExportService->generateExportFile($request);

        $request->markAsCompleted();
        $request->refresh();
        event(GdprRequestUpdated::fromModel($request));
    }

    /**
     * Traiter une demande de suppression de données
     */
    public function processDeletionRequest(DataRequest $request, int $userId): void
    {
        $request->markAsProcessing($userId);
        $request->refresh();
        event(GdprRequestUpdated::fromModel($request));

        $customer = $request->customer;

        // Vérifier si la suppression est possible
        $check = $this->dataDeletionService->canDeleteCustomer($customer);

        if (! $check['can_delete']) {
            $reasons = implode(' ', $check['reasons']);
            $request->markAsRejected($reasons);
            $request->refresh();
            event(GdprRequestUpdated::fromModel($request));

            return;
        }

        // Supprimer les données
        $this->dataDeletionService->deleteCustomerData($customer, $request, $userId);

        $request->markAsCompleted();
        $request->refresh();
        event(GdprRequestUpdated::fromModel($request));
    }

    /**
     * Obtenir toutes les demandes en attente
     */
    public function getPendingRequests(): \Illuminate\Database\Eloquent\Collection
    {
        return DataRequest::pending()
            ->with('customer')
            ->orderBy('requested_at')
            ->get();
    }

    /**
     * Obtenir l'historique des demandes d'un customer
     */
    public function getCustomerRequests(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return DataRequest::where('customer_id', $customerId)
            ->orderByDesc('requested_at')
            ->get();
    }

    /**
     * Vérifier si un customer a déjà une demande en cours du même type
     */
    public function hasPendingRequest(int $customerId, string $type): bool
    {
        return DataRequest::where('customer_id', $customerId)
            ->where('type', $type)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
    }
}
