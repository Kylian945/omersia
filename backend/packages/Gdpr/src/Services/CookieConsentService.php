<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Services;

use Omersia\Gdpr\DTO\CookieConsentDTO;
use Omersia\Gdpr\Models\CookieConsent;

/**
 * Service pour gérer les consentements cookies (RGPD)
 */
class CookieConsentService
{
    /**
     * Enregistrer un nouveau consentement
     */
    public function recordConsent(CookieConsentDTO $dto): CookieConsent
    {
        return CookieConsent::create($dto->toArray());
    }

    /**
     * Obtenir le consentement actuel d'un customer
     * Si aucun consentement trouvé par customer_id ou session_id,
     * cherche par IP address pour éviter de redemander le consentement
     */
    public function getCurrentConsent(?int $customerId, ?string $sessionId, ?string $ipAddress = null): ?CookieConsent
    {
        // Priorité 1: Customer authentifié
        if ($customerId) {
            return CookieConsent::getLatestForCustomer($customerId);
        }

        // Priorité 2: Session active
        if ($sessionId) {
            $consent = CookieConsent::getLatestForSession($sessionId);
            if ($consent) {
                return $consent;
            }
        }

        // Priorité 3: Même IP address (fallback)
        // Permet de ne pas redemander le consentement si l'utilisateur revient
        // depuis la même IP même si sa session a expiré
        if ($ipAddress) {
            return CookieConsent::getLatestForIp($ipAddress);
        }

        return null;
    }

    /**
     * Vérifier si un type de cookie est autorisé
     */
    public function isCookieAllowed(string $type, ?int $customerId, ?string $sessionId, ?string $ipAddress = null): bool
    {
        // Les cookies nécessaires sont toujours autorisés
        if ($type === 'necessary') {
            return true;
        }

        $consent = $this->getCurrentConsent($customerId, $sessionId, $ipAddress);

        if (! $consent || $consent->isExpired()) {
            return false; // Pas de consentement = refus par défaut (RGPD)
        }

        return match ($type) {
            'functional' => $consent->functional,
            'analytics' => $consent->analytics,
            'marketing' => $consent->marketing,
            default => false,
        };
    }

    /**
     * Nettoyer les consentements expirés
     */
    public function cleanExpiredConsents(): int
    {
        return CookieConsent::where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Obtenir l'historique des consentements d'un customer
     */
    public function getConsentHistory(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return CookieConsent::where('customer_id', $customerId)
            ->orderByDesc('consented_at')
            ->get();
    }
}
