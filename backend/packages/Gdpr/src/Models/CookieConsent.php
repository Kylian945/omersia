<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Omersia\Customer\Models\Customer;

/**
 * @property int $id
 * @property mixed $customer_id
 * @property mixed $session_id
 * @property mixed $ip_address
 * @property mixed $user_agent
 * @property bool $necessary
 * @property bool $functional
 * @property bool $analytics
 * @property bool $marketing
 * @property mixed $consent_version
 * @property \Illuminate\Support\Carbon|null $consented_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property-read Customer|null $customer
 */
class CookieConsent extends Model
{
    protected $fillable = [
        'customer_id',
        'session_id',
        'ip_address',
        'user_agent',
        'necessary',
        'functional',
        'analytics',
        'marketing',
        'consent_version',
        'consented_at',
        'expires_at',
    ];

    protected $casts = [
        'necessary' => 'boolean',
        'functional' => 'boolean',
        'analytics' => 'boolean',
        'marketing' => 'boolean',
        'consented_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Vérifier si le consentement est expiré (RGPD: 13 mois max)
     */
    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Obtenir le dernier consentement valide pour un customer
     */
    public static function getLatestForCustomer(int $customerId): ?self
    {
        return self::where('customer_id', $customerId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('consented_at')
            ->first();
    }

    /**
     * Obtenir le dernier consentement valide pour une session
     */
    public static function getLatestForSession(string $sessionId): ?self
    {
        return self::where('session_id', $sessionId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('consented_at')
            ->first();
    }

    /**
     * Obtenir le dernier consentement valide pour une adresse IP
     */
    public static function getLatestForIp(string $ipAddress): ?self
    {
        return self::where('ip_address', $ipAddress)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('consented_at')
            ->first();
    }
}
