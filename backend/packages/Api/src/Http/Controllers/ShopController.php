<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Omersia\Core\Models\Shop;

class ShopController
{
    /**
     * @OA\Get(
     *     path="/api/v1/shop/info",
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
        $data = Cache::tags(['shop'])->remember('shop.info', 86400, function () {
            $shop = Shop::first();

            if (! $shop) {
                return [
                    'name' => config('app.name', 'Omersia'),
                    'display_name' => config('app.name', 'Omersia'),
                    'logo_url' => null,
                ];
            }

            return [
                'name' => $shop->name,
                'display_name' => $shop->display_name ?? $shop->name,
                'logo_url' => $shop->logo_path ? url('storage/'.$shop->logo_path) : null,
            ];
        });

        return response()->json($data);
    }
}
