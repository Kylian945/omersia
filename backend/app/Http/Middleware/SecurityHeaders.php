<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Générer un nonce CSP unique pour cette requête
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        // Empêcher le clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Empêcher le MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Protection XSS (legacy)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // HSTS (seulement en production avec HTTPS)
        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        $isProduction = app()->environment('production');

        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://js.stripe.com https://maps.googleapis.com",
            "style-src 'self' 'nonce-{$nonce}'",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.gstatic.com",
            "connect-src 'self' https://api.stripe.com https://*.meilisearch.com",
            'frame-src https://js.stripe.com https://hooks.stripe.com',
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        if ($isProduction) {
            $cspDirectives[] = 'upgrade-insecure-requests';
        }

        // Mode Report-Only en développement, enforcing en production
        if ($isProduction) {
            $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives));
        } else {
            // Report-Only mode pour tester sans bloquer
            $response->headers->set('Content-Security-Policy-Report-Only', implode('; ', $cspDirectives));
        }

        return $response;
    }
}
