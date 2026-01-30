<?php

declare(strict_types=1);

namespace Tests;

use Omersia\Core\Models\ApiKey;

trait WithApiKey
{
    protected string $testApiKey = 'test-api-key-12345678901234567890123456789012';

    protected function setUpApiKey(): void
    {
        // Create API key in database (hashed)
        ApiKey::create([
            'name' => 'Test API KEY',
            'key' => hash('sha256', $this->testApiKey),
            'active' => true,
        ]);

        // Set config for easy access in tests
        config(['app.front_api_key' => $this->testApiKey]);
    }

    protected function apiHeaders(): array
    {
        return [
            'X-API-KEY' => $this->testApiKey,
        ];
    }

    protected function authenticatedHeaders($user): array
    {
        $token = $user->createToken('test-token')->plainTextToken;

        return [
            'Authorization' => 'Bearer '.$token,
            'X-API-KEY' => $this->testApiKey,
        ];
    }
}
