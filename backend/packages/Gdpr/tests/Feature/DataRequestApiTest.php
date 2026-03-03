<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Omersia\Customer\Models\Customer;
use Omersia\Gdpr\Models\DataRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\WithApiKey;

class DataRequestApiTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Storage::fake('local');
        $this->setUpApiKey();
    }

    private function customerHeaders(Customer $customer): array
    {
        $token = $customer->createToken('test-token')->plainTextToken;

        return array_merge($this->apiHeaders(), [
            'Authorization' => 'Bearer '.$token,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/gdpr/data-requests
    // -------------------------------------------------------------------------

    #[Test]
    public function it_requires_authentication_for_index(): void
    {
        $response = $this->getJson('/api/v1/gdpr/data-requests', $this->apiHeaders());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_api_key_for_index(): void
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/gdpr/data-requests', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_empty_list_when_no_requests(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson('/api/v1/gdpr/data-requests', $this->customerHeaders($customer));

        $response->assertOk()
            ->assertJson([]);
    }

    #[Test]
    public function it_returns_only_requests_for_authenticated_customer(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'access',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        DataRequest::create([
            'customer_id' => $otherCustomer->id,
            'type' => 'export',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/gdpr/data-requests', $this->customerHeaders($customer));

        $response->assertOk();
        $data = $response->json();

        $this->assertCount(1, $data);
        $this->assertEquals($customer->id, $data[0]['customer_id']);
    }

    #[Test]
    public function it_returns_all_request_types_for_customer(): void
    {
        $customer = Customer::factory()->create();

        foreach (['access', 'export', 'deletion', 'rectification'] as $type) {
            DataRequest::create([
                'customer_id' => $customer->id,
                'type' => $type,
                'status' => 'pending',
                'requested_at' => now(),
            ]);
        }

        $response = $this->getJson('/api/v1/gdpr/data-requests', $this->customerHeaders($customer));

        $response->assertOk();
        $this->assertCount(4, $response->json());
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/gdpr/data-requests
    // -------------------------------------------------------------------------

    #[Test]
    public function it_requires_authentication_for_store(): void
    {
        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'access',
        ], $this->apiHeaders());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_creates_access_request_with_valid_data(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'access',
        ], $this->customerHeaders($customer));

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'request' => [
                    'id',
                    'customer_id',
                    'type',
                    'status',
                    'requested_at',
                ],
            ])
            ->assertJsonPath('request.type', 'access')
            ->assertJsonPath('request.status', 'pending')
            ->assertJsonPath('request.customer_id', $customer->id);

        $this->assertDatabaseHas('data_requests', [
            'customer_id' => $customer->id,
            'type' => 'access',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function it_creates_export_request(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'export',
            'reason' => 'I want a backup.',
        ], $this->customerHeaders($customer));

        $response->assertCreated()
            ->assertJsonPath('request.type', 'export');

        $this->assertDatabaseHas('data_requests', [
            'customer_id' => $customer->id,
            'type' => 'export',
        ]);
    }

    #[Test]
    public function it_creates_deletion_request(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'deletion',
            'reason' => 'I want to be forgotten.',
        ], $this->customerHeaders($customer));

        $response->assertCreated()
            ->assertJsonPath('request.type', 'deletion');
    }

    #[Test]
    public function it_creates_rectification_request(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'rectification',
        ], $this->customerHeaders($customer));

        $response->assertCreated()
            ->assertJsonPath('request.type', 'rectification');
    }

    #[Test]
    public function it_validates_required_type_field(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/gdpr/data-requests', [], $this->customerHeaders($customer));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function it_validates_type_must_be_in_allowed_values(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'invalid_type',
        ], $this->customerHeaders($customer));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function it_validates_reason_max_length(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'access',
            'reason' => str_repeat('a', 1001),
        ], $this->customerHeaders($customer));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    #[Test]
    public function it_rejects_duplicate_pending_request_of_same_type(): void
    {
        $customer = Customer::factory()->create();

        // Première demande
        DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'access',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Deuxième demande du meme type
        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'access',
        ], $this->customerHeaders($customer));

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Vous avez déjà une demande de ce type en cours de traitement.');

        $this->assertDatabaseCount('data_requests', 1);
    }

    #[Test]
    public function it_rejects_duplicate_processing_request_of_same_type(): void
    {
        $customer = Customer::factory()->create();

        DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'export',
            'status' => 'processing',
            'requested_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'export',
        ], $this->customerHeaders($customer));

        $response->assertStatus(422);
    }

    #[Test]
    public function it_allows_new_request_when_previous_is_completed(): void
    {
        $customer = Customer::factory()->create();

        DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'access',
            'status' => 'completed',
            'requested_at' => now()->subWeek(),
        ]);

        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'access',
        ], $this->customerHeaders($customer));

        $response->assertCreated();
        $this->assertDatabaseCount('data_requests', 2);
    }

    #[Test]
    public function it_allows_different_types_simultaneously(): void
    {
        $customer = Customer::factory()->create();

        DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'access',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Type différent - pas de blocage
        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'export',
        ], $this->customerHeaders($customer));

        $response->assertCreated();
    }

    #[Test]
    public function it_accepts_nullable_reason(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/gdpr/data-requests', [
            'type' => 'access',
            'reason' => null,
        ], $this->customerHeaders($customer));

        $response->assertCreated();
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/gdpr/data-requests/{id}/download
    // -------------------------------------------------------------------------

    #[Test]
    public function it_requires_authentication_for_download(): void
    {
        $response = $this->getJson('/api/v1/gdpr/data-requests/1/download', $this->apiHeaders());

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_404_when_request_not_found(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson('/api/v1/gdpr/data-requests/99999/download', $this->customerHeaders($customer));

        $response->assertNotFound();
    }

    #[Test]
    public function it_returns_404_when_request_belongs_to_other_customer(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $path = 'gdpr/exports/other.json';
        Storage::disk('local')->put($path, '{"data": "test"}');

        $request = DataRequest::create([
            'customer_id' => $otherCustomer->id,
            'type' => 'export',
            'status' => 'completed',
            'requested_at' => now(),
            'export_file_path' => $path,
            'export_expires_at' => now()->addHours(72),
        ]);

        $response = $this->getJson(
            "/api/v1/gdpr/data-requests/{$request->id}/download",
            $this->customerHeaders($customer)
        );

        $response->assertNotFound();
    }

    #[Test]
    public function it_returns_404_when_request_is_not_completed(): void
    {
        $customer = Customer::factory()->create();

        $request = DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'export',
            'status' => 'pending', // Pas completed
            'requested_at' => now(),
        ]);

        $response = $this->getJson(
            "/api/v1/gdpr/data-requests/{$request->id}/download",
            $this->customerHeaders($customer)
        );

        $response->assertNotFound();
    }

    #[Test]
    public function it_returns_404_when_request_is_not_export_type(): void
    {
        $customer = Customer::factory()->create();

        $request = DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'access', // Pas export
            'status' => 'completed',
            'requested_at' => now(),
        ]);

        $response = $this->getJson(
            "/api/v1/gdpr/data-requests/{$request->id}/download",
            $this->customerHeaders($customer)
        );

        $response->assertNotFound();
    }

    #[Test]
    public function it_returns_410_when_export_file_is_expired(): void
    {
        $customer = Customer::factory()->create();

        $request = DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'export',
            'status' => 'completed',
            'requested_at' => now()->subDays(5),
            'export_file_path' => 'gdpr/exports/expired.json',
            'export_expires_at' => now()->subHour(), // Expiré
        ]);

        $response = $this->getJson(
            "/api/v1/gdpr/data-requests/{$request->id}/download",
            $this->customerHeaders($customer)
        );

        $response->assertStatus(410);
    }

    #[Test]
    public function it_returns_404_when_export_file_not_on_disk(): void
    {
        $customer = Customer::factory()->create();

        $request = DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'export',
            'status' => 'completed',
            'requested_at' => now(),
            'export_file_path' => 'gdpr/exports/missing.json', // Fichier inexistant
            'export_expires_at' => now()->addHours(72),
        ]);

        $response = $this->getJson(
            "/api/v1/gdpr/data-requests/{$request->id}/download",
            $this->customerHeaders($customer)
        );

        $response->assertNotFound();
    }

    #[Test]
    public function it_downloads_export_file_when_available(): void
    {
        $customer = Customer::factory()->create();

        $path = 'gdpr/exports/valid_export.json';
        $exportContent = json_encode([
            'personal_information' => ['email' => $customer->email],
            'export_format' => 'JSON',
        ]);
        Storage::disk('local')->put($path, $exportContent);

        $request = DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'export',
            'status' => 'completed',
            'requested_at' => now(),
            'export_file_path' => $path,
            'export_expires_at' => now()->addHours(72),
        ]);

        $response = $this->getJson(
            "/api/v1/gdpr/data-requests/{$request->id}/download",
            $this->customerHeaders($customer)
        );

        $response->assertOk();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('Content-Disposition')
        );
        $this->assertEquals($exportContent, $response->getContent());
    }
}
