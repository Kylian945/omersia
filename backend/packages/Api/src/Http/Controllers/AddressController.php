<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Api\Http\Requests\AddressStoreRequest;
use Omersia\Api\Http\Requests\AddressUpdateRequest;
use Omersia\Customer\Models\Address;
use OpenApi\Annotations as OA;

class AddressController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/addresses",
     *     summary="Liste des adresses du client connecté",
     *     tags={"Adresses"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des adresses",
     *
     *         @OA\JsonContent(ref="#/components/schemas/AddressListResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $addresses = Address::query()
            ->where('customer_id', $user->id)
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('is_default_billing')
            ->orderBy('label')
            ->get();

        return response()->json($addresses);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/addresses",
     *     summary="Créer une nouvelle adresse pour le client connecté",
     *     tags={"Adresses"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/AddressCreateRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Adresse créée",
     *
     *         @OA\JsonContent(ref="#/components/schemas/AddressDetailResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function store(AddressStoreRequest $request)
    {
        $user = $request->user();

        $data = $request->validated();

        // Si on définit une adresse par défaut, on retire le flag des autres
        if (! empty($data['is_default_shipping'])) {
            Address::where('customer_id', $user->id)->update(['is_default_shipping' => false]);
        }

        if (! empty($data['is_default_billing'])) {
            Address::where('customer_id', $user->id)->update(['is_default_billing' => false]);
        }

        $address = new Address($data);
        $address->customer_id = $user->id;
        $address->save();

        return response()->json($address, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/addresses/{id}",
     *     summary="Détail d'une adresse du client connecté",
     *     tags={"Adresses"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'adresse",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Adresse trouvée",
     *
     *         @OA\JsonContent(ref="#/components/schemas/AddressDetailResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Adresse non trouvée"
     *     )
     * )
     */
    public function show(int $id, Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $address = Address::findOrFail($id);

        $this->authorize('view', $address);

        return response()->json($address);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/addresses/{id}",
     *     summary="Mettre à jour une adresse du client connecté",
     *     tags={"Adresses"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'adresse",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/AddressUpdateRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Adresse mise à jour",
     *
     *         @OA\JsonContent(ref="#/components/schemas/AddressDetailResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Adresse non trouvée"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function update(int $id, AddressUpdateRequest $request)
    {
        $user = $request->user();

        $address = Address::findOrFail($id);

        $this->authorize('update', $address);

        $data = $request->validated();

        if (! empty($data['is_default_shipping'])) {
            Address::where('customer_id', $user->id)->update(['is_default_shipping' => false]);
        }

        if (! empty($data['is_default_billing'])) {
            Address::where('customer_id', $user->id)->update(['is_default_billing' => false]);
        }

        $address->update($data);

        return response()->json($address);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/addresses/{id}",
     *     summary="Supprimer une adresse du client connecté",
     *     tags={"Adresses"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'adresse",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Adresse supprimée"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Adresse non trouvée"
     *     )
     * )
     */
    public function destroy(int $id, Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $address = Address::findOrFail($id);

        $this->authorize('delete', $address);

        $address->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/addresses/{id}/default-shipping",
     *     summary="Définir l'adresse comme adresse de livraison par défaut",
     *     tags={"Adresses"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'adresse",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Adresse mise à jour",
     *
     *         @OA\JsonContent(ref="#/components/schemas/AddressDetailResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Adresse non trouvée"
     *     )
     * )
     */
    public function setDefaultShipping(int $id, Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $address = Address::findOrFail($id);

        $this->authorize('setDefault', $address);

        Address::where('customer_id', $user->id)->update(['is_default_shipping' => false]);
        $address->update(['is_default_shipping' => true]);

        return response()->json($address);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/addresses/{id}/default-billing",
     *     summary="Définir l'adresse comme adresse de facturation par défaut",
     *     tags={"Adresses"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'adresse",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Adresse mise à jour",
     *
     *         @OA\JsonContent(ref="#/components/schemas/AddressDetailResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Adresse non trouvée"
     *     )
     * )
     */
    public function setDefaultBilling(int $id, Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $address = Address::findOrFail($id);

        $this->authorize('setDefault', $address);

        Address::where('customer_id', $user->id)->update(['is_default_billing' => false]);
        $address->update(['is_default_billing' => true]);

        return response()->json($address);
    }

    /**
     * Validation commune pour création / mise à jour
     */
    protected function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'postcode' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'is_default_billing' => 'sometimes|boolean',
            'is_default_shipping' => 'sometimes|boolean',
        ]);
    }
}
