<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Unit;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Customer\Models\Customer;
use Omersia\Gdpr\Models\DataDeletionLog;
use Omersia\Gdpr\Models\DataRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataDeletionLogModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeLog(array $overrides = []): DataDeletionLog
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        $dataRequest = DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'deletion',
            'status' => 'completed',
            'requested_at' => now(),
        ]);

        return DataDeletionLog::create(array_merge([
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'data_request_id' => $dataRequest->id,
            'deleted_tables' => ['carts', 'addresses'],
            'anonymized_tables' => ['customers', 'orders'],
            'total_records_deleted' => 5,
            'total_records_anonymized' => 2,
            'deleted_by' => $user->id,
            'deleted_at' => now(),
            'deletion_method' => 'full_deletion',
        ], $overrides));
    }

    #[Test]
    public function it_has_expected_fillable_attributes(): void
    {
        $model = new DataDeletionLog;
        $fillable = $model->getFillable();

        $this->assertContains('customer_id', $fillable);
        $this->assertContains('customer_email', $fillable);
        $this->assertContains('data_request_id', $fillable);
        $this->assertContains('deleted_tables', $fillable);
        $this->assertContains('anonymized_tables', $fillable);
        $this->assertContains('total_records_deleted', $fillable);
        $this->assertContains('total_records_anonymized', $fillable);
        $this->assertContains('deleted_by', $fillable);
        $this->assertContains('deleted_at', $fillable);
        $this->assertContains('deletion_method', $fillable);
        $this->assertContains('notes', $fillable);
    }

    #[Test]
    public function it_casts_deleted_tables_to_array(): void
    {
        $log = $this->makeLog(['deleted_tables' => ['carts', 'addresses', 'cookie_consents']]);

        $this->assertIsArray($log->deleted_tables);
        $this->assertCount(3, $log->deleted_tables);
        $this->assertContains('carts', $log->deleted_tables);
    }

    #[Test]
    public function it_casts_anonymized_tables_to_array(): void
    {
        $log = $this->makeLog(['anonymized_tables' => ['customers', 'orders']]);

        $this->assertIsArray($log->anonymized_tables);
        $this->assertCount(2, $log->anonymized_tables);
        $this->assertContains('orders', $log->anonymized_tables);
    }

    #[Test]
    public function it_casts_deleted_at_to_datetime(): void
    {
        $log = $this->makeLog(['deleted_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->deleted_at);
    }

    #[Test]
    public function it_allows_null_anonymized_tables(): void
    {
        $log = $this->makeLog(['anonymized_tables' => null]);

        $this->assertNull($log->anonymized_tables);
    }

    #[Test]
    public function it_belongs_to_data_request(): void
    {
        $log = $this->makeLog();

        $this->assertInstanceOf(BelongsTo::class, $log->dataRequest());
        $this->assertInstanceOf(DataRequest::class, $log->dataRequest);
    }

    #[Test]
    public function it_belongs_to_deleted_by_user(): void
    {
        $user = User::factory()->create();
        $log = $this->makeLog(['deleted_by' => $user->id]);

        $this->assertInstanceOf(BelongsTo::class, $log->deletedBy());
        $this->assertInstanceOf(User::class, $log->deletedBy);
        $this->assertEquals($user->id, $log->deletedBy->id);
    }

    #[Test]
    public function it_stores_customer_email_for_audit_after_deletion(): void
    {
        $log = $this->makeLog(['customer_email' => 'audited@example.com']);

        $this->assertEquals('audited@example.com', $log->customer_email);
    }

    #[Test]
    public function it_stores_total_records_counts(): void
    {
        $log = $this->makeLog([
            'total_records_deleted' => 10,
            'total_records_anonymized' => 3,
        ]);

        $this->assertEquals(10, $log->total_records_deleted);
        $this->assertEquals(3, $log->total_records_anonymized);
    }

    #[Test]
    public function it_stores_deletion_method(): void
    {
        $log = $this->makeLog(['deletion_method' => 'anonymization']);

        $this->assertEquals('anonymization', $log->deletion_method);
    }

    #[Test]
    public function it_allows_null_data_request_id(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        $log = DataDeletionLog::create([
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'data_request_id' => null,
            'deleted_tables' => ['carts'],
            'total_records_deleted' => 1,
            'total_records_anonymized' => 0,
            'deleted_by' => $user->id,
            'deleted_at' => now(),
            'deletion_method' => 'partial',
        ]);

        $this->assertNull($log->data_request_id);
        $this->assertNull($log->dataRequest);
    }
}
