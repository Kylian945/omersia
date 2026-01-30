<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Invoice;
use Tests\TestCase;

class SequenceGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_generates_unique_sequential_numbers(): void
    {
        $number1 = Invoice::generateNumber();
        $number2 = Invoice::generateNumber();
        $number3 = Invoice::generateNumber();

        $currentYear = date('Y');

        $this->assertEquals("INV-{$currentYear}-0001", $number1);
        $this->assertEquals("INV-{$currentYear}-0002", $number2);
        $this->assertEquals("INV-{$currentYear}-0003", $number3);

        // Verify all numbers are unique
        $this->assertNotEquals($number1, $number2);
        $this->assertNotEquals($number2, $number3);
    }

    public function test_invoice_numbers_reset_per_year(): void
    {
        $currentYear = date('Y');

        // Generate invoices for current year
        $number1 = Invoice::generateNumber();
        $this->assertEquals("INV-{$currentYear}-0001", $number1);

        // Manually create a sequence for next year
        $nextYear = $currentYear + 1;
        $numberNextYear = app(\Omersia\Catalog\Services\SequenceService::class)->next(
            "invoice_number_{$nextYear}",
            "INV-{$nextYear}-",
            0,
            4
        );

        $this->assertEquals("INV-{$nextYear}-0001", $numberNextYear);

        // Current year should continue
        $number2 = Invoice::generateNumber();
        $this->assertEquals("INV-{$currentYear}-0002", $number2);
    }

    public function test_multiple_invoices_have_unique_numbers(): void
    {
        $numbers = [];

        for ($i = 0; $i < 20; $i++) {
            $numbers[] = Invoice::generateNumber();
        }

        // All numbers should be unique
        $this->assertCount(20, array_unique($numbers));

        // Should be sequential
        $currentYear = date('Y');
        for ($i = 0; $i < 20; $i++) {
            $expected = sprintf('INV-%s-%04d', $currentYear, $i + 1);
            $this->assertEquals($expected, $numbers[$i]);
        }
    }
}
