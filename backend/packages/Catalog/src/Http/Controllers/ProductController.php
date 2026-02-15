<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Controllers;

use App\Events\Realtime\ProductStockUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Omersia\Catalog\DTO\ProductCreateDTO;
use Omersia\Catalog\DTO\ProductUpdateDTO;
use Omersia\Catalog\Http\Requests\ProductStoreRequest;
use Omersia\Catalog\Http\Requests\ProductUpdateRequest;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductImage;
use Omersia\Catalog\Services\ProductCreationService;
use Omersia\Catalog\Services\ProductImageService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductCreationService $productCreationService,
        private readonly ProductImageService $productImageService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('products.view');

        $search = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->with([
                'translations' => function ($query) {
                    $query->where('locale', 'fr');
                },
                'images',
                'mainImage',
                'categories',
            ])
            ->withCount('variants')
            ->withSum(
                'variants as variants_stock_qty',
                'stock_qty'
            )
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

                $query->where(function ($query) use ($like) {
                    $query->where('sku', 'like', $like)
                        ->orWhereHas('translations', function ($query) use ($like) {
                            $query->where('locale', 'fr')
                                ->where('name', 'like', $like);
                        });
                });
            })
            ->paginate(25)
            ->withQueryString();

        return view('admin::products.index', compact('products'));
    }

    public function create()
    {
        $this->authorize('products.create');

        $categories = Category::with(['translations' => function ($q) {
            $q->where('locale', 'fr');
        }])
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $relatedProducts = Product::with(['translations' => function ($q) {
            $q->where('locale', 'fr');
        }])
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('admin::products.create', compact('categories', 'relatedProducts'));
    }

    public function store(ProductStoreRequest $request)
    {
        $this->authorize('products.create');

        $validated = $request->validated();

        // Créer le DTO depuis les données validées
        $dto = ProductCreateDTO::fromArray($validated);

        // Créer le produit via le service (avec transaction)
        $product = $this->productCreationService->createProduct($dto, $request);
        event(ProductStockUpdated::fromModel($product));

        return redirect()->route('products.index')
            ->with('success', 'Produit créé avec succès.');
    }

    public function edit(Product $product)
    {
        $this->authorize('products.update');

        // On charge toutes les relations nécessaires pour le form Alpine
        $product->load([
            // Traduction FR du produit
            'translations' => function ($q) {
                $q->where('locale', 'fr');
            },

            // Images existantes
            'images',

            // Options et leurs valeurs (pour pré-remplir le builder)
            'options.values',

            // Variantes + leurs valeurs + l'option associée (Taille / Couleur...)
            'variants.values.option',

            // Catégories liées au produit (utile si besoin dans la vue)
            'categories.translations' => function ($q) {
                $q->where('locale', 'fr');
            },

            // Produits associés déjà liés (pour les cases pré-cochées)
            'relatedProducts.translations' => function ($q) {
                $q->where('locale', 'fr');
            },
        ]);

        // Liste des catégories dispo
        $categories = Category::with([
            'translations' => function ($q) {
                $q->where('locale', 'fr');
            },
        ])
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        // Suggestions de produits associés (hors produit courant)
        $relatedProducts = Product::with([
            'translations' => function ($q) {
                $q->where('locale', 'fr');
            },
        ])
            ->where('id', '<>', $product->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('admin::products.edit', compact('product', 'categories', 'relatedProducts'));
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $this->authorize('products.update');

        $validated = $request->validated();

        // Créer le DTO depuis les données validées
        $dto = ProductUpdateDTO::fromArray($validated);

        // Mettre à jour le produit via le service (avec transaction)
        $product = $this->productCreationService->updateProduct($product, $dto, $request);
        event(ProductStockUpdated::fromModel($product));

        return redirect()
            ->route('products.edit', $product)
            ->with('success', 'Produit mis à jour avec succès.');
    }

    // Optionnel: endpoint pour changer l'image principale
    public function setMainImage(Product $product, ProductImage $image)
    {
        $this->authorize('products.update');

        try {
            $this->productImageService->changeMainImage($product, $image);

            return back()->with('success', 'Image principale mise à jour');
        } catch (\InvalidArgumentException $e) {
            abort(404);
        }
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('products.update');

        try {
            $this->productImageService->deleteImage($product, $image);

            return back()->with('success', 'Image supprimée avec succès.');
        } catch (\InvalidArgumentException $e) {
            abort(404);
        }
    }

    /**
     * API endpoint for products list (for builder)
     * DCA-004: Ajout pagination pour éviter chargement illimité
     */
    public function apiList(Request $request)
    {
        $this->authorize('products.view');

        $perPage = min((int) $request->query('per_page', 50), 100); // Max 100
        $search = trim((string) $request->query('q', ''));

        $query = Product::with(['translations' => function ($query) {
            $query->where('locale', 'fr');
        }, 'images' => function ($query) {
            $query->where('is_main', true);
        }])
            ->where('is_active', true);

        // Recherche optionnelle
        if ($search !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(function ($q) use ($like) {
                $q->where('sku', 'like', $like)
                    ->orWhereHas('translations', function ($q) use ($like) {
                        $q->where('locale', 'fr')->where('name', 'like', $like);
                    });
            });
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage);

        $products = $paginator->getCollection()->map(function ($product) {
            $translation = $product->translations->first();
            $mainImage = $product->images->first();
            $name = ($translation && is_string($translation->name) && $translation->name !== '')
                ? $translation->name
                : 'Sans nom';
            $slug = ($translation && is_string($translation->slug))
                ? $translation->slug
                : '';

            return [
                'id' => $product->id,
                'name' => $name,
                'slug' => $slug,
                'price' => $product->price,
                'image' => $mainImage ? asset('storage/'.$mainImage->path) : null,
            ];
        });

        return response()->json([
            'data' => $products,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
