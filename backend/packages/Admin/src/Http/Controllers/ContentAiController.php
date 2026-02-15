<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Omersia\Admin\Http\Requests\GenerateContentAiRequest;
use Omersia\Ai\Exceptions\AiGenerationException;
use Omersia\Ai\Services\ContentGenerationService;
use Throwable;

class ContentAiController extends Controller
{
    public function generate(
        GenerateContentAiRequest $request,
        ContentGenerationService $generator
    ): JsonResponse {
        try {
            $payload = $request->safe()->only([
                'prompt',
                'context',
                'target_field',
                'name',
                'title',
                'description',
                'meta_title',
                'meta_description',
                'slug',
                'type',
                'locale',
            ]);

            $generated = $generator->generate($payload);

            return response()->json([
                'data' => $generated,
            ]);
        } catch (AiGenerationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'La génération IA a échoué. Vérifie la configuration provider/modèle puis réessaie.',
            ], 500);
        }
    }
}
