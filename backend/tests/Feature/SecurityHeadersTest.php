<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    /** @test */
    public function it_sets_basic_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertTrue($response->headers->has('Permissions-Policy'));
    }

    /** @test */
    public function it_sets_content_security_policy_in_report_only_mode_in_development(): void
    {
        config(['app.env' => 'local']);

        $response = $this->get('/');

        // En développement, doit être en mode Report-Only
        $this->assertTrue(
            $response->headers->has('Content-Security-Policy-Report-Only'),
            'CSP Report-Only header missing in development'
        );

        $csp = $response->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9+\/=]+'/", $csp);
        $this->assertStringContainsString('https://js.stripe.com', $csp);
    }

    /** @test */
    public function it_sets_content_security_policy_in_enforcing_mode_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('/');

        // En production, doit être en mode enforcing
        $this->assertTrue(
            $response->headers->has('Content-Security-Policy'),
            'CSP enforcing header missing in production'
        );

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    /** @test */
    public function it_allows_stripe_scripts_in_csp(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy-Report-Only')
            ?? $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://js.stripe.com', $csp);
        $this->assertStringContainsString('https://hooks.stripe.com', $csp);
    }

    /** @test */
    public function it_allows_google_fonts_in_csp(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy-Report-Only')
            ?? $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://fonts.gstatic.com', $csp);
    }

    /** @test */
    public function it_blocks_object_and_embed_tags(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy-Report-Only')
            ?? $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    /** @test */
    public function it_prevents_clickjacking_with_frame_ancestors(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy-Report-Only')
            ?? $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    /** @test */
    public function it_sets_hsts_header_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('/');

        $this->assertTrue($response->headers->has('Strict-Transport-Security'));
        $hsts = $response->headers->get('Strict-Transport-Security');
        $this->assertStringContainsString('max-age=31536000', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
    }

    /** @test */
    public function it_does_not_set_hsts_header_in_development(): void
    {
        config(['app.env' => 'local']);

        $response = $this->get('/');

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }
}
