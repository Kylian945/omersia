<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Omersia\Admin\Http\Requests\GenerateProductImageRequest;
use Omersia\Admin\Http\Requests\GenerateProductSeoRequest;
use Omersia\Ai\Exceptions\AiGenerationException;
use Omersia\Ai\Services\ProductImageGenerationService;
use Omersia\Ai\Services\ProductSeoGenerationService;
use Throwable;

class ProductAiController extends Controller
{
    public function generate(
        GenerateProductSeoRequest $request,
        ProductSeoGenerationService $generator
    ): JsonResponse {
        try {
            $payload = $request->safe()->only([
                'prompt',
                'target_field',
                'name',
                'short_description',
                'description',
                'meta_title',
                'meta_description',
                'categories',
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

    public function generateImage(
        GenerateProductImageRequest $request,
        ProductImageGenerationService $generator
    ): JsonResponse {
        try {
            $payload = $request->safe()->only([
                'prompt',
                'product_id',
                'source_image_ids',
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
                'message' => 'La génération d’image IA a échoué. Vérifie la configuration provider/modèle puis réessaie.',
            ], 500);
        }
    }
}
