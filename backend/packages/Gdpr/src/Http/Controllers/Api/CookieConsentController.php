<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Gdpr\DTO\CookieConsentDTO;
use Omersia\Gdpr\Services\CookieConsentService;

class CookieConsentController extends Controller
{
    public function __construct(
        private readonly CookieConsentService $cookieConsentService
    ) {}

    /**
     * Authentifie l'utilisateur à partir du Bearer token si présent
     */
    private function authenticateFromToken(Request $request): mixed
    {
        if ($token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable_id !== null) {
                return $accessToken->tokenable;
            }
        }

        return null;
    }

    /**
     * Obtenir le consentement actuel
     */
    public function show(Request $request)
    {
        // Authentification manuelle via Sanctum si Bearer token présent
        $user = $this->authenticateFromToken($request);
        $sessionId = $user ? null : session()->getId();
        $ipAddress = $request->ip();

        $consent = $this->cookieConsentService->getCurrentConsent(
            $user?->id,
            $sessionId,
            $ipAddress
        );

        // Pas de consentement ou consentement expiré
        if (! $consent || $consent->isExpired()) {
            return response()->json([
                'has_consent' => false,
                'necessary' => true,
                'functional' => false,
                'analytics' => false,
                'marketing' => false,
            ]);
        }

        return response()->json([
            'has_consent' => true,
            'necessary' => $consent->necessary,
            'functional' => $consent->functional,
            'analytics' => $consent->analytics,
            'marketing' => $consent->marketing,
            'consented_at' => $consent->consented_at,
            'expires_at' => $consent->expires_at,
        ]);
    }

    /**
     * Enregistrer un nouveau consentement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'functional' => 'required|boolean',
            'analytics' => 'required|boolean',
            'marketing' => 'required|boolean',
        ]);

        // Authentification manuelle via Sanctum si Bearer token présent
        $user = $this->authenticateFromToken($request);
        $sessionId = $user ? null : session()->getId();

        $dto = CookieConsentDTO::fromArray(
            $validated,
            $user?->id,
            $sessionId
        );

        $consent = $this->cookieConsentService->recordConsent($dto);

        return response()->json([
            'message' => 'Consentement enregistré avec succès',
            'consent' => [
                'necessary' => $consent->necessary,
                'functional' => $consent->functional,
                'analytics' => $consent->analytics,
                'marketing' => $consent->marketing,
                'expires_at' => $consent->expires_at,
            ],
        ], 201);
    }

    /**
     * Vérifier si un type de cookie est autorisé
     */
    public function check(Request $request, string $type)
    {
        // Authentification manuelle via Sanctum si Bearer token présent
        $user = $this->authenticateFromToken($request);
        $sessionId = $user ? null : session()->getId();
        $ipAddress = $request->ip();

        $allowed = $this->cookieConsentService->isCookieAllowed(
            $type,
            $user?->id,
            $sessionId,
            $ipAddress
        );

        return response()->json([
            'type' => $type,
            'allowed' => $allowed,
        ]);
    }
}
