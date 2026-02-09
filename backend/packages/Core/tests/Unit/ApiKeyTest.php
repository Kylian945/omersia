<?php

declare(strict_types=1);

namespace Omersia\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Core\Models\ApiKey;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_generate_api_key(): void
    {
        $apiKey = ApiKey::generate('Test API Key');

        $this->assertDatabaseHas('api_keys', [
            'name' => 'Test API Key',
            'active' => true,
        ]);
        $this->assertNotNull($apiKey->key);
    }

    public function it_hashes_key_on_creation(): void
    {
        $apiKey = ApiKey::generate('Test Key');

        // La clé doit être un hash SHA256 (64 caractères hexadécimaux)
        $this->assertEquals(64, strlen($apiKey->key));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $apiKey->key);
    }

    public function it_creates_active_key_by_default(): void
    {
        $apiKey = ApiKey::generate('Test Key');

        $this->assertTrue($apiKey->active);
    }

    public function it_can_create_inactive_key(): void
    {
        $apiKey = ApiKey::create([
            'name' => 'Inactive Key',
            'key' => hash('sha256', 'test-key'),
            'active' => false,
        ]);

        $this->assertFalse($apiKey->active);
    }

    public function it_has_fillable_attributes(): void
    {
        $apiKey = new ApiKey;
        $fillable = $apiKey->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('key', $fillable);
        $this->assertContains('active', $fillable);
    }

    public function it_casts_active_to_boolean(): void
    {
        $apiKey = ApiKey::create([
            'name' => 'Test',
            'key' => hash('sha256', 'test'),
            'active' => 1,
        ]);

        $this->assertIsBool($apiKey->active);
        $this->assertTrue($apiKey->active);
    }

    public function it_can_regenerate_key(): void
    {
        $apiKey = ApiKey::generate('Test Key');
        $originalKey = $apiKey->key;

        $newKey = $apiKey->regenerateKey();

        $this->assertNotEquals($originalKey, $apiKey->fresh()->key);
        $this->assertIsString($newKey);
        $this->assertEquals(64, strlen($newKey));
    }

    public function it_validates_active_key(): void
    {
        $plainKey = str_repeat('a', 64);
        ApiKey::create([
            'name' => 'Valid Key',
            'key' => hash('sha256', $plainKey),
            'active' => true,
        ]);

        $this->assertTrue(ApiKey::isValid($plainKey));
    }

    public function it_rejects_inactive_key(): void
    {
        $plainKey = str_repeat('b', 64);
        ApiKey::create([
            'name' => 'Inactive Key',
            'key' => hash('sha256', $plainKey),
            'active' => false,
        ]);

        $this->assertFalse(ApiKey::isValid($plainKey));
    }

    public function it_rejects_non_existent_key(): void
    {
        $this->assertFalse(ApiKey::isValid('non-existent-key'));
    }

    public function it_rejects_wrong_key(): void
    {
        $plainKey = str_repeat('c', 64);
        ApiKey::create([
            'name' => 'Test Key',
            'key' => hash('sha256', $plainKey),
            'active' => true,
        ]);

        $this->assertFalse(ApiKey::isValid('wrong-key'));
    }
}
