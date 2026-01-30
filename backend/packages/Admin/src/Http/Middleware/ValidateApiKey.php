<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Omersia\Core\Models\ApiKey;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-KEY');

        if (! $key) {
            return response()->json(['message' => 'Missing API key'], 401);
        }

        $apiKey = ApiKey::where('key', hash('sha256', $key))->where('active', true)->first();

        if (! $apiKey) {
            return response()->json(['message' => 'Invalid API key'], 401);
        }

        // Vérifier l'expiration
        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return response()->json(['error' => 'API key expired'], 401);
        }

        // Mettre à jour les stats d'utilisation
        $apiKey->update([
            'last_used_at' => now(),
            'last_used_ip' => $request->ip(),
            'usage_count' => $apiKey->usage_count + 1,
        ]);

        // Optionnel : tu peux attacher $apiKey à la requête
        $request->attributes->set('apiKey', $apiKey);

        return $next($request);
    }
}
