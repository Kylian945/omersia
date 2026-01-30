<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Api\DTO\OrderCreateDTO;
use Omersia\Api\DTO\OrderUpdateDTO;
use Omersia\Api\Services\OrderCreationService;
use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\ShippingMethod;
use OpenApi\Annotations as OA;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderCreationService $orderCreationService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/orders",
     *     summary="Liste des commandes du client connecté",
     *     tags={"Commandes"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des commandes",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/Order")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
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
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Ne retourner que les commandes confirmées (pas les brouillons)
        $orders = Order::query()
            ->where('customer_id', $user->id)
            ->confirmed() // Utilise le scope pour exclure les drafts
            ->withCount('items')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'number',
                'status',
                'total',
                'placed_at',
            ]);

        return response()->json($orders);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders",
     *     summary="Créer une nouvelle commande",
     *     tags={"Commandes"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données de la commande",
     *
     *         @OA\JsonContent(
     *             required={"currency", "shipping_method_id", "customer_email", "shipping_address", "items"},
     *
     *             @OA\Property(property="currency", type="string", example="EUR"),
     *             @OA\Property(property="shipping_method_id", type="integer", example=1),
     *             @OA\Property(property="customer_email", type="string", example="customer@example.com"),
     *             @OA\Property(property="shipping_address", ref="#/components/schemas/OrderAddress"),
     *             @OA\Property(property="billing_address", ref="#/components/schemas/OrderAddress", nullable=true),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="variant_id", type="integer", nullable=true),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="quantity", type="integer"),
     *                     @OA\Property(property="unit_price", type="number")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Commande créée",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
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
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'cart_id' => 'nullable|integer|exists:carts,id',
            'currency' => 'required|string|size:3',

            'shipping_method_id' => 'required|integer|exists:shipping_methods,id',

            'customer_email' => 'required|email',
            'customer_firstname' => 'nullable|string|max:255',
            'customer_lastname' => 'nullable|string|max:255',

            'shipping_address' => 'required|array',
            'shipping_address.line1' => 'required|string|max:255',
            'shipping_address.line2' => 'nullable|string|max:255',
            'shipping_address.postcode' => 'required|string|max:20',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.country' => 'required|string|max:255',
            'shipping_address.phone' => 'nullable|string|max:30',

            'billing_address' => 'nullable|array',
            'billing_address.line1' => 'required_with:billing_address|string|max:255',
            'billing_address.line2' => 'nullable|string|max:255',
            'billing_address.postcode' => 'required_with:billing_address|string|max:20',
            'billing_address.city' => 'required_with:billing_address|string|max:255',
            'billing_address.country' => 'required_with:billing_address|string|max:255',
            'billing_address.phone' => 'nullable|string|max:30',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.name' => 'required|string|max:255',
            'items.*.sku' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',

            'discount_total' => 'nullable|numeric|min:0',
            'shipping_total' => 'nullable|numeric|min:0',
            'tax_total' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',

            'applied_discount_codes' => 'nullable|array',
            'applied_discount_codes.*' => 'string|max:255',
        ]);

        $shippingMethod = ShippingMethod::where('id', $validated['shipping_method_id'])
            ->where('is_active', true)
            ->firstOrFail();

        // Ajuster le shipping_total si non fourni
        $validated['shipping_total'] = $validated['shipping_total'] ?? $shippingMethod->price;

        $dto = OrderCreateDTO::fromArray($validated, $user->id);
        $order = $this->orderCreationService->createOrUpdateDraftOrder($dto, $shippingMethod);

        return response()->json($order, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{number}",
     *     summary="Détail d'une commande par numéro",
     *     tags={"Commandes"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="number",
     *         in="path",
     *         required=true,
     *         description="Numéro de commande",
     *
     *         @OA\Schema(type="string", example="ORD-2024-00123")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Détail de la commande",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Commande non trouvée",
     *
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
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
    public function show(string $number, Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::with(['items.product.mainImage', 'shippingMethod'])
            ->where('number', $number)
            ->where('customer_id', $user->id)
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $order->items->transform(function ($item) {
            $item->image_url = $item->product?->mainImage?->path ?? null;

            return $item;
        });

        return response()->json($order);
    }

    public function update(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Verify ownership to prevent IDOR (DCA-002)
        if ($order->customer_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'currency' => ['required', 'string'],
            'shipping_method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'customer_email' => ['required', 'email'],
            'customer_firstname' => ['nullable', 'string'],
            'customer_lastname' => ['nullable', 'string'],

            'shipping_address' => ['required', 'array'],
            'billing_address' => ['required', 'array'],

            'items' => ['required', 'array', 'min:1'],

            'discount_total' => ['required', 'numeric'],
            'shipping_total' => ['required', 'numeric'],
            'tax_total' => ['required', 'numeric'],
            'total' => ['required', 'numeric'],
        ]);

        $dto = OrderUpdateDTO::fromArray($validated);
        $order = $this->orderCreationService->updateOrder($order, $dto);

        return response()->json($order);
    }

    /**
     * Confirmer une commande brouillon (appelé après paiement réussi)
     */
    public function confirmOrder(Request $request, string $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('id', $id)
            ->where('customer_id', $user->id)
            ->firstOrFail();

        if ($order->isDraft()) {
            $order->confirm(); // Utilise la méthode du modèle

            return response()->json([
                'message' => 'Commande confirmée avec succès',
                'order' => $order->fresh(),
            ]);
        }

        return response()->json([
            'message' => 'La commande est déjà confirmée',
            'order' => $order,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{number}/invoice",
     *     summary="Télécharger la facture d'une commande",
     *     tags={"Commandes"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="number",
     *         in="path",
     *         required=true,
     *         description="Numéro de commande",
     *
     *         @OA\Schema(type="string", example="ORD-2024-00123")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Fichier PDF de la facture",
     *
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Commande ou facture non trouvée",
     *
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
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
    public function downloadInvoice(Request $request, string $number)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Trouver la commande par numéro
        $order = Order::where('number', $number)
            ->where('customer_id', $user->id)
            ->with('invoice')
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Commande introuvable'], 404);
        }

        // Vérifier que la commande a une facture
        if (! $order->invoice) {
            return response()->json(['message' => 'Aucune facture disponible pour cette commande'], 404);
        }

        // Télécharger le PDF
        $invoiceService = app(\App\Services\InvoiceService::class);
        $response = $invoiceService->downloadPdf($order->invoice);

        if (! $response) {
            return response()->json(['message' => 'Impossible de télécharger la facture'], 500);
        }

        return $response;
    }
}
