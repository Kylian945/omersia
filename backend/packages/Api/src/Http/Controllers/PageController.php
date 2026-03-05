<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Services\PageService;
use OpenApi\Annotations as OA;

class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pageService,
    ) {}

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
    public function index(Request $request): JsonResponse
    {
        $locale = $request->get('locale', app()->getLocale());
        $isAdmin = $this->isAdminContext($request);

        if ($isAdmin) {
            $pages = $this->mapPagesForResponse(
                $this->pageService->getPublicPages($locale, includeUnpublished: true)
            );

            return response()->json($pages);
        }

        $cacheKey = "pages.index.{$locale}.public";
        $pages = Cache::tags(['pages'])->remember($cacheKey, 1800, function () use ($locale) {
            return $this->mapPagesForResponse($this->pageService->getPublicPages($locale));
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
    public function show(string $slug, Request $request): JsonResponse
    {
        $locale = $request->get('locale', app()->getLocale());
        $isAdmin = $this->isAdminContext($request);

        if ($isAdmin) {
            $data = $this->buildPagePayload($slug, $locale, includeUnpublished: true);
            if ($data === null) {
                abort(404);
            }

            return response()->json($data);
        }

        $cacheKey = "page.show.{$slug}.{$locale}.public";
        $data = Cache::tags(['pages'])->remember($cacheKey, 1800, function () use ($slug, $locale) {
            return $this->buildPagePayload($slug, $locale);
        });

        if ($data === null) {
            abort(404);
        }

        return response()->json($data);
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @return array<int, array<string, mixed>>
     */
    private function mapPagesForResponse(Collection $pages): array
    {
        return $pages
            ->map(function (Page $page): array {
                $t = $page->translations->first();

                return [
                    'id' => $page->id,
                    'slug' => $t?->slug,
                    'title' => $t?->title,
                    'type' => $page->type,
                    'status' => $page->status,
                    'published_at' => $page->published_at?->toAtomString(),
                    'is_home' => (bool) $page->is_home,
                ];
            })->values()->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildPagePayload(string $slug, string $locale, bool $includeUnpublished = false): ?array
    {
        $page = $this->pageService->getPublicPage($slug, $locale, $includeUnpublished);

        if (! $page) {
            return null;
        }

        $t = $page->translations->first();
        $layout = ($t && is_array($t->content_json)) ? $t->content_json : ['sections' => []];

        return [
            'id' => $page->id,
            'slug' => $t?->slug,
            'title' => $t?->title,
            'meta_title' => $t?->meta_title,
            'meta_description' => $t?->meta_description,
            'status' => $page->status,
            'published_at' => $page->published_at?->toAtomString(),
            'layout' => $layout,
        ];
    }

    private function isAdminContext(Request $request): bool
    {
        $user = $request->user() ?? $request->user('sanctum') ?? auth('sanctum')->user();

        if (! $user instanceof Authenticatable) {
            return false;
        }

        $canViaGate = method_exists($user, 'can') && $user->can('pages.view');
        $canViaPermissionTrait = method_exists($user, 'hasPermission') && $user->hasPermission('pages.view');

        return $canViaGate || $canViaPermissionTrait;
    }
}
