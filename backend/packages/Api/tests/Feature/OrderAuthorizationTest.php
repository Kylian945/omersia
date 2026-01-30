<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Order;
use Omersia\Customer\Models\Customer;
use Tests\TestCase;
use Tests\WithApiKey;

/**
 * @group security
 * @group authorization
 * @group idor
 */
class OrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
    }

    public function unauthenticated_user_cannot_list_orders(): void
    {
        $response = $this->getJson('/api/v1/orders', $this->apiHeaders());

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function user_can_list_only_their_confirmed_orders(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        // Create confirmed orders for both users
        $orderA1 = Order::factory()->confirmed()->create(['customer_id' => $userA->id]);
        $orderA2 = Order::factory()->confirmed()->create(['customer_id' => $userA->id]);

        // User B's order should not appear in A's list
        $orderB = Order::factory()->confirmed()->create(['customer_id' => $userB->id]);

        // Draft order should not appear (even for user A)
        $orderADraft = Order::factory()->draft()->create(['customer_id' => $userA->id]);

        $response = $this->getJson('/api/v1/orders', $this->authenticatedHeaders($userA));

        $response->assertStatus(200);
        $response->assertJsonCount(2);

        $returnedIds = collect($response->json())->pluck('id')->toArray();

        $this->assertContains($orderA1->id, $returnedIds);
        $this->assertContains($orderA2->id, $returnedIds);
        $this->assertNotContains($orderB->id, $returnedIds);
        $this->assertNotContains($orderADraft->id, $returnedIds);
    }

    /**
     * @group idor
     */
    public function user_cannot_view_other_user_order_details(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $orderB = Order::factory()->confirmed()->create([
            'customer_id' => $userB->id,
            'number' => 'ORD-123456',
        ]);

        $response = $this->getJson('/api/v1/orders/ORD-123456', $this->authenticatedHeaders($userA));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not found']);
    }

    public function user_can_view_their_own_order_details(): void
    {
        $user = Customer::factory()->create();

        $order = Order::factory()->confirmed()->create([
            'customer_id' => $user->id,
            'number' => 'ORD-789012',
        ]);

        $response = $this->getJson('/api/v1/orders/ORD-789012', $this->authenticatedHeaders($user));

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $order->id]);
        $response->assertJsonFragment(['number' => 'ORD-789012']);
    }

    public function unauthenticated_user_cannot_view_order_details(): void
    {
        $order = Order::factory()->confirmed()->create([
            'number' => 'ORD-123456',
        ]);

        $response = $this->getJson('/api/v1/orders/ORD-123456', $this->apiHeaders());

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function user_cannot_view_nonexistent_order(): void
    {
        $user = Customer::factory()->create();

        $response = $this->getJson('/api/v1/orders/FAKE-ORDER', $this->authenticatedHeaders($user));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not found']);
    }

    /**
     * @group idor
     */
    public function user_cannot_confirm_other_user_order(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $orderB = Order::factory()->draft()->create([
            'customer_id' => $userB->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/v1/orders/'.$orderB->id.'/confirm', [], $this->authenticatedHeaders($userA));

        // firstOrFail with where clause should throw 404
        $response->assertStatus(404);

        // Verify order status unchanged
        $this->assertEquals('draft', $orderB->fresh()->status);
    }

    public function user_can_confirm_their_own_draft_order(): void
    {
        $user = Customer::factory()->create();

        $order = Order::factory()->draft()->create([
            'customer_id' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/v1/orders/'.$order->id.'/confirm', [], $this->authenticatedHeaders($user));

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Commande confirmée avec succès']);

        // Verify order was confirmed
        $this->assertEquals('confirmed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->placed_at);
    }

    public function unauthenticated_user_cannot_confirm_order(): void
    {
        $order = Order::factory()->draft()->create();

        $response = $this->postJson('/api/v1/orders/'.$order->id.'/confirm', [], $this->apiHeaders());

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * @group idor
     */
    public function user_cannot_update_other_user_order(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $orderB = Order::factory()->create([
            'customer_id' => $userB->id,
        ]);

        $response = $this->putJson('/api/v1/orders/'.$orderB->id, [
            'currency' => 'EUR',
            'shipping_method_id' => 1,
            'customer_email' => 'hacker@test.com',
            'shipping_address' => ['line1' => 'Hacked Street'],
            'billing_address' => ['line1' => 'Hacked Street'],
            'items' => [['id' => 1]],
            'discount_total' => 0,
            'shipping_total' => 10,
            'tax_total' => 0,
            'total' => 10,
        ], $this->authenticatedHeaders($userA));

        // DCA-002 fix: Explicit ownership check prevents IDOR
        $response->assertStatus(403);
        $response->assertJson(['message' => 'Unauthorized']);
    }

    public function unauthenticated_user_cannot_download_invoice(): void
    {
        $order = Order::factory()->confirmed()->create([
            'number' => 'ORD-123456',
        ]);

        $response = $this->getJson('/api/v1/orders/ORD-123456/invoice', $this->apiHeaders());

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * @group idor
     */
    public function user_cannot_download_other_user_invoice(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $orderB = Order::factory()->confirmed()->create([
            'customer_id' => $userB->id,
            'number' => 'ORD-123456',
        ]);

        $response = $this->getJson('/api/v1/orders/ORD-123456/invoice', $this->authenticatedHeaders($userA));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Commande introuvable']);
    }

    public function guest_order_with_null_customer_id_cannot_be_accessed_without_auth(): void
    {
        // Create order with null customer_id (guest checkout)
        $guestOrder = Order::factory()->confirmed()->create([
            'customer_id' => null,
            'number' => 'ORD-GUEST-001',
        ]);

        $response = $this->getJson('/api/v1/orders/ORD-GUEST-001', $this->apiHeaders());

        // Must return 401 for unauthenticated access
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * @group idor
     */
    public function authenticated_user_cannot_access_guest_order(): void
    {
        $user = Customer::factory()->create();

        // Create order with null customer_id (guest checkout)
        $guestOrder = Order::factory()->confirmed()->create([
            'customer_id' => null,
            'number' => 'ORD-GUEST-002',
        ]);

        $response = $this->getJson('/api/v1/orders/ORD-GUEST-002', $this->authenticatedHeaders($user));

        // where('customer_id', $user->id) should not match null
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not found']);
    }

    public function returns_404_for_invalid_order_number(): void
    {
        $user = Customer::factory()->create();

        $response = $this->getJson('/api/v1/orders/INVALID-NUMBER', $this->authenticatedHeaders($user));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not found']);
    }

    public function order_list_is_sorted_by_placed_at_desc(): void
    {
        $user = Customer::factory()->create();

        $order1 = Order::factory()->confirmed()->create([
            'customer_id' => $user->id,
            'placed_at' => now()->subDays(3),
        ]);

        $order2 = Order::factory()->confirmed()->create([
            'customer_id' => $user->id,
            'placed_at' => now()->subDays(1),
        ]);

        $order3 = Order::factory()->confirmed()->create([
            'customer_id' => $user->id,
            'placed_at' => now()->subDays(2),
        ]);

        $response = $this->getJson('/api/v1/orders', $this->authenticatedHeaders($user));

        $response->assertStatus(200);

        $ids = collect($response->json())->pluck('id')->toArray();

        // Should be sorted: most recent first
        $this->assertEquals([$order2->id, $order3->id, $order1->id], $ids);
    }
}
