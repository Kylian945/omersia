<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Unit;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Customer\Models\Customer;
use Omersia\Gdpr\Models\DataRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataRequestModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(array $overrides = []): DataRequest
    {
        $customer = Customer::factory()->create();

        return DataRequest::create(array_merge([
            'customer_id' => $customer->id,
            'type' => 'access',
            'status' => 'pending',
            'reason' => null,
            'requested_at' => now(),
        ], $overrides));
    }

    #[Test]
    public function it_has_expected_fillable_attributes(): void
    {
        $model = new DataRequest;
        $fillable = $model->getFillable();

        $this->assertContains('customer_id', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('reason', $fillable);
        $this->assertContains('admin_notes', $fillable);
        $this->assertContains('processed_by', $fillable);
        $this->assertContains('requested_at', $fillable);
        $this->assertContains('processed_at', $fillable);
        $this->assertContains('completed_at', $fillable);
        $this->assertContains('export_file_path', $fillable);
        $this->assertContains('export_expires_at', $fillable);
        $this->assertContains('data_deleted', $fillable);
        $this->assertContains('deleted_data_summary', $fillable);
    }

    #[Test]
    public function it_casts_datetime_fields(): void
    {
        $request = $this->makeRequest([
            'requested_at' => now(),
            'processed_at' => now()->addMinute(),
            'completed_at' => now()->addMinutes(5),
            'export_expires_at' => now()->addHours(72),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $request->requested_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $request->processed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $request->completed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $request->export_expires_at);
    }

    #[Test]
    public function it_casts_data_deleted_to_boolean(): void
    {
        $request = $this->makeRequest(['data_deleted' => 1]);

        $this->assertIsBool($request->data_deleted);
        $this->assertTrue($request->data_deleted);
    }

    #[Test]
    public function it_casts_deleted_data_summary_to_array(): void
    {
        $summary = ['deleted_tables' => ['carts'], 'total_deleted' => 3];

        $request = $this->makeRequest(['deleted_data_summary' => $summary]);

        $this->assertIsArray($request->deleted_data_summary);
        $this->assertEquals($summary, $request->deleted_data_summary);
    }

    #[Test]
    public function it_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $request = DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'export',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->assertInstanceOf(BelongsTo::class, $request->customer());
        $this->assertInstanceOf(Customer::class, $request->customer);
        $this->assertEquals($customer->id, $request->customer->id);
    }

    #[Test]
    public function it_belongs_to_processed_by_user(): void
    {
        $user = User::factory()->create();
        $request = $this->makeRequest(['processed_by' => $user->id]);

        $this->assertInstanceOf(BelongsTo::class, $request->processedBy());
        $this->assertInstanceOf(User::class, $request->processedBy);
        $this->assertEquals($user->id, $request->processedBy->id);
    }

    #[Test]
    public function is_pending_returns_true_when_status_is_pending(): void
    {
        $request = $this->makeRequest(['status' => 'pending']);

        $this->assertTrue($request->isPending());
    }

    #[Test]
    public function is_pending_returns_false_when_status_is_not_pending(): void
    {
        foreach (['processing', 'completed', 'rejected'] as $status) {
            $request = $this->makeRequest(['status' => $status]);
            $this->assertFalse($request->isPending(), "Expected isPending() to be false for status '{$status}'");
        }
    }

    #[Test]
    public function is_completed_returns_true_when_status_is_completed(): void
    {
        $request = $this->makeRequest(['status' => 'completed']);

        $this->assertTrue($request->isCompleted());
    }

    #[Test]
    public function is_completed_returns_false_when_status_is_not_completed(): void
    {
        foreach (['pending', 'processing', 'rejected'] as $status) {
            $request = $this->makeRequest(['status' => $status]);
            $this->assertFalse($request->isCompleted(), "Expected isCompleted() to be false for status '{$status}'");
        }
    }

    #[Test]
    public function is_export_available_returns_false_when_no_file_path(): void
    {
        $request = $this->makeRequest([
            'export_file_path' => null,
            'export_expires_at' => now()->addHours(72),
        ]);

        $this->assertFalse($request->isExportAvailable());
    }

    #[Test]
    public function is_export_available_returns_false_when_no_expiry(): void
    {
        $request = $this->makeRequest([
            'export_file_path' => 'gdpr/exports/test.json',
            'export_expires_at' => null,
        ]);

        $this->assertFalse($request->isExportAvailable());
    }

    #[Test]
    public function is_export_available_returns_false_when_expired(): void
    {
        $request = $this->makeRequest([
            'export_file_path' => 'gdpr/exports/test.json',
            'export_expires_at' => now()->subHour(),
        ]);

        $this->assertFalse($request->isExportAvailable());
    }

    #[Test]
    public function is_export_available_returns_true_when_file_exists_and_not_expired(): void
    {
        $request = $this->makeRequest([
            'export_file_path' => 'gdpr/exports/test.json',
            'export_expires_at' => now()->addHours(72),
        ]);

        $this->assertTrue($request->isExportAvailable());
    }

    #[Test]
    public function mark_as_processing_updates_status_and_timestamps(): void
    {
        $user = User::factory()->create();
        $request = $this->makeRequest(['status' => 'pending']);

        $request->markAsProcessing($user->id);

        $this->assertEquals('processing', $request->fresh()->status);
        $this->assertEquals($user->id, $request->fresh()->processed_by);
        $this->assertNotNull($request->fresh()->processed_at);
    }

    #[Test]
    public function mark_as_completed_updates_status_and_completed_at(): void
    {
        $request = $this->makeRequest(['status' => 'processing']);

        $request->markAsCompleted();

        $this->assertEquals('completed', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->completed_at);
    }

    #[Test]
    public function mark_as_rejected_updates_status_and_admin_notes(): void
    {
        $reason = 'Commandes en cours de traitement.';
        $request = $this->makeRequest(['status' => 'processing']);

        $request->markAsRejected($reason);

        $fresh = $request->fresh();
        $this->assertEquals('rejected', $fresh->status);
        $this->assertEquals($reason, $fresh->admin_notes);
        $this->assertNotNull($fresh->completed_at);
    }

    #[Test]
    public function scope_pending_filters_pending_requests(): void
    {
        $customer = Customer::factory()->create();

        DataRequest::create(['customer_id' => $customer->id, 'type' => 'access', 'status' => 'pending', 'requested_at' => now()]);
        DataRequest::create(['customer_id' => $customer->id, 'type' => 'export', 'status' => 'processing', 'requested_at' => now()]);
        DataRequest::create(['customer_id' => $customer->id, 'type' => 'deletion', 'status' => 'completed', 'requested_at' => now()]);

        $pending = DataRequest::pending()->get();

        $this->assertCount(1, $pending);
        $this->assertEquals('pending', $pending->first()->status);
    }

    #[Test]
    public function scope_processing_filters_processing_requests(): void
    {
        $customer = Customer::factory()->create();

        DataRequest::create(['customer_id' => $customer->id, 'type' => 'access', 'status' => 'pending', 'requested_at' => now()]);
        DataRequest::create(['customer_id' => $customer->id, 'type' => 'export', 'status' => 'processing', 'requested_at' => now()]);
        DataRequest::create(['customer_id' => $customer->id, 'type' => 'deletion', 'status' => 'completed', 'requested_at' => now()]);

        $processing = DataRequest::processing()->get();

        $this->assertCount(1, $processing);
        $this->assertEquals('processing', $processing->first()->status);
    }

    #[Test]
    public function scope_completed_filters_completed_requests(): void
    {
        $customer = Customer::factory()->create();

        DataRequest::create(['customer_id' => $customer->id, 'type' => 'access', 'status' => 'pending', 'requested_at' => now()]);
        DataRequest::create(['customer_id' => $customer->id, 'type' => 'deletion', 'status' => 'completed', 'requested_at' => now()]);

        $completed = DataRequest::completed()->get();

        $this->assertCount(1, $completed);
        $this->assertEquals('completed', $completed->first()->status);
    }

    #[Test]
    public function scope_of_type_filters_by_type(): void
    {
        $customer = Customer::factory()->create();

        DataRequest::create(['customer_id' => $customer->id, 'type' => 'access', 'status' => 'pending', 'requested_at' => now()]);
        DataRequest::create(['customer_id' => $customer->id, 'type' => 'export', 'status' => 'pending', 'requested_at' => now()]);
        DataRequest::create(['customer_id' => $customer->id, 'type' => 'deletion', 'status' => 'pending', 'requested_at' => now()]);

        $exportRequests = DataRequest::ofType('export')->get();

        $this->assertCount(1, $exportRequests);
        $this->assertEquals('export', $exportRequests->first()->type);
    }
}
