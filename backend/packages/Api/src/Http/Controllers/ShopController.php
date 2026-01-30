<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Omersia\Core\Models\Shop;

class ShopController
{
    /**
     * @OA\Get(
     *     path="/shop/info",
     *     summary="Get shop information",
     *     description="Returns the shop name, logo and other public information",
     *     operationId="getShopInfo",
     *     tags={"Shop"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Shop information",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="name", type="string", example="Ma Boutique"),
     *             @OA\Property(property="display_name", type="string", example="Ma Super Boutique"),
     *             @OA\Property(property="logo_url", type="string", nullable=true, example="http://localhost:8000/storage/logos/logo.png")
     *         )
     *     )
     * )
     */
    public function info(): JsonResponse
    {
        $shop = Shop::first();

        if (! $shop) {
            return response()->json([
                'name' => config('app.name', 'Omersia'),
                'display_name' => config('app.name', 'Omersia'),
                'logo_url' => null,
            ]);
        }

        return response()->json([
            'name' => $shop->name,
            'display_name' => $shop->display_name ?? $shop->name,
            'logo_url' => $shop->logo_path ? url('storage/'.$shop->logo_path) : null,
        ]);
    }
}
