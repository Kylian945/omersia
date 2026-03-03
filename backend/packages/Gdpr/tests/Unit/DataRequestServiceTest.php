<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Omersia\Customer\Models\Customer;
use Omersia\Gdpr\DTO\DataRequestDTO;
use Omersia\Gdpr\Models\DataRequest;
use Omersia\Gdpr\Services\DataRequestService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private DataRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Storage::fake('local');
        $this->service = app(DataRequestService::class);
    }

    private function makeDataRequest(Customer $customer, string $type = 'access', string $status = 'pending'): DataRequest
    {
        return DataRequest::create([
            'customer_id' => $customer->id,
            'type' => $type,
            'status' => $status,
            'requested_at' => now(),
        ]);
    }

    #[Test]
    public function create_request_persists_a_new_data_request(): void
    {
        $customer = Customer::factory()->create();
        $dto = new DataRequestDTO(
            customerId: $customer->id,
            type: 'access',
            reason: 'I want to see my data.',
        );

        $request = $this->service->createRequest($dto);

        $this->assertInstanceOf(DataRequest::class, $request);
        $this->assertEquals($customer->id, $request->customer_id);
        $this->assertEquals('access', $request->type);
        $this->assertEquals('pending', $request->status);
        $this->assertEquals('I want to see my data.', $request->reason);

        $this->assertDatabaseHas('data_requests', [
            'customer_id' => $customer->id,
            'type' => 'access',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function create_request_dispatches_gdpr_request_updated_event(): void
    {
        $customer = Customer::factory()->create();
        $dto = new DataRequestDTO(customerId: $customer->id, type: 'export');

        $this->service->createRequest($dto);

        Event::assertDispatched(\App\Events\Realtime\GdprRequestUpdated::class);
    }

    #[Test]
    public function process_access_request_transitions_to_completed(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        $request = $this->makeDataRequest($customer, 'access');

        $this->service->processAccessRequest($request, $user->id);

        $fresh = $request->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertEquals($user->id, $fresh->processed_by);
        $this->assertNotNull($fresh->processed_at);
        $this->assertNotNull($fresh->completed_at);
    }

    #[Test]
    public function process_access_request_dispatches_events(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        $request = $this->makeDataRequest($customer, 'access');

        $this->service->processAccessRequest($request, $user->id);

        // Doit dispatcher au moins 2 événements (processing + completed)
        Event::assertDispatchedTimes(\App\Events\Realtime\GdprRequestUpdated::class, 2);
    }

    #[Test]
    public function process_export_request_generates_export_file_and_completes(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        $request = $this->makeDataRequest($customer, 'export');

        $this->service->processExportRequest($request, $user->id);

        $fresh = $request->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertNotNull($fresh->export_file_path);
        $this->assertNotNull($fresh->export_expires_at);
    }

    #[Test]
    public function process_export_request_stores_file_on_disk(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        $request = $this->makeDataRequest($customer, 'export');

        $this->service->processExportRequest($request, $user->id);

        $fresh = $request->fresh();
        Storage::disk('local')->assertExists($fresh->export_file_path);
    }

    #[Test]
    public function process_deletion_request_completes_when_customer_can_be_deleted(): void
    {
        $customer = Customer::factory()->create();
        $customerId = $customer->id;
        $user = User::factory()->create();
        // Pas de commandes en cours => suppression possible
        $request = $this->makeDataRequest($customer, 'deletion');

        $this->service->processDeletionRequest($request, $user->id);

        // After deletion, the customer is hard-deleted (data_requests cascade too).
        // We verify the process completed by checking the deletion log was created.
        $this->assertDatabaseHas('data_deletion_logs', [
            'customer_id' => $customerId,
            'deleted_by' => $user->id,
        ]);
        $this->assertDatabaseMissing('customers', ['id' => $customerId]);
    }

    #[Test]
    public function process_deletion_request_rejects_when_customer_has_pending_orders(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        // Créer une commande en cours
        \Omersia\Catalog\Models\Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'processing',
        ]);

        $request = $this->makeDataRequest($customer, 'deletion');

        $this->service->processDeletionRequest($request, $user->id);

        $fresh = $request->fresh();
        $this->assertEquals('rejected', $fresh->status);
        $this->assertNotEmpty($fresh->admin_notes);
    }

    #[Test]
    public function get_pending_requests_returns_only_pending(): void
    {
        $customer = Customer::factory()->create();

        $this->makeDataRequest($customer, 'access', 'pending');
        $this->makeDataRequest($customer, 'export', 'processing');
        $this->makeDataRequest($customer, 'deletion', 'completed');

        $pending = $this->service->getPendingRequests();

        $this->assertCount(1, $pending);
        $this->assertEquals('pending', $pending->first()->status);
    }

    #[Test]
    public function get_pending_requests_eager_loads_customer(): void
    {
        $customer = Customer::factory()->create();
        $this->makeDataRequest($customer, 'access', 'pending');

        $pending = $this->service->getPendingRequests();

        $this->assertTrue($pending->first()->relationLoaded('customer'));
    }

    #[Test]
    public function get_customer_requests_returns_all_requests_for_customer(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $this->makeDataRequest($customer, 'access');
        $this->makeDataRequest($customer, 'export');
        $this->makeDataRequest($otherCustomer, 'deletion');

        $requests = $this->service->getCustomerRequests($customer->id);

        $this->assertCount(2, $requests);
        foreach ($requests as $request) {
            $this->assertEquals($customer->id, $request->customer_id);
        }
    }

    #[Test]
    public function get_customer_requests_returns_empty_collection_when_no_requests(): void
    {
        $requests = $this->service->getCustomerRequests(99999);

        $this->assertCount(0, $requests);
    }

    #[Test]
    public function has_pending_request_returns_true_when_pending_exists(): void
    {
        $customer = Customer::factory()->create();
        $this->makeDataRequest($customer, 'export', 'pending');

        $result = $this->service->hasPendingRequest($customer->id, 'export');

        $this->assertTrue($result);
    }

    #[Test]
    public function has_pending_request_returns_true_when_processing_exists(): void
    {
        $customer = Customer::factory()->create();
        $this->makeDataRequest($customer, 'access', 'processing');

        $result = $this->service->hasPendingRequest($customer->id, 'access');

        $this->assertTrue($result);
    }

    #[Test]
    public function has_pending_request_returns_false_when_completed_exists(): void
    {
        $customer = Customer::factory()->create();
        $this->makeDataRequest($customer, 'deletion', 'completed');

        $result = $this->service->hasPendingRequest($customer->id, 'deletion');

        $this->assertFalse($result);
    }

    #[Test]
    public function has_pending_request_returns_false_for_different_type(): void
    {
        $customer = Customer::factory()->create();
        $this->makeDataRequest($customer, 'access', 'pending');

        $result = $this->service->hasPendingRequest($customer->id, 'export');

        $this->assertFalse($result);
    }

    #[Test]
    public function has_pending_request_returns_false_when_no_requests(): void
    {
        $result = $this->service->hasPendingRequest(99999, 'access');

        $this->assertFalse($result);
    }
}
