<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\TaxRate;
use Omersia\Catalog\Models\TaxZone;
use Omersia\Catalog\Services\TaxCalculator;
use Omersia\Core\Models\Shop;
use Tests\TestCase;

class TaxCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected TaxCalculator $calculator;

    protected Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TaxCalculator;
        $this->shop = Shop::factory()->create();
    }

    public function it_returns_zero_tax_when_shop_does_not_exist(): void
    {
        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'FR'],
            0,
            999999
        );

        $this->assertEquals(0, $result['tax_total']);
        $this->assertEquals(0, $result['tax_rate']);
        $this->assertNull($result['tax_zone']);
        $this->assertEmpty($result['breakdown']);
    }

    public function it_returns_zero_tax_when_no_tax_zone_matches(): void
    {
        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'US', 'state' => 'CA'],
            0,
            $this->shop->id
        );

        $this->assertEquals(0, $result['tax_total']);
        $this->assertEquals(0, $result['tax_rate']);
        $this->assertNull($result['tax_zone']);
    }

    public function it_returns_zero_tax_when_tax_zone_has_no_active_rates(): void
    {
        $taxZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'France',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['FR'],
        ]);

        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'FR'],
            0,
            $this->shop->id
        );

        $this->assertEquals(0, $result['tax_total']);
        $this->assertEquals(0, $result['tax_rate']);
        $this->assertNotNull($result['tax_zone']);
        $this->assertEquals($taxZone->id, $result['tax_zone']->id);
        $this->assertEmpty($result['breakdown']);
    }

    public function it_calculates_simple_percentage_tax(): void
    {
        $taxZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'France',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['FR'],
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'TVA',
            'rate' => 20.0,
            'type' => 'percentage',
            'is_active' => true,
            'compound' => false,
            'shipping_taxable' => false,
        ]);

        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'FR'],
            0,
            $this->shop->id
        );

        $this->assertEquals(20.0, $result['tax_total']);
        $this->assertEquals(20.0, $result['tax_rate']);
        $this->assertEquals($taxZone->id, $result['tax_zone']->id);
        $this->assertCount(1, $result['breakdown']);
        $this->assertEquals('TVA', $result['breakdown'][0]['name']);
        $this->assertEquals(20.0, $result['breakdown'][0]['rate']);
        $this->assertEquals(20.0, $result['breakdown'][0]['amount']);
    }

    public function it_calculates_tax_with_shipping(): void
    {
        $taxZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'France',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['FR'],
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'TVA',
            'rate' => 20.0,
            'type' => 'percentage',
            'is_active' => true,
            'compound' => false,
            'shipping_taxable' => true,
        ]);

        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'FR'],
            10.0,
            $this->shop->id
        );

        // Tax on 100 + 10 = 110 * 0.20 = 22
        $this->assertEquals(22.0, $result['tax_total']);
        $this->assertEquals(20.0, $result['tax_rate']);
        $this->assertEquals(20.0, $result['breakdown'][0]['product_tax']);
        $this->assertEquals(2.0, $result['breakdown'][0]['shipping_tax']);
    }

    public function it_calculates_tax_without_taxable_shipping(): void
    {
        $taxZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'France',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['FR'],
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'TVA',
            'rate' => 20.0,
            'type' => 'percentage',
            'is_active' => true,
            'compound' => false,
            'shipping_taxable' => false,
        ]);

        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'FR'],
            10.0,
            $this->shop->id
        );

        // Tax only on 100 = 100 * 0.20 = 20
        $this->assertEquals(20.0, $result['tax_total']);
        $this->assertEquals(20.0, $result['breakdown'][0]['product_tax']);
        $this->assertEquals(0.0, $result['breakdown'][0]['shipping_tax']);
    }

    public function it_calculates_compound_taxes(): void
    {
        $taxZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Quebec',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['CA'],
            'states' => ['QC'],
        ]);

        // GST 5% - not compound
        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'GST',
            'rate' => 5.0,
            'type' => 'percentage',
            'is_active' => true,
            'compound' => false,
            'shipping_taxable' => false,
            'priority' => 1,
        ]);

        // QST 9.975% - compound (applies on subtotal + GST)
        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'QST',
            'rate' => 9.975,
            'type' => 'percentage',
            'is_active' => true,
            'compound' => true,
            'shipping_taxable' => false,
            'priority' => 2,
        ]);

        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'CA', 'state' => 'QC'],
            0,
            $this->shop->id
        );

        // GST: 100 * 0.05 = 5.00
        // QST: (100 + 5) * 0.09975 = 10.47
        // Total: 5.00 + 10.47 = 15.47
        $this->assertEquals(15.47, $result['tax_total']);
        $this->assertCount(2, $result['breakdown']);
        $this->assertEquals('GST', $result['breakdown'][0]['name']);
        $this->assertEquals(5.0, $result['breakdown'][0]['amount']);
        $this->assertEquals('QST', $result['breakdown'][1]['name']);
        $this->assertEquals(10.47, $result['breakdown'][1]['amount']);
    }

    public function it_calculates_multiple_non_compound_taxes(): void
    {
        $taxZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Test Zone',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['US'],
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'State Tax',
            'rate' => 6.0,
            'type' => 'percentage',
            'is_active' => true,
            'compound' => false,
            'shipping_taxable' => false,
            'priority' => 1,
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'County Tax',
            'rate' => 2.0,
            'type' => 'percentage',
            'is_active' => true,
            'compound' => false,
            'shipping_taxable' => false,
            'priority' => 2,
        ]);

        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'US'],
            0,
            $this->shop->id
        );

        // State: 100 * 0.06 = 6.00
        // County: 100 * 0.02 = 2.00
        // Total: 8.00
        $this->assertEquals(8.0, $result['tax_total']);
        $this->assertEquals(8.0, $result['tax_rate']);
        $this->assertCount(2, $result['breakdown']);
    }

    public function it_respects_tax_zone_priority(): void
    {
        // Create low priority zone
        $lowPriorityZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'General EU',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['FR', 'DE', 'ES'],
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $lowPriorityZone->id,
            'name' => 'Standard VAT',
            'rate' => 20.0,
            'type' => 'percentage',
            'is_active' => true,
        ]);

        // Create high priority zone
        $highPriorityZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'France Special',
            'is_active' => true,
            'priority' => 10,
            'countries' => ['FR'],
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $highPriorityZone->id,
            'name' => 'France VAT',
            'rate' => 25.0,
            'type' => 'percentage',
            'is_active' => true,
        ]);

        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'FR'],
            0,
            $this->shop->id
        );

        // Should use high priority zone (25% tax)
        $this->assertEquals(25.0, $result['tax_total']);
        $this->assertEquals($highPriorityZone->id, $result['tax_zone']->id);
    }

    public function it_calculates_included_tax(): void
    {
        $taxZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'France',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['FR'],
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'TVA',
            'rate' => 20.0,
            'type' => 'percentage',
            'is_active' => true,
        ]);

        $result = $this->calculator->calculateIncludedTax(
            120.0,
            ['country' => 'FR'],
            $this->shop->id
        );

        // Price including tax: 120
        // Tax rate: 20%
        // Price excluding tax: 120 / 1.20 = 100
        // Tax: 120 - 100 = 20
        $this->assertEquals(20.0, $result['tax_total']);
        $this->assertEquals(20.0, $result['tax_rate']);
        $this->assertEquals(100.0, $result['price_excluding_tax']);
    }

    public function it_returns_original_price_when_no_tax_for_included_tax(): void
    {
        $result = $this->calculator->calculateIncludedTax(
            120.0,
            ['country' => 'XX'],
            $this->shop->id
        );

        $this->assertEquals(0, $result['tax_total']);
        $this->assertEquals(0, $result['tax_rate']);
        $this->assertEquals(120.0, $result['price_excluding_tax']);
    }

    public function it_uses_first_shop_when_shop_id_is_null(): void
    {
        $firstShop = Shop::first();

        $taxZone = TaxZone::factory()->create([
            'shop_id' => $firstShop->id,
            'name' => 'Default Zone',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['FR'],
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'Default Tax',
            'rate' => 10.0,
            'type' => 'percentage',
            'is_active' => true,
        ]);

        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'FR'],
            0,
            null
        );

        $this->assertEquals(10.0, $result['tax_total']);
    }

    public function it_rounds_tax_amounts_correctly(): void
    {
        $taxZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Test',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['FR'],
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'Tax',
            'rate' => 19.6,
            'type' => 'percentage',
            'is_active' => true,
        ]);

        $result = $this->calculator->calculate(
            33.33,
            ['country' => 'FR'],
            0,
            $this->shop->id
        );

        // 33.33 * 0.196 = 6.53268, should round to 6.53
        $this->assertEquals(6.53, $result['tax_total']);
    }

    public function it_skips_inactive_tax_rates(): void
    {
        $taxZone = TaxZone::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Test',
            'is_active' => true,
            'priority' => 1,
            'countries' => ['FR'],
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'Active Tax',
            'rate' => 10.0,
            'type' => 'percentage',
            'is_active' => true,
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $taxZone->id,
            'name' => 'Inactive Tax',
            'rate' => 20.0,
            'type' => 'percentage',
            'is_active' => false,
        ]);

        $result = $this->calculator->calculate(
            100.0,
            ['country' => 'FR'],
            0,
            $this->shop->id
        );

        // Only active tax should apply
        $this->assertEquals(10.0, $result['tax_total']);
        $this->assertCount(1, $result['breakdown']);
    }
}
