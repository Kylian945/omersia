<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Customer\Models\Customer;
use Omersia\Gdpr\Models\CookieConsent;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CookieConsentModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_expected_fillable_attributes(): void
    {
        $model = new CookieConsent;
        $fillable = $model->getFillable();

        $this->assertContains('customer_id', $fillable);
        $this->assertContains('session_id', $fillable);
        $this->assertContains('ip_address', $fillable);
        $this->assertContains('user_agent', $fillable);
        $this->assertContains('necessary', $fillable);
        $this->assertContains('functional', $fillable);
        $this->assertContains('analytics', $fillable);
        $this->assertContains('marketing', $fillable);
        $this->assertContains('consent_version', $fillable);
        $this->assertContains('consented_at', $fillable);
        $this->assertContains('expires_at', $fillable);
    }

    #[Test]
    public function it_casts_boolean_fields_correctly(): void
    {
        $consent = CookieConsent::create([
            'session_id' => 'sess-001',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => 1,
            'functional' => 0,
            'analytics' => 1,
            'marketing' => 0,
            'consent_version' => '1.0',
            'consented_at' => now(),
        ]);

        $this->assertIsBool($consent->necessary);
        $this->assertIsBool($consent->functional);
        $this->assertIsBool($consent->analytics);
        $this->assertIsBool($consent->marketing);

        $this->assertTrue($consent->necessary);
        $this->assertFalse($consent->functional);
        $this->assertTrue($consent->analytics);
        $this->assertFalse($consent->marketing);
    }

    #[Test]
    public function it_casts_consented_at_to_datetime(): void
    {
        $now = now();

        $consent = CookieConsent::create([
            'session_id' => 'sess-002',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => $now,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $consent->consented_at);
    }

    #[Test]
    public function it_casts_expires_at_to_datetime_when_set(): void
    {
        $expiresAt = now()->addMonths(13);

        $consent = CookieConsent::create([
            'session_id' => 'sess-003',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $consent->expires_at);
    }

    #[Test]
    public function it_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();

        $consent = CookieConsent::create([
            'customer_id' => $customer->id,
            'session_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $this->assertInstanceOf(BelongsTo::class, $consent->customer());
        $this->assertInstanceOf(Customer::class, $consent->customer);
        $this->assertEquals($customer->id, $consent->customer->id);
    }

    #[Test]
    public function it_allows_null_customer_for_anonymous_visitor(): void
    {
        $consent = CookieConsent::create([
            'customer_id' => null,
            'session_id' => 'anon-session-xyz',
            'ip_address' => '192.168.0.1',
            'user_agent' => 'Mozilla/5.0',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
        ]);

        $this->assertNull($consent->customer_id);
        $this->assertNull($consent->customer);
    }

    #[Test]
    public function is_expired_returns_false_when_no_expires_at(): void
    {
        $consent = CookieConsent::create([
            'session_id' => 'sess-004',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => null,
        ]);

        $this->assertFalse($consent->isExpired());
    }

    #[Test]
    public function is_expired_returns_false_when_expires_at_is_in_the_future(): void
    {
        $consent = CookieConsent::create([
            'session_id' => 'sess-005',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $this->assertFalse($consent->isExpired());
    }

    #[Test]
    public function is_expired_returns_true_when_expires_at_is_in_the_past(): void
    {
        $consent = CookieConsent::create([
            'session_id' => 'sess-006',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($consent->isExpired());
    }

    #[Test]
    public function get_latest_for_customer_returns_most_recent_valid_consent(): void
    {
        $customer = Customer::factory()->create();

        // Ancien consentement
        CookieConsent::create([
            'customer_id' => $customer->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now()->subMonths(2),
            'expires_at' => now()->addMonths(11),
        ]);

        // Consentement récent
        $recent = CookieConsent::create([
            'customer_id' => $customer->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => false,
            'consent_version' => '1.1',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $result = CookieConsent::getLatestForCustomer($customer->id);

        $this->assertNotNull($result);
        $this->assertEquals($recent->id, $result->id);
    }

    #[Test]
    public function get_latest_for_customer_excludes_expired_consents(): void
    {
        $customer = Customer::factory()->create();

        CookieConsent::create([
            'customer_id' => $customer->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => true,
            'consent_version' => '1.0',
            'consented_at' => now()->subYear(),
            'expires_at' => now()->subDay(), // Expiré
        ]);

        $result = CookieConsent::getLatestForCustomer($customer->id);

        $this->assertNull($result);
    }

    #[Test]
    public function get_latest_for_customer_returns_null_when_no_consent(): void
    {
        $result = CookieConsent::getLatestForCustomer(99999);

        $this->assertNull($result);
    }

    #[Test]
    public function get_latest_for_session_returns_valid_consent(): void
    {
        $sessionId = 'test-session-abc123';

        $consent = CookieConsent::create([
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => true,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $result = CookieConsent::getLatestForSession($sessionId);

        $this->assertNotNull($result);
        $this->assertEquals($consent->id, $result->id);
    }

    #[Test]
    public function get_latest_for_session_excludes_expired_consents(): void
    {
        $sessionId = 'expired-session-xyz';

        CookieConsent::create([
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => true,
            'consent_version' => '1.0',
            'consented_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $result = CookieConsent::getLatestForSession($sessionId);

        $this->assertNull($result);
    }

    #[Test]
    public function get_latest_for_session_returns_null_for_unknown_session(): void
    {
        $result = CookieConsent::getLatestForSession('nonexistent-session');

        $this->assertNull($result);
    }

    #[Test]
    public function get_latest_for_ip_returns_valid_consent(): void
    {
        $ip = '203.0.113.42';

        $consent = CookieConsent::create([
            'session_id' => 'sess-007',
            'ip_address' => $ip,
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => false,
            'analytics' => true,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $result = CookieConsent::getLatestForIp($ip);

        $this->assertNotNull($result);
        $this->assertEquals($consent->id, $result->id);
    }

    #[Test]
    public function get_latest_for_ip_excludes_expired_consents(): void
    {
        $ip = '203.0.113.99';

        CookieConsent::create([
            'session_id' => 'sess-008',
            'ip_address' => $ip,
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => true,
            'consent_version' => '1.0',
            'consented_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $result = CookieConsent::getLatestForIp($ip);

        $this->assertNull($result);
    }

    #[Test]
    public function get_latest_for_ip_includes_consent_without_expiry(): void
    {
        $ip = '10.0.0.1';

        $consent = CookieConsent::create([
            'session_id' => 'sess-009',
            'ip_address' => $ip,
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => null,
        ]);

        $result = CookieConsent::getLatestForIp($ip);

        $this->assertNotNull($result);
        $this->assertEquals($consent->id, $result->id);
    }
}
