<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Unit;

use Omersia\Gdpr\DTO\CookieConsentDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CookieConsentDTOTest extends TestCase
{
    #[Test]
    public function it_constructs_with_explicit_values(): void
    {
        $dto = new CookieConsentDTO(
            customerId: 42,
            sessionId: 'sess-abc',
            ipAddress: '127.0.0.1',
            userAgent: 'TestAgent/1.0',
            necessary: true,
            functional: true,
            analytics: false,
            marketing: false,
            consentVersion: '2.0',
        );

        $this->assertEquals(42, $dto->customerId);
        $this->assertEquals('sess-abc', $dto->sessionId);
        $this->assertEquals('127.0.0.1', $dto->ipAddress);
        $this->assertEquals('TestAgent/1.0', $dto->userAgent);
        $this->assertTrue($dto->necessary);
        $this->assertTrue($dto->functional);
        $this->assertFalse($dto->analytics);
        $this->assertFalse($dto->marketing);
        $this->assertEquals('2.0', $dto->consentVersion);
    }

    #[Test]
    public function it_defaults_consent_version_to_1_0(): void
    {
        $dto = new CookieConsentDTO(
            customerId: null,
            sessionId: 'sess-xyz',
            ipAddress: '127.0.0.1',
            userAgent: 'Agent',
            necessary: true,
            functional: false,
            analytics: false,
            marketing: false,
        );

        $this->assertEquals('1.0', $dto->consentVersion);
    }

    #[Test]
    public function from_array_sets_necessary_to_true_always(): void
    {
        $data = [
            'ip_address' => '10.0.0.1',
            'user_agent' => 'TestBrowser',
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
        ];

        $dto = CookieConsentDTO::fromArray($data, null, 'session-001');

        $this->assertTrue($dto->necessary);
    }

    #[Test]
    public function from_array_maps_functional_analytics_marketing(): void
    {
        $data = [
            'ip_address' => '10.0.0.1',
            'user_agent' => 'TestBrowser',
            'functional' => true,
            'analytics' => false,
            'marketing' => true,
        ];

        $dto = CookieConsentDTO::fromArray($data, null, 'session-002');

        $this->assertTrue($dto->functional);
        $this->assertFalse($dto->analytics);
        $this->assertTrue($dto->marketing);
    }

    #[Test]
    public function from_array_casts_values_to_boolean(): void
    {
        $data = [
            'ip_address' => '10.0.0.1',
            'user_agent' => 'TestBrowser',
            'functional' => 1,
            'analytics' => 0,
            'marketing' => '1',
        ];

        $dto = CookieConsentDTO::fromArray($data, 5, null);

        $this->assertIsBool($dto->functional);
        $this->assertIsBool($dto->analytics);
        $this->assertIsBool($dto->marketing);
        $this->assertTrue($dto->functional);
        $this->assertFalse($dto->analytics);
    }

    #[Test]
    public function from_array_defaults_missing_booleans_to_false(): void
    {
        $data = [
            'ip_address' => '10.0.0.1',
            'user_agent' => 'TestBrowser',
        ];

        $dto = CookieConsentDTO::fromArray($data, null, 'session-003');

        $this->assertFalse($dto->functional);
        $this->assertFalse($dto->analytics);
        $this->assertFalse($dto->marketing);
    }

    #[Test]
    public function from_array_uses_provided_consent_version(): void
    {
        $data = [
            'ip_address' => '10.0.0.1',
            'user_agent' => 'TestBrowser',
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '3.0',
        ];

        $dto = CookieConsentDTO::fromArray($data, null, 'session-004');

        $this->assertEquals('3.0', $dto->consentVersion);
    }

    #[Test]
    public function from_array_defaults_version_to_1_0_when_missing(): void
    {
        $data = [
            'ip_address' => '10.0.0.1',
            'user_agent' => 'TestBrowser',
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
        ];

        $dto = CookieConsentDTO::fromArray($data, null, 'session-005');

        $this->assertEquals('1.0', $dto->consentVersion);
    }

    #[Test]
    public function from_array_passes_customer_id_and_session_id(): void
    {
        $data = [
            'ip_address' => '10.0.0.1',
            'user_agent' => 'TestBrowser',
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
        ];

        $dto = CookieConsentDTO::fromArray($data, 7, 'my-session');

        $this->assertEquals(7, $dto->customerId);
        $this->assertEquals('my-session', $dto->sessionId);
    }

    #[Test]
    public function to_array_contains_all_required_keys(): void
    {
        $dto = new CookieConsentDTO(
            customerId: 1,
            sessionId: 'sess-abc',
            ipAddress: '127.0.0.1',
            userAgent: 'TestAgent/1.0',
            necessary: true,
            functional: true,
            analytics: false,
            marketing: false,
            consentVersion: '1.0',
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('customer_id', $array);
        $this->assertArrayHasKey('session_id', $array);
        $this->assertArrayHasKey('ip_address', $array);
        $this->assertArrayHasKey('user_agent', $array);
        $this->assertArrayHasKey('necessary', $array);
        $this->assertArrayHasKey('functional', $array);
        $this->assertArrayHasKey('analytics', $array);
        $this->assertArrayHasKey('marketing', $array);
        $this->assertArrayHasKey('consent_version', $array);
        $this->assertArrayHasKey('consented_at', $array);
        $this->assertArrayHasKey('expires_at', $array);
    }

    #[Test]
    public function to_array_sets_consented_at_to_now(): void
    {
        $dto = new CookieConsentDTO(
            customerId: null,
            sessionId: 'sess-test',
            ipAddress: '127.0.0.1',
            userAgent: 'Agent',
            necessary: true,
            functional: false,
            analytics: false,
            marketing: false,
        );

        $before = now()->subSecond();
        $array = $dto->toArray();
        $after = now()->addSecond();

        $this->assertGreaterThanOrEqual($before->timestamp, $array['consented_at']->timestamp);
        $this->assertLessThanOrEqual($after->timestamp, $array['consented_at']->timestamp);
    }

    #[Test]
    public function to_array_sets_expires_at_to_13_months_from_now(): void
    {
        $dto = new CookieConsentDTO(
            customerId: null,
            sessionId: 'sess-test',
            ipAddress: '127.0.0.1',
            userAgent: 'Agent',
            necessary: true,
            functional: false,
            analytics: false,
            marketing: false,
        );

        $array = $dto->toArray();

        $expectedExpiry = now()->addMonths(13);
        // Allow 5 seconds tolerance for test execution time
        $this->assertEqualsWithDelta(
            $expectedExpiry->timestamp,
            $array['expires_at']->timestamp,
            5
        );
    }

    #[Test]
    public function to_array_maps_values_correctly(): void
    {
        $dto = new CookieConsentDTO(
            customerId: 99,
            sessionId: 'sess-xyz',
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            necessary: true,
            functional: true,
            analytics: false,
            marketing: true,
            consentVersion: '2.1',
        );

        $array = $dto->toArray();

        $this->assertEquals(99, $array['customer_id']);
        $this->assertEquals('sess-xyz', $array['session_id']);
        $this->assertEquals('192.168.1.1', $array['ip_address']);
        $this->assertEquals('Mozilla/5.0', $array['user_agent']);
        $this->assertTrue($array['necessary']);
        $this->assertTrue($array['functional']);
        $this->assertFalse($array['analytics']);
        $this->assertTrue($array['marketing']);
        $this->assertEquals('2.1', $array['consent_version']);
    }
}
