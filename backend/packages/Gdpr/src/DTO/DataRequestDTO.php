<?php

declare(strict_types=1);

namespace Omersia\Gdpr\DTO;

/**
 * DTO pour créer une demande de données RGPD
 */
class DataRequestDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly string $type, // 'access', 'export', 'deletion', 'rectification'
        public readonly ?string $reason = null,
    ) {}

    /**
     * Créer depuis un tableau (ex: request validated)
     */
    public static function fromArray(array $data, int $customerId): self
    {
        return new self(
            customerId: $customerId,
            type: $data['type'],
            reason: $data['reason'] ?? null,
        );
    }

    /**
     * Retourne les données pour la création de la demande
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'type' => $this->type,
            'status' => 'pending',
            'reason' => $this->reason,
            'requested_at' => now(),
        ];
    }
}
