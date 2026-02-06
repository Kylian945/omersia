<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\WithApiKey;

class CartApiTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
    }

    /** @test */
    public function cart_sync_creates_and_deletes_cart(): void
    {
        $payload = [
            'currency' => 'EUR',
            'items' => [
                [
                    'id' => 1,
                    'name' => 'Produit test',
                    'price' => 19.99,
                    'qty' => 2,
                ],
            ],
        ];

        $create = $this->postJson('/api/v1/cart/sync', $payload, $this->apiHeaders());
        $create->assertStatus(200);
        $create->assertJsonStructure(['id', 'token', 'subtotal', 'total_qty', 'currency']);

        $token = $create->json('token');
        $this->assertNotEmpty($token);

        $delete = $this->postJson('/api/v1/cart/sync', [
            'token' => $token,
            'items' => [],
        ], $this->apiHeaders());

        $delete->assertStatus(200);
        $delete->assertJson(['deleted' => true]);
    }
}
