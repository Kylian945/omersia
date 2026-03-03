<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Customer\Models\Customer;
use Omersia\Gdpr\DTO\CookieConsentDTO;
use Omersia\Gdpr\Models\CookieConsent;
use Omersia\Gdpr\Services\CookieConsentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CookieConsentServiceTest extends TestCase
{
    use RefreshDatabase;

    private CookieConsentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CookieConsentService::class);
    }

    private function makeDto(array $overrides = []): CookieConsentDTO
    {
        return new CookieConsentDTO(
            customerId: $overrides['customerId'] ?? null,
            sessionId: $overrides['sessionId'] ?? 'sess-default',
            ipAddress: $overrides['ipAddress'] ?? '127.0.0.1',
            userAgent: $overrides['userAgent'] ?? 'TestAgent/1.0',
            necessary: true,
            functional: $overrides['functional'] ?? false,
            analytics: $overrides['analytics'] ?? false,
            marketing: $overrides['marketing'] ?? false,
            consentVersion: $overrides['consentVersion'] ?? '1.0',
        );
    }

    #[Test]
    public function record_consent_creates_a_cookie_consent_record(): void
    {
        $dto = $this->makeDto(['sessionId' => 'sess-record', 'functional' => true, 'analytics' => true]);

        $consent = $this->service->recordConsent($dto);

        $this->assertInstanceOf(CookieConsent::class, $consent);
        $this->assertNotNull($consent->id);
        $this->assertTrue($consent->necessary);
        $this->assertTrue($consent->functional);
        $this->assertTrue($consent->analytics);
        $this->assertFalse($consent->marketing);

        $this->assertDatabaseHas('cookie_consents', [
            'session_id' => 'sess-record',
            'functional' => 1,
            'analytics' => 1,
            'marketing' => 0,
        ]);
    }

    #[Test]
    public function record_consent_sets_expires_at_to_13_months(): void
    {
        $dto = $this->makeDto(['sessionId' => 'sess-expiry']);

        $consent = $this->service->recordConsent($dto);

        $this->assertNotNull($consent->expires_at);
        $expectedExpiry = now()->addMonths(13);
        $this->assertEqualsWithDelta($expectedExpiry->timestamp, $consent->expires_at->timestamp, 5);
    }

    #[Test]
    public function get_current_consent_returns_customer_consent_by_priority(): void
    {
        $customer = Customer::factory()->create();
        $sessionId = 'sess-cust-priority';
        $ip = '10.0.0.1';

        // Consentement par customer
        CookieConsent::create([
            'customer_id' => $customer->id,
            'session_id' => null,
            'ip_address' => $ip,
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => true,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        // Consentement par session pour le meme user
        CookieConsent::create([
            'customer_id' => null,
            'session_id' => $sessionId,
            'ip_address' => $ip,
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => false,
            'analytics' => true,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        // Customer ID a la priorité
        $result = $this->service->getCurrentConsent($customer->id, $sessionId, $ip);

        $this->assertNotNull($result);
        $this->assertEquals($customer->id, $result->customer_id);
        $this->assertTrue($result->functional); // Propriété du consentement customer
    }

    #[Test]
    public function get_current_consent_falls_back_to_session_when_no_customer(): void
    {
        $sessionId = 'sess-fallback';
        $ip = '10.0.0.2';

        // Seulement un consentement par session
        CookieConsent::create([
            'customer_id' => null,
            'session_id' => $sessionId,
            'ip_address' => $ip,
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => false,
            'analytics' => true,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $result = $this->service->getCurrentConsent(null, $sessionId, $ip);

        $this->assertNotNull($result);
        $this->assertEquals($sessionId, $result->session_id);
    }

    #[Test]
    public function get_current_consent_falls_back_to_ip_when_no_session_match(): void
    {
        $ip = '10.0.0.3';

        // Seulement un consentement par IP
        CookieConsent::create([
            'customer_id' => null,
            'session_id' => 'old-session',
            'ip_address' => $ip,
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => true,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        // Recherche avec une session différente (pas de match session)
        $result = $this->service->getCurrentConsent(null, 'no-match-session', $ip);

        $this->assertNotNull($result);
        $this->assertEquals($ip, $result->ip_address);
        $this->assertTrue($result->marketing);
    }

    #[Test]
    public function get_current_consent_returns_null_when_nothing_found(): void
    {
        $result = $this->service->getCurrentConsent(null, 'nonexistent', '9.9.9.9');

        $this->assertNull($result);
    }

    #[Test]
    public function is_cookie_allowed_always_returns_true_for_necessary(): void
    {
        // Même sans aucun consentement, 'necessary' doit être autorisé
        $allowed = $this->service->isCookieAllowed('necessary', null, null, null);

        $this->assertTrue($allowed);
    }

    #[Test]
    public function is_cookie_allowed_returns_false_when_no_consent_exists(): void
    {
        foreach (['functional', 'analytics', 'marketing'] as $type) {
            $result = $this->service->isCookieAllowed($type, null, 'no-session', '0.0.0.0');
            $this->assertFalse($result, "Expected '{$type}' to be denied when no consent exists");
        }
    }

    #[Test]
    public function is_cookie_allowed_returns_false_for_expired_consent(): void
    {
        $sessionId = 'expired-sess';

        CookieConsent::create([
            'customer_id' => null,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => true,
            'consent_version' => '1.0',
            'consented_at' => now()->subYear(),
            'expires_at' => now()->subDay(), // Expiré
        ]);

        $result = $this->service->isCookieAllowed('analytics', null, $sessionId, '127.0.0.1');

        $this->assertFalse($result);
    }

    #[Test]
    public function is_cookie_allowed_returns_correct_value_for_each_type(): void
    {
        $sessionId = 'sess-all-types';

        CookieConsent::create([
            'customer_id' => null,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => true,
            'analytics' => false,
            'marketing' => true,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $this->assertTrue($this->service->isCookieAllowed('necessary', null, $sessionId, '127.0.0.1'));
        $this->assertTrue($this->service->isCookieAllowed('functional', null, $sessionId, '127.0.0.1'));
        $this->assertFalse($this->service->isCookieAllowed('analytics', null, $sessionId, '127.0.0.1'));
        $this->assertTrue($this->service->isCookieAllowed('marketing', null, $sessionId, '127.0.0.1'));
    }

    #[Test]
    public function is_cookie_allowed_returns_false_for_unknown_type(): void
    {
        $sessionId = 'sess-unknown';

        CookieConsent::create([
            'customer_id' => null,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => true,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $result = $this->service->isCookieAllowed('unknown_type', null, $sessionId, '127.0.0.1');

        $this->assertFalse($result);
    }

    #[Test]
    public function clean_expired_consents_deletes_only_expired_records(): void
    {
        // Consentement valide
        CookieConsent::create([
            'session_id' => 'valid-sess',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        // Consentements expirés
        CookieConsent::create([
            'session_id' => 'expired-sess-1',
            'ip_address' => '127.0.0.2',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);
        CookieConsent::create([
            'session_id' => 'expired-sess-2',
            'ip_address' => '127.0.0.3',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now()->subYear(),
            'expires_at' => now()->subMonth(),
        ]);

        $deleted = $this->service->cleanExpiredConsents();

        $this->assertEquals(2, $deleted);
        $this->assertDatabaseCount('cookie_consents', 1);
        $this->assertDatabaseHas('cookie_consents', ['session_id' => 'valid-sess']);
    }

    #[Test]
    public function clean_expired_consents_returns_zero_when_nothing_to_clean(): void
    {
        CookieConsent::create([
            'session_id' => 'valid-only',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $deleted = $this->service->cleanExpiredConsents();

        $this->assertEquals(0, $deleted);
    }

    #[Test]
    public function get_consent_history_returns_all_consents_for_customer(): void
    {
        $customer = Customer::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            CookieConsent::create([
                'customer_id' => $customer->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'TestAgent',
                'necessary' => true,
                'functional' => (bool) ($i % 2),
                'analytics' => false,
                'marketing' => false,
                'consent_version' => '1.0',
                'consented_at' => now()->subDays(3 - $i),
                'expires_at' => now()->addMonths(13),
            ]);
        }

        $history = $this->service->getConsentHistory($customer->id);

        $this->assertCount(3, $history);
    }

    #[Test]
    public function get_consent_history_returns_empty_collection_when_no_history(): void
    {
        $history = $this->service->getConsentHistory(99999);

        $this->assertCount(0, $history);
    }

    #[Test]
    public function get_consent_history_is_ordered_by_most_recent_first(): void
    {
        $customer = Customer::factory()->create();

        CookieConsent::create([
            'customer_id' => $customer->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now()->subMonth(),
            'expires_at' => now()->addMonths(12),
        ]);

        CookieConsent::create([
            'customer_id' => $customer->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => false,
            'consent_version' => '1.1',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $history = $this->service->getConsentHistory($customer->id);

        $this->assertEquals('1.1', $history->first()->consent_version);
    }
}
