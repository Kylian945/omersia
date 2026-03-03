<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Omersia\Customer\Models\Customer;
use Omersia\Gdpr\Models\DataRequest;
use Omersia\Gdpr\Services\DataExportService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private DataExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->service = app(DataExportService::class);
    }

    private function makeCompletedExportRequest(Customer $customer): DataRequest
    {
        return DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'export',
            'status' => 'completed',
            'requested_at' => now(),
        ]);
    }

    #[Test]
    public function export_customer_data_returns_expected_top_level_keys(): void
    {
        $customer = Customer::factory()->create();

        $data = $this->service->exportCustomerData($customer);

        $this->assertArrayHasKey('personal_information', $data);
        $this->assertArrayHasKey('addresses', $data);
        $this->assertArrayHasKey('orders', $data);
        $this->assertArrayHasKey('cart', $data);
        $this->assertArrayHasKey('cookie_consents', $data);
        $this->assertArrayHasKey('data_requests', $data);
        $this->assertArrayHasKey('export_date', $data);
        $this->assertArrayHasKey('export_format', $data);
    }

    #[Test]
    public function export_customer_data_includes_personal_information(): void
    {
        $customer = Customer::factory()->create([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@example.com',
        ]);

        $data = $this->service->exportCustomerData($customer);
        $personal = $data['personal_information'];

        $this->assertArrayHasKey('id', $personal);
        $this->assertArrayHasKey('email', $personal);
        $this->assertArrayHasKey('firstname', $personal);
        $this->assertArrayHasKey('lastname', $personal);
        $this->assertArrayHasKey('phone', $personal);
        $this->assertArrayHasKey('date_of_birth', $personal);
        $this->assertArrayHasKey('created_at', $personal);
        $this->assertArrayHasKey('updated_at', $personal);

        $this->assertEquals($customer->id, $personal['id']);
        $this->assertEquals('jean.dupont@example.com', $personal['email']);
        $this->assertEquals('Jean', $personal['firstname']);
        $this->assertEquals('Dupont', $personal['lastname']);
    }

    #[Test]
    public function export_customer_data_sets_export_format_to_json(): void
    {
        $customer = Customer::factory()->create();

        $data = $this->service->exportCustomerData($customer);

        $this->assertEquals('JSON', $data['export_format']);
    }

    #[Test]
    public function export_customer_data_export_date_is_iso8601(): void
    {
        $customer = Customer::factory()->create();

        $data = $this->service->exportCustomerData($customer);

        // Verifier format ISO 8601
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $data['export_date']
        );
    }

    #[Test]
    public function export_customer_data_returns_empty_arrays_for_no_relations(): void
    {
        $customer = Customer::factory()->create();

        $data = $this->service->exportCustomerData($customer);

        $this->assertIsArray($data['addresses']);
        $this->assertEmpty($data['addresses']);

        $this->assertIsArray($data['orders']);
        $this->assertEmpty($data['orders']);

        $this->assertIsArray($data['cookie_consents']);
        $this->assertEmpty($data['cookie_consents']);

        $this->assertIsArray($data['data_requests']);
        $this->assertEmpty($data['data_requests']);

        $this->assertNull($data['cart']);
    }

    #[Test]
    public function generate_export_file_creates_file_on_disk(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->makeCompletedExportRequest($customer);

        $path = $this->service->generateExportFile($request);

        Storage::disk('local')->assertExists($path);
    }

    #[Test]
    public function generate_export_file_returns_path_string(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->makeCompletedExportRequest($customer);

        $path = $this->service->generateExportFile($request);

        $this->assertIsString($path);
        $this->assertStringContainsString('gdpr/exports/', $path);
        $this->assertStringContainsString('.json', $path);
    }

    #[Test]
    public function generate_export_file_updates_request_with_path_and_expiry(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->makeCompletedExportRequest($customer);

        $path = $this->service->generateExportFile($request);

        $fresh = $request->fresh();
        $this->assertEquals($path, $fresh->export_file_path);
        $this->assertNotNull($fresh->export_expires_at);

        // Expiration à 72h
        $expectedExpiry = now()->addHours(72);
        $this->assertEqualsWithDelta($expectedExpiry->timestamp, $fresh->export_expires_at->timestamp, 5);
    }

    #[Test]
    public function generate_export_file_content_is_valid_json(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->makeCompletedExportRequest($customer);

        $path = $this->service->generateExportFile($request);

        $content = Storage::disk('local')->get($path);
        $decoded = json_decode($content, true);

        $this->assertNotNull($decoded);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('personal_information', $decoded);
    }

    #[Test]
    public function get_export_file_content_returns_file_content_when_exists(): void
    {
        $path = 'gdpr/exports/test_file.json';
        $content = json_encode(['test' => 'data']);
        Storage::disk('local')->put($path, $content);

        $result = $this->service->getExportFileContent($path);

        $this->assertEquals($content, $result);
    }

    #[Test]
    public function get_export_file_content_returns_null_when_file_not_found(): void
    {
        $result = $this->service->getExportFileContent('gdpr/exports/nonexistent.json');

        $this->assertNull($result);
    }

    #[Test]
    public function delete_expired_export_file_removes_file(): void
    {
        $path = 'gdpr/exports/to_delete.json';
        Storage::disk('local')->put($path, '{}');

        Storage::disk('local')->assertExists($path);

        $result = $this->service->deleteExpiredExportFile($path);

        $this->assertTrue($result);
        Storage::disk('local')->assertMissing($path);
    }

    #[Test]
    public function clean_expired_exports_deletes_expired_files_and_clears_paths(): void
    {
        $customer = Customer::factory()->create();

        // Demande avec export expiré
        $path = 'gdpr/exports/expired.json';
        Storage::disk('local')->put($path, '{}');

        $expiredRequest = DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'export',
            'status' => 'completed',
            'requested_at' => now()->subDays(4),
            'export_file_path' => $path,
            'export_expires_at' => now()->subHour(), // Expiré
        ]);

        // Demande avec export encore valide
        $validPath = 'gdpr/exports/valid.json';
        Storage::disk('local')->put($validPath, '{}');

        $validRequest = DataRequest::create([
            'customer_id' => $customer->id,
            'type' => 'export',
            'status' => 'completed',
            'requested_at' => now()->subDay(),
            'export_file_path' => $validPath,
            'export_expires_at' => now()->addHours(48), // Valide
        ]);

        $deleted = $this->service->cleanExpiredExports();

        $this->assertEquals(1, $deleted);
        Storage::disk('local')->assertMissing($path);
        Storage::disk('local')->assertExists($validPath);

        $this->assertNull($expiredRequest->fresh()->export_file_path);
        $this->assertEquals($validPath, $validRequest->fresh()->export_file_path);
    }

    #[Test]
    public function clean_expired_exports_returns_zero_when_nothing_to_clean(): void
    {
        $deleted = $this->service->cleanExpiredExports();

        $this->assertEquals(0, $deleted);
    }
}
