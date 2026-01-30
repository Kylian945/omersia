<?php

declare(strict_types=1);

namespace Omersia\Gdpr\DTO;

/**
 * DTO pour l'enregistrement d'un consentement cookie
 */
class CookieConsentDTO
{
    public function __construct(
        public readonly ?int $customerId,
        public readonly ?string $sessionId,
        public readonly string $ipAddress,
        public readonly string $userAgent,
        public readonly bool $necessary,
        public readonly bool $functional,
        public readonly bool $analytics,
        public readonly bool $marketing,
        public readonly string $consentVersion = '1.0',
    ) {}

    /**
     * Créer depuis un tableau (ex: request validated)
     */
    public static function fromArray(array $data, ?int $customerId = null, ?string $sessionId = null): self
    {
        return new self(
            customerId: $customerId,
            sessionId: $sessionId,
            ipAddress: $data['ip_address'] ?? request()->ip(),
            userAgent: $data['user_agent'] ?? request()->userAgent(),
            necessary: true, // Toujours true (cookies essentiels)
            functional: (bool) ($data['functional'] ?? false),
            analytics: (bool) ($data['analytics'] ?? false),
            marketing: (bool) ($data['marketing'] ?? false),
            consentVersion: $data['consent_version'] ?? '1.0',
        );
    }

    /**
     * Retourne les données pour la création du consentement
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'session_id' => $this->sessionId,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'necessary' => $this->necessary,
            'functional' => $this->functional,
            'analytics' => $this->analytics,
            'marketing' => $this->marketing,
            'consent_version' => $this->consentVersion,
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13), // RGPD: 13 mois max
        ];
    }
}
