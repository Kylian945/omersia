<?php

declare(strict_types=1);

namespace Omersia\Sales\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Order;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Models\Discount;
use Omersia\Sales\Models\DiscountUsage;
use Tests\TestCase;

class DiscountUsageTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_discount_usage(): void
    {
        $discount = Discount::factory()->create();
        $order = Order::factory()->create();
        $customer = Customer::factory()->create();

        $usage = DiscountUsage::create([
            'discount_id' => $discount->id,
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'usage_count' => 1,
        ]);

        $this->assertDatabaseHas('discount_usages', [
            'discount_id' => $discount->id,
            'order_id' => $order->id,
            'usage_count' => 1,
        ]);
    }

    public function it_belongs_to_discount(): void
    {
        $discount = Discount::factory()->create();
        $usage = DiscountUsage::factory()->create(['discount_id' => $discount->id]);

        $this->assertInstanceOf(Discount::class, $usage->discount);
        $this->assertEquals($discount->id, $usage->discount->id);
    }

    public function it_belongs_to_order(): void
    {
        $order = Order::factory()->create();
        $usage = DiscountUsage::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $usage->order);
        $this->assertEquals($order->id, $usage->order->id);
    }

    public function it_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $usage = DiscountUsage::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $usage->customer);
        $this->assertEquals($customer->id, $usage->customer->id);
    }

    public function it_has_fillable_attributes(): void
    {
        $usage = new DiscountUsage;
        $fillable = $usage->getFillable();

        $this->assertContains('discount_id', $fillable);
        $this->assertContains('order_id', $fillable);
        $this->assertContains('customer_id', $fillable);
        $this->assertContains('usage_count', $fillable);
    }

    public function it_tracks_usage_count(): void
    {
        $usage = DiscountUsage::factory()->create(['usage_count' => 5]);

        $this->assertEquals(5, $usage->usage_count);
    }
}
