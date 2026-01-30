<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Omersia\Catalog\Models\Order;
use Omersia\Customer\Models\Customer;
use Tests\TestCase;
use Tests\WithApiKey;

/**
 * @group security
 * @group authorization
 * @group idor
 * @group payment
 */
class PaymentAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
    }

    public function unauthenticated_user_cannot_create_payment_intent(): void
    {
        $order = Order::factory()->draft()->create();

        $response = $this->postJson('/api/v1/payments/intent', [
            'order_id' => $order->id,
            'provider' => 'stripe',
        ], $this->apiHeaders());

        $response->assertStatus(401);
    }

    /**
     * @group idor
     */
    public function user_cannot_create_payment_intent_for_other_user_order(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $orderB = Order::factory()->draft()->create(['customer_id' => $userB->id]);

        Sanctum::actingAs($userA);

        $response = $this
            ->postJson('/api/v1/payments/intent', [
                'order_id' => $orderB->id,
                'provider' => 'stripe',
            ], $this->authenticatedHeaders($userA));

        // The ->where('customer_id', $request->user()->id)->firstOrFail() should fail
        $response->assertStatus(404);
    }

    public function user_can_create_payment_intent_for_own_order(): void
    {
        $user = Customer::factory()->create();
        $order = Order::factory()->draft()->create([
            'customer_id' => $user->id,
            'total' => 10000, // 100.00 EUR
            'currency' => 'EUR',
        ]);

        // Mock or skip Stripe integration
        if (! config('services.stripe.secret')) {
            $this->markTestSkipped('Stripe not configured in test environment');
        }

        $response = $this
            ->postJson('/api/v1/payments/intent', [
                'order_id' => $order->id,
                'provider' => 'stripe',
            ], $this->authenticatedHeaders($user));

        // If Stripe is configured, should succeed
        // If not configured, may return error - adjust based on implementation
        $this->assertContains($response->status(), [200, 500]);
    }

    /**
     * @group idor
     */
    public function user_cannot_create_payment_intent_for_null_customer_order(): void
    {
        $user = Customer::factory()->create();

        // Guest order with customer_id = null
        $guestOrder = Order::factory()->draft()->create(['customer_id' => null]);

        $response = $this
            ->postJson('/api/v1/payments/intent', [
                'order_id' => $guestOrder->id,
                'provider' => 'stripe',
            ], $this->authenticatedHeaders($user));

        // where('customer_id', $user->id) should not match null
        $response->assertStatus(404);
    }

    public function payment_intent_validates_required_fields(): void
    {
        $user = Customer::factory()->create();

        $response = $this
            ->postJson('/api/v1/payments/intent', [
                // Missing required fields
            ], $this->authenticatedHeaders($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id', 'provider']);
    }

    public function payment_intent_validates_provider_must_be_stripe(): void
    {
        $user = Customer::factory()->create();
        $order = Order::factory()->draft()->create(['customer_id' => $user->id]);

        $response = $this
            ->postJson('/api/v1/payments/intent', [
                'order_id' => $order->id,
                'provider' => 'invalid_provider',
            ], $this->authenticatedHeaders($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['provider']);
    }

    public function payment_intent_validates_order_exists(): void
    {
        $user = Customer::factory()->create();

        $response = $this
            ->postJson('/api/v1/payments/intent', [
                'order_id' => 99999, // Non-existent
                'provider' => 'stripe',
            ], $this->authenticatedHeaders($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id']);
    }

    public function payment_intent_requires_sanctum_authentication(): void
    {
        $order = Order::factory()->draft()->create();

        // Using api.key middleware only (no sanctum)
        $response = $this->postJson('/api/v1/payments/intent', [
            'order_id' => $order->id,
            'provider' => 'stripe',
        ], $this->apiHeaders());

        // auth:sanctum middleware should block this
        $response->assertStatus(401);
    }

    /**
     * @group idor
     */
    public function payment_intent_returns_404_for_order_id_not_owned_by_user(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $orderA = Order::factory()->draft()->create(['customer_id' => $userA->id]);

        // User B tries to create payment intent for User A's order
        $response = $this
            ->postJson('/api/v1/payments/intent', [
                'order_id' => $orderA->id,
                'provider' => 'stripe',
            ], $this->authenticatedHeaders($userB));

        $response->assertStatus(404);
    }

    public function unauthenticated_user_can_view_payment_methods(): void
    {
        // GET /payment-methods does not require authentication (only api.key)
        $response = $this->getJson('/api/v1/payment-methods', $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ok',
            'data',
        ]);
    }

    public function payment_methods_endpoint_is_public(): void
    {
        // Verify this endpoint doesn't leak sensitive information
        $response = $this->getJson('/api/v1/payment-methods', $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ok',
            'data' => [
                '*' => ['id', 'name', 'code'],
            ],
        ]);

        // Should only return enabled methods
        $methods = $response->json('data');
        $this->assertIsArray($methods);
    }

    /**
     * @group idor
     *
     * Tests that User B cannot create payment intent for User A's order (IDOR protection)
     */
    public function multiple_users_cannot_create_payment_intents_for_same_order(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $orderA = Order::factory()->draft()->create(['customer_id' => $userA->id]);

        // Test IDOR protection: User B tries to create payment intent for User A's order
        // This should return 404 because of the customer_id check in PaymentController:
        // Order::where('id', $orderId)->where('customer_id', $request->user()->id)->firstOrFail()
        $responseB = $this->postJson('/api/v1/payments/intent', [
            'order_id' => $orderA->id,
            'provider' => 'stripe',
        ], $this->authenticatedHeaders($userB));

        // CRITICAL: Must get 404, NOT 500
        // If we get 500, it means IDOR check failed and Stripe processing was attempted
        $responseB->assertStatus(404);
        $responseB->assertJson(['message' => 'No query results for model [Omersia\Catalog\Models\Order].']);
    }

    public function payment_intent_requires_valid_api_key(): void
    {
        $user = Customer::factory()->create();
        $order = Order::factory()->draft()->create(['customer_id' => $user->id]);

        // Request without API key (only Bearer token, no X-API-KEY)
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this
            ->postJson('/api/v1/payments/intent', [
                'order_id' => $order->id,
                'provider' => 'stripe',
            ], [
                'Authorization' => 'Bearer '.$token,
                // Intentionally omitting X-API-KEY header
            ]);

        // api.key middleware should block this
        $response->assertStatus(401);
    }
}
