<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Catalog\Services\TaxCalculator;
use OpenApi\Annotations as OA;

class TaxCalculatorController extends Controller
{
    protected TaxCalculator $taxCalculator;

    public function __construct(TaxCalculator $taxCalculator)
    {
        $this->taxCalculator = $taxCalculator;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/calculate-tax",
     *     summary="Calculer les taxes pour une commande",
     *     tags={"Taxes"},
     *     security={{"api.key": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/TaxCalculationRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Taxes calculées",
     *
     *         @OA\JsonContent(ref="#/components/schemas/TaxCalculationResponse")
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
    public function calculate(Request $request)
    {
        $data = $request->validate([
            'subtotal' => 'required|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'address' => 'required|array',
            'address.country' => 'required|string',
            'address.state' => 'nullable|string',
            'address.postal_code' => 'nullable|string',
        ]);

        $subtotal = $data['subtotal'];
        $shippingCost = $data['shipping_cost'] ?? 0;
        $address = $data['address'];

        $result = $this->taxCalculator->calculate(
            $subtotal,
            $address,
            $shippingCost
        );

        return response()->json([
            'tax_total' => $result['tax_total'],
            'tax_rate' => $result['tax_rate'],
            'tax_zone' => $result['tax_zone'] ? [
                'id' => $result['tax_zone']->id,
                'name' => $result['tax_zone']->name,
                'code' => $result['tax_zone']->code,
            ] : null,
            'breakdown' => $result['breakdown'],
        ]);
    }

    /**
     * Calculate included tax (extract tax from TTC price)
     *
     * @OA\Post(
     *     path="/api/v1/calculate-included-tax",
     *     summary="Extraire la taxe d'un prix TTC",
     *     tags={"Taxes"},
     *     security={{"api.key": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"price_including_tax", "address"},
     *
     *             @OA\Property(property="price_including_tax", type="number", format="float", example=119.98, description="Prix TTC"),
     *             @OA\Property(
     *                 property="address",
     *                 type="object",
     *                 required={"country"},
     *
     *                 @OA\Property(property="country", type="string", example="FR"),
     *                 @OA\Property(property="state", type="string", nullable=true, example="Île-de-France"),
     *                 @OA\Property(property="postal_code", type="string", nullable=true, example="75001")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Taxe extraite du prix TTC",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="tax_total", type="number", format="float", example=19.99),
     *             @OA\Property(property="tax_rate", type="number", format="float", example=20.00),
     *             @OA\Property(property="price_excluding_tax", type="number", format="float", example=99.99)
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
    public function calculateIncludedTax(Request $request)
    {
        $data = $request->validate([
            'price_including_tax' => 'required|numeric|min:0',
            'address' => 'required|array',
            'address.country' => 'required|string',
            'address.state' => 'nullable|string',
            'address.postal_code' => 'nullable|string',
        ]);

        $priceIncludingTax = $data['price_including_tax'];
        $address = $data['address'];

        $result = $this->taxCalculator->calculateIncludedTax(
            $priceIncludingTax,
            $address
        );

        return response()->json([
            'tax_total' => $result['tax_total'],
            'tax_rate' => $result['tax_rate'],
            'price_excluding_tax' => $result['price_excluding_tax'],
        ]);
    }
}
