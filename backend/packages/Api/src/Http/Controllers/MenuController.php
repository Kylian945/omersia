<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Apparence\Models\Menu;
use OpenApi\Annotations as OA;

class MenuController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/menus/{slug}",
     *     summary="Récupérer un menu par son slug",
     *     description="Retourne un menu actif et ses items.
     *          Les items de type `category` exposent la catégorie liée ainsi que ses sous-catégories jusqu'à 2 niveaux d'enfants (3 niveaux au total : parent > enfant > petit-enfant), selon la locale demandée.",
     *     operationId="getMenuBySlug",
     *     tags={"Menus"},
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug du menu (ex: main, footer, header)",
     *
     *         @OA\Schema(type="string", example="main")
     *     ),
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         required=false,
     *         description="Code langue pour les traductions des catégories (ex: fr, en). Si non fourni, fr est utilisé.",
     *
     *         @OA\Schema(type="string", example="fr")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Menu trouvé",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="slug",
     *                 type="string",
     *                 description="Slug du menu",
     *                 example="main"
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="Nom lisible du menu",
     *                 example="Menu principal"
     *             ),
     *             @OA\Property(
     *                 property="location",
     *                 type="string",
     *                 nullable=true,
     *                 description="Emplacement / type du menu (header, footer, etc.)",
     *                 example="header"
     *             ),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 description="Liste des éléments de menu",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="label",
     *                         type="string",
     *                         description="Label affiché dans la navigation",
     *                         example="Chemises"
     *                     ),
     *                     @OA\Property(
     *                         property="type",
     *                         type="string",
     *                         enum={"category","link","text"},
     *                         description="Type de l'item: category = lié à une catégorie, link = URL libre, text = texte seul",
     *                         example="category"
     *                     ),
     *                     @OA\Property(
     *                         property="url",
     *                         type="string",
     *                         nullable=true,
     *                         description="URL cible. Pour un type category, est générée automatiquement si absente (/categories/{slug}).",
     *                         example="/categories/chemises"
     *                     ),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         nullable=true,
     *                         description="Données de la catégorie liée si type = category",
     *                         @OA\Property(
     *                             property="id",
     *                             type="integer",
     *                             example=12
     *                         ),
     *                         @OA\Property(
     *                             property="slug",
     *                             type="string",
     *                             example="chemises"
     *                         ),
     *                         @OA\Property(
     *                             property="name",
     *                             type="string",
     *                             example="Chemises"
     *                         ),
     *                         @OA\Property(
     *                             property="children",
     *                             type="array",
     *                             description="Sous-catégories directes (niveau 2)",
     *
     *                             @OA\Items(
     *                                 type="object",
     *
     *                                 @OA\Property(
     *                                     property="id",
     *                                     type="integer",
     *                                     example=34
     *                                 ),
     *                                 @OA\Property(
     *                                     property="slug",
     *                                     type="string",
     *                                     example="chemises-homme"
     *                                 ),
     *                                 @OA\Property(
     *                                     property="name",
     *                                     type="string",
     *                                     example="Chemises homme"
     *                                 ),
     *                                 @OA\Property(
     *                                     property="children",
     *                                     type="array",
     *                                     description="Sous-catégories du niveau 2 (niveau 3, petites-filles)",
     *
     *                                     @OA\Items(
     *                                         type="object",
     *
     *                                         @OA\Property(
     *                                             property="id",
     *                                             type="integer",
     *                                             example=56
     *                                         ),
     *                                         @OA\Property(
     *                                             property="slug",
     *                                             type="string",
     *                                             example="chemises-homme-manches-longues"
     *                                         ),
     *                                         @OA\Property(
     *                                             property="name",
     *                                             type="string",
     *                                             example="Chemises manches longues"
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Menu non trouvé"
     *     )
     * )
     */
    public function show(string $slug, Request $request)
    {
        $locale = $request->get('locale', 'fr');

        $menu = Menu::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'items' => function ($q) use ($locale) {
                    $q->where('is_active', true)
                        ->orderBy('position')
                        ->orderBy('id')
                        ->with([
                            // Traduction de la catégorie liée
                            'category.translations' => function ($t) use ($locale) {
                                $t->where('locale', $locale);
                            },
                            // Niveau 2 : enfants directs
                            'category.children.translations' => function ($t) use ($locale) {
                                $t->where('locale', $locale);
                            },
                            // Niveau 3 : enfants des enfants
                            'category.children.children.translations' => function ($t) use ($locale) {
                                $t->where('locale', $locale);
                            },
                        ]);
                },
            ])
            ->firstOrFail();

        $items = $menu->items->map(function ($item) {
            $data = [
                'id' => $item->id,
                'label' => $item->label,
                'type' => $item->type,
                'url' => $item->url,
            ];

            if ($item->type === 'category' && $item->category) {
                $translation = $item->category->translations->first();
                $slug = $translation?->slug;

                $categoryData = [
                    'id' => $item->category->id,
                    'slug' => $slug,
                    'name' => $translation?->name,
                ];

                // Niveau 2 : enfants directs
                if ($item->category->relationLoaded('children') && $item->category->children->isNotEmpty()) {
                    $categoryData['children'] = $item->category->children->map(function ($child) {
                        $childTranslation = $child->translations->first();

                        $childData = [
                            'id' => $child->id,
                            'slug' => $childTranslation?->slug,
                            'name' => $childTranslation?->name,
                        ];

                        // Niveau 3 : enfants des enfants
                        if ($child->relationLoaded('children') && $child->children->isNotEmpty()) {
                            $childData['children'] = $child->children->map(function ($grandChild) {
                                $gcTranslation = $grandChild->translations->first();

                                return [
                                    'id' => $grandChild->id,
                                    'slug' => $gcTranslation?->slug,
                                    'name' => $gcTranslation?->name,
                                ];
                            })->values()->all();
                        }

                        return $childData;
                    })->values()->all();
                }

                $data['category'] = $categoryData;

                // URL sécurisée auto pour les catégories
                if (! $data['url'] && $slug) {
                    $data['url'] = '/categories/'.$slug;
                }
            }

            return $data;
        })->values();

        return response()->json([
            'slug' => $menu->slug,
            'name' => $menu->name,
            'location' => $menu->location,
            'items' => $items,
        ]);
    }
}
