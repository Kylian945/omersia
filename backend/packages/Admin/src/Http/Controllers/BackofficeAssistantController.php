<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Omersia\Admin\Http\Requests\AskBackofficeAssistantRequest;
use Omersia\Ai\Exceptions\AiGenerationException;
use Omersia\Ai\Services\BackofficeAssistantService;
use Throwable;

class BackofficeAssistantController extends Controller
{
    public function chat(
        AskBackofficeAssistantRequest $request,
        BackofficeAssistantService $assistant
    ): JsonResponse {
        try {
            $payload = $request->safe()->only([
                'message',
                'history',
            ]);

            $result = $assistant->ask(
                message: (string) ($payload['message'] ?? ''),
                history: is_array($payload['history'] ?? null) ? $payload['history'] : []
            );

            return response()->json([
                'data' => $result,
            ]);
        } catch (AiGenerationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'L’assistant IA est momentanément indisponible. Réessaie dans un instant.',
            ], 500);
        }
    }
}
