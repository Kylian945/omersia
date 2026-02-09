<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\CMS\Models\Page;
use OpenApi\Annotations as OA;

class PageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/pages",
     *     summary="Liste des pages actives",
     *     tags={"Pages"},
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string", default="fr")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des pages",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="is_home", type="boolean")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $locale = $request->get('locale', app()->getLocale());

        $pages = Page::query()
            ->where('is_active', true)
            ->with(['translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            }])
            ->get()
            ->map(function (Page $page) {
                $t = $page->translations->first();

                return [
                    'id' => $page->id,
                    'slug' => $t?->slug,
                    'title' => $t?->title,
                    'type' => $page->type,
                    'is_home' => (bool) $page->is_home,
                ];
            });

        return response()->json($pages);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pages/{slug}",
     *     summary="Détail d'une page CMS",
     *     tags={"Pages"},
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string", default="fr")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Page trouvée",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="meta_title", type="string"),
     *             @OA\Property(property="meta_description", type="string"),
     *             @OA\Property(property="layout", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Page non trouvée")
     * )
     */
    public function show(string $slug, Request $request)
    {
        $locale = $request->get('locale', app()->getLocale());

        $page = Page::query()
            ->where('is_active', true)
            ->whereHas('translations', fn ($q) => $q->where('locale', $locale)->where('slug', $slug))
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->firstOrFail();

        $t = $page->translations->first();

        return response()->json([
            'id' => $page->id,
            'slug' => $t?->slug,
            'title' => $t?->title,
            'meta_title' => $t?->meta_title,
            'meta_description' => $t?->meta_description,
            // 👇 le builder admin sauve la structure complète ici
            'layout' => $t?->content_json ?? ['sections' => []],
        ]);
    }
}
