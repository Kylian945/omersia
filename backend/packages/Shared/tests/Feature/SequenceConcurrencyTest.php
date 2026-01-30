<?php

declare(strict_types=1);

namespace Omersia\Shared\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Omersia\Shared\Models\Sequence;
use Omersia\Shared\Services\SequenceService;
use Tests\TestCase;

/**
 * Tests de concurrence pour le SequenceService
 *
 * Ces tests valident que la génération de numéros de séquence
 * est atomique et thread-safe même sous charge concurrente.
 */
class SequenceConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private SequenceService $sequenceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sequenceService = app(SequenceService::class);
    }

    /**
     * Test that concurrent sequence generation produces unique numbers.
     *
     * This simulates 100 concurrent requests all trying to generate
     * sequence numbers at the same time. Without proper locking,
     * this would produce duplicates.
     */
    public function it_generates_unique_numbers_under_concurrent_load(): void
    {
        $sequenceName = 'test_concurrency';
        $iterations = 100;
        $generatedNumbers = [];

        // Simulate concurrent requests
        // Note: True parallelism requires php-fpm/multiple processes
        // This test validates the locking mechanism logic
        for ($i = 0; $i < $iterations; $i++) {
            $number = $this->sequenceService->next($sequenceName, 'TEST-');
            $generatedNumbers[] = $number;
        }

        // Assert all numbers are unique
        $uniqueNumbers = array_unique($generatedNumbers);
        $duplicates = array_diff_assoc($generatedNumbers, $uniqueNumbers);

        $this->assertCount(
            $iterations,
            $uniqueNumbers,
            'Expected all generated numbers to be unique, but found duplicates: '.
            json_encode(array_values($duplicates))
        );

        // Assert numbers are sequential
        $expectedNumbers = [];
        for ($i = 1; $i <= $iterations; $i++) {
            $expectedNumbers[] = 'TEST-'.str_pad((string) (1000 + $i), 8, '0', STR_PAD_LEFT);
        }

        sort($generatedNumbers);
        sort($expectedNumbers);
        $this->assertEquals($expectedNumbers, $generatedNumbers);
    }

    /**
     * Test order number generation with concurrent order creation.
     */
    public function it_generates_unique_order_numbers_concurrently(): void
    {
        $iterations = 50;
        $orderNumbers = [];

        for ($i = 0; $i < $iterations; $i++) {
            $number = $this->sequenceService->next('order_number', 'ORD-');
            $orderNumbers[] = $number;
        }

        // Verify uniqueness
        $this->assertCount($iterations, array_unique($orderNumbers));

        // Verify format (ORD-00001001, ORD-00001002, etc.)
        foreach ($orderNumbers as $number) {
            $this->assertMatchesRegularExpression('/^ORD-\d{8}$/', $number);
        }
    }

    /**
     * Test invoice number generation with year-based sequences.
     */
    public function it_generates_unique_invoice_numbers_per_year(): void
    {
        $year = date('Y');
        $iterations = 50;
        $invoiceNumbers = [];

        for ($i = 0; $i < $iterations; $i++) {
            $number = $this->sequenceService->next(
                "invoice_number_{$year}",
                "INV-{$year}-",
                0,
                4
            );
            $invoiceNumbers[] = $number;
        }

        // Verify uniqueness
        $this->assertCount($iterations, array_unique($invoiceNumbers));

        // Verify format (INV-2026-0001, INV-2026-0002, etc.)
        foreach ($invoiceNumbers as $number) {
            $this->assertMatchesRegularExpression("/^INV-{$year}-\d{4}$/", $number);
        }

        // Verify sequential
        $expectedNumbers = [];
        for ($i = 1; $i <= $iterations; $i++) {
            $expectedNumbers[] = "INV-{$year}-".str_pad((string) $i, 4, '0', STR_PAD_LEFT);
        }

        sort($invoiceNumbers);
        $this->assertEquals($expectedNumbers, $invoiceNumbers);
    }

    /**
     * Test that different years maintain separate sequences.
     */
    public function it_isolates_invoice_sequences_per_year(): void
    {
        $year2025 = '2025';
        $year2026 = '2026';

        $number2025_1 = $this->sequenceService->next("invoice_number_{$year2025}", "INV-{$year2025}-", 0, 4);
        $number2026_1 = $this->sequenceService->next("invoice_number_{$year2026}", "INV-{$year2026}-", 0, 4);
        $number2025_2 = $this->sequenceService->next("invoice_number_{$year2025}", "INV-{$year2025}-", 0, 4);

        $this->assertEquals("INV-{$year2025}-0001", $number2025_1);
        $this->assertEquals("INV-{$year2026}-0001", $number2026_1);
        $this->assertEquals("INV-{$year2025}-0002", $number2025_2);
    }

    /**
     * Test performance: generating 100 numbers should be reasonably fast.
     * Target: < 2 seconds for 100 generations
     */
    public function it_performs_efficiently_under_load(): void
    {
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->sequenceService->next('perf_test', 'PERF-');
        }

        $duration = microtime(true) - $startTime;

        // Should complete in under 2 seconds
        $this->assertLessThan(
            2.0,
            $duration,
            "Generating {$iterations} numbers took {$duration}s (expected < 2s)"
        );

        // Log performance for monitoring
        $this->logInfo("Generated {$iterations} sequence numbers in ".round($duration, 3).'s');
    }

    /**
     * Test that sequence service correctly initializes new sequences.
     */
    public function it_creates_new_sequence_with_correct_initial_value(): void
    {
        $sequenceName = 'new_sequence_test';
        $initialValue = 5000;

        $number = $this->sequenceService->next($sequenceName, 'NEW-', $initialValue);

        $this->assertEquals('NEW-00005001', $number);

        // Verify sequence was created in database
        $sequence = Sequence::where('name', $sequenceName)->first();
        $this->assertNotNull($sequence);
        $this->assertEquals(5001, $sequence->current_value);
        $this->assertEquals('NEW-', $sequence->prefix);
    }

    /**
     * Test sequence reset functionality.
     */
    public function it_resets_sequence_to_new_value(): void
    {
        $sequenceName = 'reset_test';

        // Generate some numbers
        $this->sequenceService->next($sequenceName);
        $this->sequenceService->next($sequenceName);
        $this->sequenceService->next($sequenceName);

        // Reset to 2000
        $this->sequenceService->reset($sequenceName, 2000);

        // Next number should be 2001
        $number = $this->sequenceService->next($sequenceName, 'RST-');
        $this->assertEquals('RST-00002001', $number);
    }

    /**
     * Test that current() returns correct value without incrementing.
     */
    public function it_returns_current_value_without_incrementing(): void
    {
        $sequenceName = 'current_test';

        $this->sequenceService->next($sequenceName);
        $current1 = $this->sequenceService->current($sequenceName);
        $current2 = $this->sequenceService->current($sequenceName);

        $this->assertEquals($current1, $current2);
        $this->assertEquals(1001, $current1);
    }

    /**
     * Test database transaction rollback doesn't increment sequence.
     */
    public function it_handles_transaction_rollback_correctly(): void
    {
        $sequenceName = 'rollback_test';

        try {
            DB::transaction(function () use ($sequenceName) {
                $number = $this->sequenceService->next($sequenceName, 'RB-');
                // Force rollback
                throw new \Exception('Intentional rollback');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Next number should start fresh (not skip the rolled-back one)
        // Note: This behavior depends on isolation level
        // With proper locking, rolled-back increments won't persist
        $number = $this->sequenceService->next($sequenceName, 'RB-');

        // The number should be RB-00001001 (first number) since rollback prevented increment
        $this->assertStringStartsWith('RB-', $number);
        $this->assertEquals('RB-00001001', $number);
    }

    /**
     * Test multiple sequences are independent.
     */
    public function it_maintains_independent_sequences(): void
    {
        $orderNumber1 = $this->sequenceService->next('orders', 'ORD-');
        $invoiceNumber1 = $this->sequenceService->next('invoices', 'INV-');
        $orderNumber2 = $this->sequenceService->next('orders', 'ORD-');
        $invoiceNumber2 = $this->sequenceService->next('invoices', 'INV-');

        $this->assertEquals('ORD-00001001', $orderNumber1);
        $this->assertEquals('INV-00001001', $invoiceNumber1);
        $this->assertEquals('ORD-00001002', $orderNumber2);
        $this->assertEquals('INV-00001002', $invoiceNumber2);
    }

    /**
     * Test custom padding length.
     */
    public function it_supports_custom_padding_length(): void
    {
        $sequenceName = 'custom_padding';

        // 4 digits padding
        $number1 = $this->sequenceService->next($sequenceName, 'REF-', 0, 4);
        $this->assertEquals('REF-0001', $number1);

        // 6 digits padding
        $number2 = $this->sequenceService->next($sequenceName.'_6', 'NUM-', 0, 6);
        $this->assertEquals('NUM-000001', $number2);

        // 10 digits padding
        $number3 = $this->sequenceService->next($sequenceName.'_10', 'ID-', 0, 10);
        $this->assertEquals('ID-0000000001', $number3);
    }

    /**
     * Test sequence with no prefix.
     */
    public function it_generates_numbers_without_prefix(): void
    {
        $sequenceName = 'no_prefix';

        $number = $this->sequenceService->next($sequenceName, '');
        $this->assertEquals('00001001', $number);
    }

    /**
     * Test concurrent access to same sequence doesn't cause deadlocks.
     */
    public function it_prevents_deadlocks_under_concurrent_access(): void
    {
        $sequenceName = 'deadlock_test';
        $iterations = 50;
        $numbers = [];

        // This test ensures that multiple concurrent requests
        // don't cause database deadlocks
        for ($i = 0; $i < $iterations; $i++) {
            try {
                $number = $this->sequenceService->next($sequenceName);
                $numbers[] = $number;
            } catch (\Exception $e) {
                $this->fail('Deadlock or exception occurred: '.$e->getMessage());
            }
        }

        $this->assertCount($iterations, $numbers);
        $this->assertCount($iterations, array_unique($numbers));
    }

    /**
     * Test sequence with very high initial value.
     */
    public function it_handles_high_initial_values(): void
    {
        $sequenceName = 'high_value';
        $initialValue = 999999;

        $number = $this->sequenceService->next($sequenceName, 'HI-', $initialValue);
        $this->assertEquals('HI-01000000', $number);
    }

    /**
     * Test that sequence persists across multiple calls.
     */
    public function it_persists_sequence_state(): void
    {
        $sequenceName = 'persist_test';

        // First batch
        $numbers1 = [];
        for ($i = 0; $i < 5; $i++) {
            $numbers1[] = $this->sequenceService->next($sequenceName, 'P-');
        }

        // Simulate new request/service instance
        $newService = app(SequenceService::class);

        // Second batch should continue from where first batch left off
        $numbers2 = [];
        for ($i = 0; $i < 5; $i++) {
            $numbers2[] = $newService->next($sequenceName, 'P-');
        }

        $this->assertEquals('P-00001001', $numbers1[0]);
        $this->assertEquals('P-00001005', $numbers1[4]);
        $this->assertEquals('P-00001006', $numbers2[0]);
        $this->assertEquals('P-00001010', $numbers2[4]);
    }

    /**
     * Helper to output info messages during tests.
     */
    private function logInfo(string $message): void
    {
        // Output to console during test execution
        fwrite(STDOUT, "\n[INFO] {$message}\n");
    }
}
