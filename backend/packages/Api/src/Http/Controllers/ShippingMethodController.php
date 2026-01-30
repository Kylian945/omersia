<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Catalog\Models\ShippingMethod;
use OpenApi\Annotations as OA;

class ShippingMethodController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/shipping-methods",
     *     summary="Liste des méthodes de livraison disponibles",
     *     tags={"Livraison"},
     *     security={{"api.key": {}}},
     *
     *     @OA\Parameter(
     *         name="cart_total",
     *         in="query",
     *         required=false,
     *         description="Montant total du panier pour calcul de livraison gratuite",
     *
     *         @OA\Schema(type="number", format="float", example=59.98)
     *     ),
     *
     *     @OA\Parameter(
     *         name="weight",
     *         in="query",
     *         required=false,
     *         description="Poids total du panier",
     *
     *         @OA\Schema(type="number", format="float", example=2.5)
     *     ),
     *
     *     @OA\Parameter(
     *         name="country_code",
     *         in="query",
     *         required=false,
     *         description="Code pays de livraison",
     *
     *         @OA\Schema(type="string", example="FR")
     *     ),
     *
     *     @OA\Parameter(
     *         name="postal_code",
     *         in="query",
     *         required=false,
     *         description="Code postal de livraison",
     *
     *         @OA\Schema(type="string", example="75001")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des méthodes de livraison",
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
     *                     @OA\Property(property="code", type="string", example="standard"),
     *                     @OA\Property(property="name", type="string", example="Livraison standard"),
     *                     @OA\Property(property="description", type="string", nullable=true, example="Livraison en 3-5 jours ouvrés"),
     *                     @OA\Property(property="price", type="number", format="float", example=5.00),
     *                     @OA\Property(property="original_price", type="number", format="float", example=5.00),
     *                     @OA\Property(property="delivery_time", type="string", nullable=true, example="3-5 jours"),
     *                     @OA\Property(property="is_free", type="boolean", example=false),
     *                     @OA\Property(property="has_advanced_pricing", type="boolean", example=false),
     *                     @OA\Property(property="free_shipping_threshold", type="number", format="float", nullable=true, example=100.00)
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
    public function index(Request $request)
    {
        // Récupérer les paramètres pour le calcul du prix
        $cartTotal = $request->input('cart_total', 0);
        $weight = $request->input('weight');
        $countryCode = $request->input('country_code');
        $postalCode = $request->input('postal_code');

        $methods = ShippingMethod::query()
            ->where('is_active', true)
            ->with(['zones', 'rates'])
            ->get()
            ->map(function ($method) use ($cartTotal, $weight, $countryCode, $postalCode) {
                // Calculer le prix en fonction des règles avancées
                $calculatedPrice = $method->calculatePrice(
                    (float) $cartTotal,
                    $weight ? (float) $weight : null,
                    $countryCode,
                    $postalCode
                );

                return [
                    'id' => $method->id,
                    'code' => $method->code,
                    'name' => $method->name,
                    'description' => $method->description,
                    'price' => $calculatedPrice,
                    'original_price' => (float) $method->price,
                    'delivery_time' => $method->delivery_time,
                    'is_free' => $calculatedPrice == 0,
                    'has_advanced_pricing' => $method->use_weight_based_pricing || $method->use_zone_based_pricing,
                    'free_shipping_threshold' => $method->free_shipping_threshold ? (float) $method->free_shipping_threshold : null,
                ];
            })
            ->sortBy('price')
            ->values();

        return response()->json([
            'ok' => true,
            'data' => $methods,
        ]);
    }
}
