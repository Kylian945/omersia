<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Sequence;
use Omersia\Catalog\Services\SequenceService;
use Tests\TestCase;

class SequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private SequenceService $sequenceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sequenceService = app(SequenceService::class);
    }

    public function test_generates_first_sequence_number(): void
    {
        $number = $this->sequenceService->next('test_sequence', 'TEST-', 1000, 4);

        $this->assertEquals('TEST-1001', $number);
        $this->assertDatabaseHas('sequences', [
            'name' => 'test_sequence',
            'prefix' => 'TEST-',
            'current_value' => 1001,
            'padding' => 4,
        ]);
    }

    public function test_generates_sequential_numbers(): void
    {
        $number1 = $this->sequenceService->next('order_number', 'ORD-');
        $number2 = $this->sequenceService->next('order_number', 'ORD-');
        $number3 = $this->sequenceService->next('order_number', 'ORD-');

        $this->assertEquals('ORD-00001001', $number1);
        $this->assertEquals('ORD-00001002', $number2);
        $this->assertEquals('ORD-00001003', $number3);
    }

    public function test_handles_different_sequences_independently(): void
    {
        $orderNumber = $this->sequenceService->next('order_number', 'ORD-');
        $invoiceNumber = $this->sequenceService->next('invoice_number_2026', 'INV-2026-', 0, 4);

        $this->assertEquals('ORD-00001001', $orderNumber);
        $this->assertEquals('INV-2026-0001', $invoiceNumber);

        // Second calls
        $orderNumber2 = $this->sequenceService->next('order_number', 'ORD-');
        $invoiceNumber2 = $this->sequenceService->next('invoice_number_2026', 'INV-2026-', 0, 4);

        $this->assertEquals('ORD-00001002', $orderNumber2);
        $this->assertEquals('INV-2026-0002', $invoiceNumber2);
    }

    public function test_current_returns_sequence_value(): void
    {
        $this->sequenceService->next('test_sequence', 'TEST-', 1000, 4);
        $this->sequenceService->next('test_sequence', 'TEST-', 1000, 4);

        $current = $this->sequenceService->current('test_sequence');

        $this->assertEquals(1002, $current);
    }

    public function test_current_returns_null_for_non_existent_sequence(): void
    {
        $current = $this->sequenceService->current('non_existent');

        $this->assertNull($current);
    }

    public function test_reset_updates_sequence_value(): void
    {
        $this->sequenceService->next('test_sequence', 'TEST-', 1000, 4);
        $this->sequenceService->reset('test_sequence', 5000);

        $current = $this->sequenceService->current('test_sequence');
        $this->assertEquals(5000, $current);

        $nextNumber = $this->sequenceService->next('test_sequence', 'TEST-', 1000, 4);
        $this->assertEquals('TEST-5001', $nextNumber);
    }

    public function test_handles_concurrent_sequence_generation(): void
    {
        // Simulate concurrent access by creating sequence manually
        Sequence::create([
            'name' => 'concurrent_test',
            'prefix' => 'CON-',
            'current_value' => 1000,
            'padding' => 4,
        ]);

        // Multiple calls should be sequential
        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = $this->sequenceService->next('concurrent_test', 'CON-', 1000, 4);
        }

        // Verify all numbers are unique and sequential
        $this->assertCount(10, $numbers);
        $this->assertCount(10, array_unique($numbers));

        $expected = [
            'CON-1001',
            'CON-1002',
            'CON-1003',
            'CON-1004',
            'CON-1005',
            'CON-1006',
            'CON-1007',
            'CON-1008',
            'CON-1009',
            'CON-1010',
        ];

        $this->assertEquals($expected, $numbers);
    }

    public function test_uses_existing_sequence_prefix(): void
    {
        // Create sequence with prefix
        Sequence::create([
            'name' => 'prefixed_test',
            'prefix' => 'ORIG-',
            'current_value' => 100,
            'padding' => 6,
        ]);

        // Call with different prefix - should use stored prefix
        $number = $this->sequenceService->next('prefixed_test', 'NEW-', 1000, 4);

        $this->assertEquals('ORIG-000101', $number);
    }

    public function test_padding_formats_correctly(): void
    {
        $number1 = $this->sequenceService->next('padding_test_4', 'P4-', 0, 4);
        $number2 = $this->sequenceService->next('padding_test_8', 'P8-', 0, 8);
        $number3 = $this->sequenceService->next('padding_test_2', 'P2-', 0, 2);

        $this->assertEquals('P4-0001', $number1);
        $this->assertEquals('P8-00000001', $number2);
        $this->assertEquals('P2-01', $number3);
    }
}
