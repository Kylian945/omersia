<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Core\Models\Shop;
use OpenApi\Annotations as OA;

class EcommercePageApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/ecommerce-pages/{type}/{slug}",
     *     summary="Récupérer une page e-commerce par type et slug optionnel",
     *     tags={"Pages E-commerce"},
     *     security={{"api.key": {}}},
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         description="Type de page e-commerce",
     *
     *         @OA\Schema(type="string", example="collection")
     *     ),
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=false,
     *         description="Slug optionnel de la page",
     *
     *         @OA\Schema(type="string", example="summer-sale")
     *     ),
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string", default="fr", enum={"fr", "en"})
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Page trouvée",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", example="collection"),
     *             @OA\Property(property="slug", type="string", nullable=true, example="summer-sale"),
     *             @OA\Property(property="title", type="string", example="Soldes d'été"),
     *             @OA\Property(
     *                 property="content",
     *                 type="object",
     *                 @OA\Property(property="sections", type="array", @OA\Items(type="object"))
     *             )
     *         )
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
     *         description="Page non trouvée",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Page not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ServerError")
     *     )
     * )
     */
    public function show(Request $request, string $type, ?string $slug = null)
    {
        $locale = $request->get('locale', 'fr');
        $shop = Shop::firstOrFail();

        $query = EcommercePage::where('shop_id', $shop->id)
            ->where('type', $type)
            ->where('is_active', true);

        if ($slug) {
            $query->where('slug', $slug);
        } else {
            $query->whereNull('slug');
        }

        $page = $query->with(['translations' => function ($q) use ($locale) {
            $q->where('locale', $locale);
        }])->first();

        if (! $page) {
            return response()->json([
                'error' => 'Page not found',
            ], 404);
        }

        $translation = $page->translations->first();

        return response()->json([
            'id' => $page->id,
            'type' => $page->type,
            'slug' => $page->slug,
            'title' => $translation?->title ?? '',
            'content' => $translation?->content_json ?? ['sections' => []],
        ]);
    }

    /**
     * Récupérer une page e-commerce par slug
     *
     * @OA\Get(
     *     path="/api/v1/ecommerce-pages/{slug}",
     *     summary="Récupérer une page e-commerce par slug",
     *     tags={"Pages E-commerce"},
     *     security={{"api.key": {}}},
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug de la page",
     *
     *         @OA\Schema(type="string", example="about-us")
     *     ),
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         required=false,
     *         description="Langue de la page",
     *
     *         @OA\Schema(type="string", default="fr", enum={"fr", "en"})
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Page trouvée",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", example="custom"),
     *             @OA\Property(property="slug", type="string", example="about-us"),
     *             @OA\Property(property="title", type="string", example="À propos de nous"),
     *             @OA\Property(
     *                 property="content",
     *                 type="object",
     *                 description="Contenu JSON de la page (sections du page builder)",
     *                 @OA\Property(
     *                     property="sections",
     *                     type="array",
     *
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
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
     *         description="Page non trouvée",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="error", type="string", example="Page not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ServerError")
     *     )
     * )
     */
    public function getBySlug(Request $request, string $slug)
    {
        $locale = $request->get('locale', 'fr');
        $shop = Shop::firstOrFail();

        $page = EcommercePage::where('shop_id', $shop->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            }])
            ->first();

        if (! $page) {
            return response()->json([
                'error' => 'Page not found',
            ], 404);
        }

        $translation = $page->translations->first();

        return response()->json([
            'id' => $page->id,
            'type' => $page->type,
            'slug' => $page->slug,
            'title' => $translation?->title ?? '',
            'content' => $translation?->content_json ?? ['sections' => []],
        ]);
    }
}
