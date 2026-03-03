<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Customer\Models\Customer;
use Omersia\Gdpr\Models\CookieConsent;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\WithApiKey;

class CookieConsentApiTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/gdpr/cookie-consent
    // -------------------------------------------------------------------------

    #[Test]
    public function it_requires_api_key_for_show(): void
    {
        $response = $this->getJson('/api/v1/gdpr/cookie-consent');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_no_consent_when_none_exists(): void
    {
        $response = $this->getJson('/api/v1/gdpr/cookie-consent', $this->apiHeaders());

        $response->assertOk()
            ->assertJson([
                'has_consent' => false,
                'necessary' => true,
                'functional' => false,
                'analytics' => false,
                'marketing' => false,
            ]);
    }

    #[Test]
    public function it_returns_no_consent_when_consent_is_expired(): void
    {
        CookieConsent::create([
            'session_id' => 'test-session',
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

        $response = $this->getJson('/api/v1/gdpr/cookie-consent', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('has_consent', false);
    }

    #[Test]
    public function it_returns_consent_data_when_valid_consent_exists_for_ip(): void
    {
        CookieConsent::create([
            'session_id' => 'other-session',
            'ip_address' => '127.0.0.1', // Same IP as test request
            'user_agent' => 'TestAgent/1.0',
            'necessary' => true,
            'functional' => true,
            'analytics' => false,
            'marketing' => true,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $response = $this->getJson('/api/v1/gdpr/cookie-consent', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'has_consent',
                'necessary',
                'functional',
                'analytics',
                'marketing',
                'consented_at',
                'expires_at',
            ])
            ->assertJsonPath('has_consent', true)
            ->assertJsonPath('necessary', true)
            ->assertJsonPath('functional', true)
            ->assertJsonPath('analytics', false)
            ->assertJsonPath('marketing', true);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/gdpr/cookie-consent
    // -------------------------------------------------------------------------

    #[Test]
    public function it_requires_api_key_for_store(): void
    {
        $response = $this->postJson('/api/v1/gdpr/cookie-consent', [
            'functional' => true,
            'analytics' => false,
            'marketing' => false,
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_stores_consent_with_valid_data(): void
    {
        $payload = [
            'functional' => true,
            'analytics' => false,
            'marketing' => true,
        ];

        $response = $this->postJson('/api/v1/gdpr/cookie-consent', $payload, $this->apiHeaders());

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'consent' => [
                    'necessary',
                    'functional',
                    'analytics',
                    'marketing',
                    'expires_at',
                ],
            ])
            ->assertJsonPath('consent.necessary', true)
            ->assertJsonPath('consent.functional', true)
            ->assertJsonPath('consent.analytics', false)
            ->assertJsonPath('consent.marketing', true);

        $this->assertDatabaseHas('cookie_consents', [
            'functional' => 1,
            'analytics' => 0,
            'marketing' => 1,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_for_store(): void
    {
        $response = $this->postJson('/api/v1/gdpr/cookie-consent', [], $this->apiHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['functional', 'analytics', 'marketing']);
    }

    #[Test]
    public function it_validates_boolean_type_for_consent_fields(): void
    {
        $response = $this->postJson('/api/v1/gdpr/cookie-consent', [
            'functional' => 'yes',
            'analytics' => 'no',
            'marketing' => 'maybe',
        ], $this->apiHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['functional', 'analytics', 'marketing']);
    }

    #[Test]
    public function it_stores_consent_for_authenticated_customer(): void
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('test')->plainTextToken;

        $headers = array_merge($this->apiHeaders(), [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response = $this->postJson('/api/v1/gdpr/cookie-consent', [
            'functional' => true,
            'analytics' => true,
            'marketing' => false,
        ], $headers);

        $response->assertCreated();

        $this->assertDatabaseHas('cookie_consents', [
            'customer_id' => $customer->id,
            'functional' => 1,
            'analytics' => 1,
        ]);
    }

    #[Test]
    public function it_accepts_false_for_all_optional_cookies(): void
    {
        $response = $this->postJson('/api/v1/gdpr/cookie-consent', [
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
        ], $this->apiHeaders());

        $response->assertCreated()
            ->assertJsonPath('consent.necessary', true)
            ->assertJsonPath('consent.functional', false)
            ->assertJsonPath('consent.analytics', false)
            ->assertJsonPath('consent.marketing', false);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/gdpr/cookie-consent/check/{type}
    // -------------------------------------------------------------------------

    #[Test]
    public function it_requires_api_key_for_check(): void
    {
        $response = $this->getJson('/api/v1/gdpr/cookie-consent/check/analytics');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_type_and_allowed_flag(): void
    {
        $response = $this->getJson('/api/v1/gdpr/cookie-consent/check/analytics', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure(['type', 'allowed'])
            ->assertJsonPath('type', 'analytics');
    }

    #[Test]
    public function it_always_allows_necessary_cookies(): void
    {
        $response = $this->getJson('/api/v1/gdpr/cookie-consent/check/necessary', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('type', 'necessary')
            ->assertJsonPath('allowed', true);
    }

    #[Test]
    public function it_denies_optional_cookies_when_no_consent_exists(): void
    {
        foreach (['functional', 'analytics', 'marketing'] as $type) {
            $response = $this->getJson(
                "/api/v1/gdpr/cookie-consent/check/{$type}",
                $this->apiHeaders()
            );

            $response->assertOk()
                ->assertJsonPath('type', $type)
                ->assertJsonPath('allowed', false);
        }
    }

    #[Test]
    public function it_allows_cookie_type_when_consent_is_granted(): void
    {
        CookieConsent::create([
            'session_id' => 'check-session',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => false,
            'analytics' => true,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $response = $this->getJson('/api/v1/gdpr/cookie-consent/check/analytics', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('allowed', true);
    }

    #[Test]
    public function it_denies_cookie_type_when_consent_exists_but_type_not_granted(): void
    {
        CookieConsent::create([
            'session_id' => 'partial-consent',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'necessary' => true,
            'functional' => true,
            'analytics' => false,
            'marketing' => false,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'expires_at' => now()->addMonths(13),
        ]);

        $response = $this->getJson('/api/v1/gdpr/cookie-consent/check/analytics', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('allowed', false);
    }

    #[Test]
    public function it_returns_false_for_unknown_cookie_type(): void
    {
        // Meme avec un consentement complet, les types inconnus sont refusés
        CookieConsent::create([
            'session_id' => 'full-consent',
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

        $response = $this->getJson('/api/v1/gdpr/cookie-consent/check/unknown_type', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('allowed', false);
    }
}
