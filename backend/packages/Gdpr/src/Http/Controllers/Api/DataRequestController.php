<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Gdpr\DTO\DataRequestDTO;
use Omersia\Gdpr\Services\DataExportService;
use Omersia\Gdpr\Services\DataRequestService;

class DataRequestController extends Controller
{
    public function __construct(
        private readonly DataRequestService $dataRequestService,
        private readonly DataExportService $dataExportService
    ) {}

    /**
     * Lister les demandes RGPD du customer connecté
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $requests = $this->dataRequestService->getCustomerRequests($user->id);

        return response()->json($requests);
    }

    /**
     * Créer une nouvelle demande RGPD
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'type' => 'required|in:access,export,deletion,rectification',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Vérifier si une demande du même type est déjà en cours
        if ($this->dataRequestService->hasPendingRequest($user->id, $validated['type'])) {
            return response()->json([
                'message' => 'Vous avez déjà une demande de ce type en cours de traitement.',
            ], 422);
        }

        $dto = DataRequestDTO::fromArray($validated, $user->id);
        $dataRequest = $this->dataRequestService->createRequest($dto);

        return response()->json([
            'message' => 'Votre demande a été enregistrée et sera traitée dans les meilleurs délais.',
            'request' => $dataRequest,
        ], 201);
    }

    /**
     * Télécharger un export de données
     */
    public function download(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $dataRequest = \Omersia\Gdpr\Models\DataRequest::where('id', $id)
            ->where('customer_id', $user->id)
            ->where('type', 'export')
            ->where('status', 'completed')
            ->firstOrFail();

        if (! $dataRequest->isExportAvailable()) {
            return response()->json([
                'message' => 'Le fichier d\'export n\'est plus disponible ou a expiré.',
            ], 410);
        }

        $content = $this->dataExportService->getExportFileContent($dataRequest->export_file_path);

        if (! $content) {
            return response()->json([
                'message' => 'Le fichier d\'export est introuvable.',
            ], 404);
        }

        return response($content, 200)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="my_data_export.json"');
    }
}
