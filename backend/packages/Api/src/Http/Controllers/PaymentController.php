<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Payments\PaymentProviderManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Omersia\Catalog\Models\Order;
use Omersia\Payment\Models\PaymentProvider;
use OpenApi\Annotations as OA;

class PaymentController extends Controller
{
    protected PaymentProviderManager $providers;

    public function __construct(PaymentProviderManager $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payment-methods",
     *     summary="Liste des méthodes de paiement disponibles",
     *     tags={"Paiement"},
     *     security={{"api.key": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des méthodes de paiement actives",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Stripe"),
     *                     @OA\Property(property="code", type="string", example="stripe")
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
     *         response=500,
     *         description="Erreur serveur",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ServerError")
     *     )
     * )
     */
    public function getAvailableMethods()
    {
        $methods = PaymentProvider::where('enabled', true)
            ->select('id', 'name', 'code')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $methods,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/intent",
     *     summary="Créer une intention de paiement (Stripe)",
     *     tags={"Paiement"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/PaymentIntentRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment intent créé",
     *
     *         @OA\JsonContent(ref="#/components/schemas/PaymentIntentResponse")
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
    public function createIntent(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'provider' => ['required', 'string', 'in:stripe'], // pour l’instant
        ]);

        $order = Order::where('id', $data['order_id'])
            ->where('customer_id', $request->user()->id)
            ->firstOrFail();

        // ✅ utilise bien ton manager
        $provider = $this->providers->resolve($data['provider']);

        // 🧠 crée ou réutilise l'intent (idempotent)
        $result = $provider->createPaymentIntent($order);

        // DCA-014: Logger la création d'intention de paiement
        Log::channel('transactions')->info('Payment intent created', [
            'order_id' => $order->id,
            'order_number' => $order->number,
            'customer_id' => $order->customer_id,
            'customer_email' => $order->customer_email,
            'amount' => $order->total,
            'currency' => $order->currency,
            'provider' => $data['provider'],
            'payment_intent_id' => $result['client_secret'] ?? $result['id'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'data' => $result,
        ]);
    }
}
