<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Omersia\Apparence\Services\ThemeCustomizationService;
use Omersia\Core\Models\Shop;
use OpenApi\Annotations as OA;

class ThemeApiController
{
    public function __construct(
        protected ThemeCustomizationService $customizationService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/theme/settings",
     *     summary="Récupérer les paramètres du thème actif",
     *     tags={"Thème"},
     *     security={{"api.key": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paramètres du thème",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ThemeSettingsResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="API key invalide ou manquante",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ApiKeyError")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Shop non configuré",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Shop not configured")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Theme settings error"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function settings(): JsonResponse
    {
        try {
            $shop = Shop::first();

            if (! $shop) {
                return response()->json([
                    'error' => 'Shop not configured',
                ], 404);
            }

            $activeTheme = $shop->themes()->where('is_active', true)->first();

            $settings = $this->customizationService->getActiveThemeSettings($shop->id);

            $cssVariables = $this->customizationService->generateCssVariables($settings);

            // Get theme settings schema if available
            $settingsSchema = null;
            if ($activeTheme && $activeTheme->hasSettingsSchema()) {
                $settingsSchema = $activeTheme->getSettingsSchema();
            }

            return response()->json([
                'settings' => $settings,
                'settings_schema' => $settingsSchema,
                'css_variables' => $cssVariables,
                'component_path' => $activeTheme?->component_path ?? 'vision',
                'theme_slug' => $activeTheme?->slug ?? 'vision',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in ThemeApiController@settings', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Theme settings error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
