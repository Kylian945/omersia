<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Api\Http\Requests\AccountProfileUpdateRequest;
use OpenApi\Annotations as OA;

class AccountProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/account/profile",
     *     summary="Récupérer le profil du client connecté",
     *     tags={"Compte"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profil du client",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProfileResponse")
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
    public function show(Request $request)
    {
        /** @var \Omersia\Customer\Models\Customer $user */
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/account/profile",
     *     summary="Mettre à jour le profil du client connecté",
     *     tags={"Compte"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/UpdateProfileRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProfileResponse")
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
    public function update(AccountProfileUpdateRequest $request)
    {
        /** @var \Omersia\Customer\Models\Customer $user */
        $user = $request->user();

        $data = $request->validated();

        $user->fill($data);
        $user->save();

        return response()->json([
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
    }
}
