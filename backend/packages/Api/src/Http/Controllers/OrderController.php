<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Payments\PaymentProviderManager;
use App\Payments\Providers\StripePaymentProvider;
use Illuminate\Http\Request;
use Omersia\Api\DTO\OrderCreateDTO;
use Omersia\Api\DTO\OrderUpdateDTO;
use Omersia\Api\Exceptions\PriceTamperingException;
use Omersia\Api\Services\OrderCreationService;
use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\ShippingMethod;
use Omersia\Payment\Models\Payment;
use OpenApi\Annotations as OA;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderCreationService $orderCreationService,
        private readonly PaymentProviderManager $paymentProviderManager
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
     *         description="Liste des commandes confirmées du client",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *                 type="object",
     *
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="number", type="string", example="ORD-2024-00123"),
     *                 @OA\Property(property="status", type="string", enum={"pending", "processing", "shipped", "completed", "cancelled"}, example="pending"),
     *                 @OA\Property(property="total", type="number", format="float", example=76.97),
     *                 @OA\Property(property="placed_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="items_count", type="integer", example=3)
     *             )
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

        // DCA-012: Valider les prix côté serveur (via OrderCreationService)
        try {
            $order = $this->orderCreationService->createOrUpdateDraftOrder($dto, $shippingMethod);
        } catch (PriceTamperingException $e) {
            return response()->json([
                'error' => 'validation_error',
                'message' => 'Les prix soumis ne correspondent pas aux prix réels. Veuillez rafraîchir votre panier.',
            ], 422);
        }

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
            $mainImage = $item->product ? $item->product->mainImage : null;
            $item->image_url = $mainImage ? $mainImage->path : null;

            return $item;
        });

        return response()->json($order);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/orders/{order}",
     *     summary="Mettre à jour une commande brouillon (checkout)",
     *     tags={"Commandes"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID de la commande",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"currency", "shipping_method_id", "customer_email", "shipping_address", "billing_address", "items", "discount_total", "shipping_total", "tax_total", "total"},
     *
     *             @OA\Property(property="currency", type="string", example="EUR"),
     *             @OA\Property(property="shipping_method_id", type="integer", example=1),
     *             @OA\Property(property="customer_email", type="string", format="email", example="customer@example.com"),
     *             @OA\Property(property="customer_firstname", type="string", nullable=true, example="John"),
     *             @OA\Property(property="customer_lastname", type="string", nullable=true, example="Doe"),
     *             @OA\Property(property="shipping_address", type="object", ref="#/components/schemas/OrderAddress"),
     *             @OA\Property(property="billing_address", type="object", ref="#/components/schemas/OrderAddress"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="discount_total", type="number", format="float", example=0.00),
     *             @OA\Property(property="shipping_total", type="number", format="float", example=5.00),
     *             @OA\Property(property="tax_total", type="number", format="float", example=11.99),
     *             @OA\Property(property="total", type="number", format="float", example=76.97)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Commande mise à jour",
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
     *         response=403,
     *         description="Non autorisé (commande d'un autre client)",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
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
    public function update(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $this->authorize('update', $order);

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

        // DCA-003: Valider les prix côté serveur (via OrderCreationService)
        try {
            $order = $this->orderCreationService->updateOrder($order, $dto);
        } catch (PriceTamperingException $e) {
            \Log::warning('Price validation failed in update', [
                'field' => $e->field,
                'submitted' => $e->submitted,
                'expected' => $e->expected,
                'message' => $e->getMessage(),
                'order_id' => $order->id,
            ]);

            return response()->json([
                'error' => 'validation_error',
                'message' => 'Les prix soumis ne correspondent pas aux prix réels. Veuillez rafraîchir votre panier.',
            ], 422);
        }

        return response()->json($order);
    }

    /**
     * Confirmer une commande brouillon (appelé après paiement réussi)
     *
     * @OA\Post(
     *     path="/api/v1/orders/{id}/confirm",
     *     summary="Confirmer une commande brouillon après paiement réussi",
     *     tags={"Commandes"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la commande",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Commande confirmée ou déjà confirmée",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Commande confirmée avec succès"),
     *             @OA\Property(property="order", ref="#/components/schemas/Order")
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
    public function confirmOrder(Request $request, string $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'payment_intent_id' => ['nullable', 'string'],
        ]);

        $order = Order::where('id', $id)
            ->where('customer_id', $user->id)
            ->firstOrFail();

        if ($order->isDraft()) {
            if ($order->payment_status !== 'paid') {
                $intentId = $validated['payment_intent_id'] ?? null;

                $paymentQuery = Payment::where('order_id', $order->id)
                    ->where('provider_code', 'stripe');

                if (is_string($intentId) && trim($intentId) !== '') {
                    $payment = (clone $paymentQuery)
                        ->where('provider_payment_id', $intentId)
                        ->first();
                } else {
                    $payment = (clone $paymentQuery)
                        ->orderByDesc('id')
                        ->first();
                    $intentId = $payment?->provider_payment_id;
                }

                if (! $payment || ! is_string($intentId) || trim($intentId) === '') {
                    return response()->json([
                        'message' => 'Paiement Stripe introuvable pour cette commande.',
                    ], 422);
                }

                $provider = $this->paymentProviderManager->resolve('stripe');

                if (! ($provider instanceof StripePaymentProvider)) {
                    return response()->json([
                        'message' => 'Provider Stripe indisponible.',
                    ], 500);
                }

                $provider->syncPaymentIntent($intentId);
                $order->refresh();

                if ($order->payment_status !== 'paid') {
                    return response()->json([
                        'message' => 'Le paiement Stripe n’est pas encore confirmé.',
                    ], 422);
                }
            }

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
