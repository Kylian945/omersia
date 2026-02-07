<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Omersia\Api\Http\Requests\CustomerForgotPasswordRequest;
use Omersia\Api\Http\Requests\CustomerLoginRequest;
use Omersia\Api\Http\Requests\CustomerRegisterRequest;
use Omersia\Api\Http\Requests\CustomerResetPasswordRequest;
use Omersia\Api\Mail\CustomerPasswordResetMail;
use Omersia\Customer\Mail\WelcomeMail;
use Omersia\Customer\Models\Customer;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Créer un nouveau compte client",
     *     tags={"Authentification"},
     *     security={{"api.key": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *
     *         @OA\JsonContent(ref="#/components/schemas/LoginResponse")
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
    public function register(CustomerRegisterRequest $request)
    {
        $validated = $request->validated();

        $customer = Customer::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            // éventuellement:
            'shop_id' => 1,
            'is_active' => true,
        ]);

        // Envoi de l'email de bienvenue
        try {
            Mail::to($customer->email)->send(new WelcomeMail($customer));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email de bienvenue: '.$e->getMessage());
            // On continue le processus d'inscription même si l'email échoue
        }

        $token = $customer->createToken('storefront')->plainTextToken;

        return response()->json([
            'message' => 'Compte créé avec succès.',
            'user' => [
                'id' => $customer->id,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'email' => $customer->email,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Connexion client",
     *     tags={"Authentification"},
     *     security={{"api.key": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *
     *         @OA\JsonContent(ref="#/components/schemas/LoginResponse")
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
     *         description="Identifiants incorrects",
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
    public function login(CustomerLoginRequest $request)
    {
        $credentials = $request->validated();

        // On cherche dans la table customers
        /** @var \Omersia\Customer\Models\Customer|null $customer */
        $customer = Customer::where('email', $credentials['email'])->first();

        if (! $customer || ! Hash::check($credentials['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        // Optionnel : tu peux révoquer les anciens tokens si tu veux 1 token unique
        // $customer->tokens()->delete();

        $token = $customer->createToken('storefront')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'user' => [
                'id' => $customer->id,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'email' => $customer->email,
            ],
            'token' => $token,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Déconnexion client",
     *     tags={"Authentification"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MessageResponse")
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
    public function logout(Request $request)
    {
        $user = $request->user(); // Sanctum retourne ici un Customer

        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Déconnecté avec succès.',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="Informations du client connecté",
     *     tags={"Authentification"},
     *     security={{"api.key": {}, "sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Informations du client",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MeResponse")
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
    public function me(Request $request)
    {
        $user = $request->user(); // Customer via token Sanctum

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        return response()->json([
            'id' => $user->id,
            'firstname' => $user->firstname ?? null,
            'lastname' => $user->lastname ?? null,
            'email' => $user->email,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/password/forgot",
     *     summary="Demander un lien de réinitialisation de mot de passe",
     *     tags={"Authentification"},
     *     security={{"api.key": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ForgotPasswordRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Email envoyé (si l'adresse existe)",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MessageResponse")
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
    public function forgotPassword(CustomerForgotPasswordRequest $request)
    {
        $validated = $request->validated();

        // Vérifier si le customer existe
        $customer = Customer::where('email', $validated['email'])->first();

        if (! $customer) {
            // Pour des raisons de sécurité, on ne révèle pas si l'email existe ou non
            return response()->json([
                'message' => 'Si cette adresse existe, un email de réinitialisation a été envoyé.',
            ], 200);
        }

        // Générer un token de réinitialisation
        $token = Str::random(64);

        // Sauvegarder le token dans la table password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'email' => $validated['email'],
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Construire l'URL de réinitialisation pour le front
        $frontUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $resetUrl = $frontUrl.'/password/reset?token='.$token.'&email='.urlencode($validated['email']);

        // Envoyer l'email
        try {
            Mail::to($customer->email)->send(new CustomerPasswordResetMail($customer, $resetUrl));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email reset password: '.$e->getMessage());
            // On continue quand même pour ne pas révéler d'infos
        }

        return response()->json([
            'message' => 'Si cette adresse existe, un email de réinitialisation a été envoyé.',
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/password/reset",
     *     summary="Réinitialiser le mot de passe avec le token",
     *     tags={"Authentification"},
     *     security={{"api.key": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ResetPasswordRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe réinitialisé avec succès",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MessageResponse")
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
     *         response=404,
     *         description="Utilisateur non trouvé",
     *
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Token invalide ou expiré",
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
    public function resetPassword(CustomerResetPasswordRequest $request)
    {
        $validated = $request->validated();

        // Récupérer l'entrée de réinitialisation
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (! $resetRecord) {
            return response()->json([
                'message' => 'Le lien de réinitialisation est invalide ou a expiré.',
            ], 422);
        }

        // Vérifier que le token correspond
        if (! Hash::check($validated['token'], $resetRecord->token)) {
            return response()->json([
                'message' => 'Le lien de réinitialisation est invalide ou a expiré.',
            ], 422);
        }

        // Vérifier que le token n'a pas expiré (60 minutes par défaut)
        $expireMinutes = config('auth.passwords.customers.expire', 60);
        if (now()->diffInMinutes($resetRecord->created_at) > $expireMinutes) {
            DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->delete();

            return response()->json([
                'message' => 'Le lien de réinitialisation a expiré.',
            ], 422);
        }

        // Mettre à jour le mot de passe du customer
        $customer = Customer::where('email', $validated['email'])->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        $customer->password = Hash::make($validated['password']);
        $customer->save();

        // Supprimer le token utilisé
        DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->delete();

        // Optionnel : révoquer tous les tokens existants pour forcer une reconnexion
        $customer->tokens()->delete();

        return response()->json([
            'message' => 'Votre mot de passe a été réinitialisé avec succès.',
        ], 200);
    }
}
