<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Omersia\Customer\Models\Address;
use Omersia\Customer\Models\Customer;
use Tests\TestCase;
use Tests\WithApiKey;

/**
 * @group security
 * @group authorization
 * @group idor
 * @group address
 */
class AddressAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
    }

    public function unauthenticated_user_cannot_list_addresses(): void
    {
        $response = $this->getJson('/api/v1/addresses', $this->apiHeaders());

        $response->assertStatus(401);
    }

    public function user_can_list_only_their_own_addresses(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $addressA1 = Address::factory()->create(['customer_id' => $userA->id]);
        $addressA2 = Address::factory()->create(['customer_id' => $userA->id]);
        $addressB = Address::factory()->create(['customer_id' => $userB->id]);

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/addresses', $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJsonCount(2);

        $returnedIds = collect($response->json())->pluck('id')->toArray();

        $this->assertContains($addressA1->id, $returnedIds);
        $this->assertContains($addressA2->id, $returnedIds);
        $this->assertNotContains($addressB->id, $returnedIds);
    }

    /**
     * @group idor
     */
    public function user_cannot_view_other_user_address(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $addressB = Address::factory()->create(['customer_id' => $userB->id]);

        $response = $this->getJson('/api/v1/addresses/'.$addressB->id, $this->authenticatedHeaders($userA));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not found']);
    }

    public function user_can_view_their_own_address(): void
    {
        $user = Customer::factory()->create();
        $address = Address::factory()->create(['customer_id' => $user->id]);

        $response = $this->getJson('/api/v1/addresses/'.$address->id, $this->authenticatedHeaders($user));

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $address->id]);
    }

    public function unauthenticated_user_cannot_view_address(): void
    {
        $address = Address::factory()->create();

        $response = $this->getJson('/api/v1/addresses/'.$address->id, [
            'X-API-KEY' => config('app.front_api_key'),
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * @group idor
     */
    public function user_cannot_update_other_user_address(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $addressB = Address::factory()->create([
            'customer_id' => $userB->id,
            'line1' => 'Original Street',
        ]);

        $response = $this->putJson('/api/v1/addresses/'.$addressB->id, [
            'label' => 'Home',
            'line1' => 'Hacked Street',
            'postcode' => '75001',
            'city' => 'Paris',
        ], $this->authenticatedHeaders($userA));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not found']);

        // Verify address was not modified
        $this->assertEquals('Original Street', $addressB->fresh()->line1);
    }

    public function user_can_update_their_own_address(): void
    {
        $user = Customer::factory()->create();
        $address = Address::factory()->create([
            'customer_id' => $user->id,
            'line1' => 'Old Street',
        ]);

        $response = $this->putJson('/api/v1/addresses/'.$address->id, [
            'label' => 'Home',
            'line1' => 'New Street',
            'postcode' => '75001',
            'city' => 'Paris',
        ], $this->authenticatedHeaders($user));

        $response->assertStatus(200);
        $response->assertJsonFragment(['line1' => 'New Street']);

        $this->assertEquals('New Street', $address->fresh()->line1);
    }

    public function unauthenticated_user_cannot_update_address(): void
    {
        $address = Address::factory()->create();

        $response = $this->putJson('/api/v1/addresses/'.$address->id, [
            'label' => 'Home',
            'line1' => 'Test Street',
            'postcode' => '75001',
            'city' => 'Paris',
        ], [
            'X-API-KEY' => config('app.front_api_key'),
        ]);

        $response->assertStatus(401);
    }

    /**
     * @group idor
     */
    public function user_cannot_delete_other_user_address(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $addressB = Address::factory()->create(['customer_id' => $userB->id]);

        $response = $this->deleteJson('/api/v1/addresses/'.$addressB->id, [], $this->authenticatedHeaders($userA));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not found']);

        // Verify address still exists
        $this->assertDatabaseHas('addresses', ['id' => $addressB->id]);
    }

    public function user_can_delete_their_own_address(): void
    {
        $user = Customer::factory()->create();
        $address = Address::factory()->create(['customer_id' => $user->id]);

        $response = $this->deleteJson('/api/v1/addresses/'.$address->id, [], $this->authenticatedHeaders($user));

        $response->assertStatus(204);

        // Verify address was deleted
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function unauthenticated_user_cannot_delete_address(): void
    {
        $address = Address::factory()->create();

        $response = $this->deleteJson('/api/v1/addresses/'.$address->id, [], [
            'X-API-KEY' => config('app.front_api_key'),
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function user_can_create_address(): void
    {
        $user = Customer::factory()->create();

        $response = $this->postJson('/api/v1/addresses', [
            'label' => 'Home',
            'line1' => '123 Test Street',
            'line2' => 'Apt 4B',
            'postcode' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
            'phone' => '+33612345678',
        ], $this->authenticatedHeaders($user));

        $response->assertStatus(201);
        $response->assertJsonFragment(['line1' => '123 Test Street']);

        // Verify address was created with correct customer_id
        $this->assertDatabaseHas('addresses', [
            'customer_id' => $user->id,
            'line1' => '123 Test Street',
        ]);
    }

    public function unauthenticated_user_cannot_create_address(): void
    {
        $response = $this->postJson('/api/v1/addresses', [
            'label' => 'Home',
            'line1' => '123 Test Street',
            'postcode' => '75001',
            'city' => 'Paris',
        ], [
            'X-API-KEY' => config('app.front_api_key'),
        ]);

        $response->assertStatus(401);
    }

    /**
     * @group idor
     */
    public function user_cannot_set_default_shipping_for_other_user_address(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $addressB = Address::factory()->create(['customer_id' => $userB->id]);

        $response = $this->postJson('/api/v1/addresses/'.$addressB->id.'/default-shipping', [], $this->authenticatedHeaders($userA));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not found']);

        // Verify flag was not changed
        $this->assertFalse($addressB->fresh()->is_default_shipping);
    }

    public function user_can_set_default_shipping_for_own_address(): void
    {
        $user = Customer::factory()->create();
        $address = Address::factory()->create(['customer_id' => $user->id]);

        $response = $this->postJson('/api/v1/addresses/'.$address->id.'/default-shipping', [], $this->authenticatedHeaders($user));

        $response->assertStatus(200);
        $response->assertJsonFragment(['is_default_shipping' => true]);

        $this->assertTrue($address->fresh()->is_default_shipping);
    }

    /**
     * @group idor
     */
    public function user_cannot_set_default_billing_for_other_user_address(): void
    {
        $userA = Customer::factory()->create();
        $userB = Customer::factory()->create();

        $addressB = Address::factory()->create(['customer_id' => $userB->id]);

        $response = $this->postJson('/api/v1/addresses/'.$addressB->id.'/default-billing', [], $this->authenticatedHeaders($userA));

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not found']);

        // Verify flag was not changed
        $this->assertFalse($addressB->fresh()->is_default_billing);
    }

    public function user_can_set_default_billing_for_own_address(): void
    {
        $user = Customer::factory()->create();
        $address = Address::factory()->create(['customer_id' => $user->id]);

        $response = $this->postJson('/api/v1/addresses/'.$address->id.'/default-billing', [], $this->authenticatedHeaders($user));

        $response->assertStatus(200);
        $response->assertJsonFragment(['is_default_billing' => true]);

        $this->assertTrue($address->fresh()->is_default_billing);
    }

    public function setting_default_shipping_removes_flag_from_other_addresses(): void
    {
        $user = Customer::factory()->create();

        $address1 = Address::factory()->defaultShipping()->create(['customer_id' => $user->id]);
        $address2 = Address::factory()->create(['customer_id' => $user->id]);

        $this->assertTrue($address1->is_default_shipping);

        // Set address2 as default
        $response = $this->postJson('/api/v1/addresses/'.$address2->id.'/default-shipping', [], $this->authenticatedHeaders($user));

        $response->assertStatus(200);

        // Verify address1 is no longer default
        $this->assertFalse($address1->fresh()->is_default_shipping);
        $this->assertTrue($address2->fresh()->is_default_shipping);
    }

    public function setting_default_billing_removes_flag_from_other_addresses(): void
    {
        $user = Customer::factory()->create();

        $address1 = Address::factory()->defaultBilling()->create(['customer_id' => $user->id]);
        $address2 = Address::factory()->create(['customer_id' => $user->id]);

        $this->assertTrue($address1->is_default_billing);

        // Set address2 as default
        $response = $this->postJson('/api/v1/addresses/'.$address2->id.'/default-billing', [], $this->authenticatedHeaders($user));

        $response->assertStatus(200);

        // Verify address1 is no longer default
        $this->assertFalse($address1->fresh()->is_default_billing);
        $this->assertTrue($address2->fresh()->is_default_billing);
    }

    public function returns_404_for_invalid_address_id(): void
    {
        $user = Customer::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/addresses/99999', $this->apiHeaders());

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Not found']);
    }

    public function address_list_is_sorted_by_default_flags_and_label(): void
    {
        $user = Customer::factory()->create();

        $address1 = Address::factory()->create([
            'customer_id' => $user->id,
            'label' => 'Work',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);

        $address2 = Address::factory()->create([
            'customer_id' => $user->id,
            'label' => 'Home',
            'is_default_shipping' => true,
            'is_default_billing' => false,
        ]);

        $address3 = Address::factory()->create([
            'customer_id' => $user->id,
            'label' => 'Other',
            'is_default_shipping' => false,
            'is_default_billing' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/addresses', $this->apiHeaders());

        $response->assertStatus(200);

        $ids = collect($response->json())->pluck('id')->toArray();

        // Default shipping first, then default billing, then alphabetical
        $this->assertEquals($address2->id, $ids[0]); // Default shipping (Home)
        $this->assertEquals($address3->id, $ids[1]); // Default billing (Other)
        $this->assertEquals($address1->id, $ids[2]); // Regular address (Work)
    }

    public function address_creation_validates_required_fields(): void
    {
        $user = Customer::factory()->create();

        $response = $this->postJson('/api/v1/addresses', [
            // Missing required fields
        ], $this->authenticatedHeaders($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['label', 'line1', 'postcode', 'city']);
    }
}
