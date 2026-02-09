<?php

declare(strict_types=1);

namespace Omersia\Admin\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Admin\DTO\Cart;
use Omersia\Admin\DTO\CartLine;
use Omersia\Admin\Services\Discounts\DiscountEngine;
use Omersia\Core\Models\Shop;
use Omersia\Customer\Models\Customer;
use Omersia\Customer\Models\CustomerGroup;
use Omersia\Sales\Models\Discount;
use Tests\TestCase;

class DiscountEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Shop $shop;

    protected DiscountEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Shop::factory()->create();
        $this->engine = new DiscountEngine($this->shop->id);
    }

    public function it_returns_empty_result_for_empty_cart(): void
    {
        $cart = new Cart(
            lines: [],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        $this->assertEquals(0, $result->subtotal);
        $this->assertEquals(0, $result->total);
        $this->assertEmpty($result->lines);
    }

    public function it_calculates_subtotal_correctly(): void
    {
        $cart = new Cart(
            lines: [
                new CartLine(1, null, 2, 10.0, null, 'Product 1'),
                new CartLine(2, null, 3, 15.0, null, 'Product 2'),
            ],
            shipping_amount: 5.0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        // (10 * 2) + (15 * 3) = 20 + 45 = 65
        $this->assertEquals(65.0, $result->subtotal);
        $this->assertEquals(70.0, $result->total); // 65 + 5 shipping
    }

    public function it_applies_percentage_product_discount(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => '10% off',
            'type' => 'product',
            'method' => 'automatic',
            'value_type' => 'percentage',
            'value' => 10.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        $this->assertEquals(100.0, $result->subtotal);
        $this->assertEquals(10.0, $result->product_discount_total);
        $this->assertEquals(90.0, $result->total);
        $this->assertCount(1, $result->applied_discounts);
        $this->assertEquals('10% off', $result->applied_discounts[0]->name);
    }

    public function it_applies_fixed_amount_product_discount(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => '$5 off',
            'type' => 'product',
            'method' => 'automatic',
            'value_type' => 'fixed_amount',
            'value' => 5.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 20.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        $this->assertEquals(20.0, $result->subtotal);
        $this->assertEquals(5.0, $result->product_discount_total);
        $this->assertEquals(15.0, $result->total);
    }

    public function it_does_not_apply_more_discount_than_line_subtotal(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => '$50 off',
            'type' => 'product',
            'method' => 'automatic',
            'value_type' => 'fixed_amount',
            'value' => 50.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 20.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        // Discount should be capped at line subtotal
        $this->assertEquals(20.0, $result->product_discount_total);
        $this->assertEquals(0.0, $result->total);
    }

    public function it_applies_best_product_discount_when_multiple_are_available(): void
    {
        Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => '10% off',
            'type' => 'product',
            'method' => 'automatic',
            'value_type' => 'percentage',
            'value' => 10.0,
            'priority' => 1,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => '$15 off',
            'type' => 'product',
            'method' => 'automatic',
            'value_type' => 'fixed_amount',
            'value' => 15.0,
            'priority' => 2,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        // $15 is better than 10% of $100 (which is $10)
        $this->assertEquals(15.0, $result->product_discount_total);
        $this->assertEquals('$15 off', $result->applied_discounts[0]->name);
    }

    public function it_applies_percentage_order_discount(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => '20% off order',
            'type' => 'order',
            'method' => 'automatic',
            'value_type' => 'percentage',
            'value' => 20.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        $this->assertEquals(100.0, $result->subtotal);
        $this->assertEquals(20.0, $result->order_discount_total);
        $this->assertEquals(80.0, $result->total);
    }

    public function it_applies_fixed_amount_order_discount(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => '$10 off order',
            'type' => 'order',
            'method' => 'automatic',
            'value_type' => 'fixed_amount',
            'value' => 10.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 50.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        $this->assertEquals(50.0, $result->subtotal);
        $this->assertEquals(10.0, $result->order_discount_total);
        $this->assertEquals(40.0, $result->total);
    }

    public function it_applies_free_shipping_discount(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Free Shipping',
            'type' => 'shipping',
            'method' => 'automatic',
            'value_type' => 'free_shipping',
            'value' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 50.0, null, 'Product 1'),
            ],
            shipping_amount: 10.0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        $this->assertEquals(10.0, $result->shipping_discount_total);
        $this->assertEquals(50.0, $result->total); // 50 + 10 - 10
    }

    public function it_applies_percentage_shipping_discount(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => '50% off shipping',
            'type' => 'shipping',
            'method' => 'automatic',
            'value_type' => 'percentage',
            'value' => 50.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 50.0, null, 'Product 1'),
            ],
            shipping_amount: 20.0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        $this->assertEquals(10.0, $result->shipping_discount_total);
        $this->assertEquals(60.0, $result->total); // 50 + 20 - 10
    }

    public function it_applies_buy_x_get_y_discount(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Buy 2 Get 1 Free',
            'type' => 'buy_x_get_y',
            'method' => 'automatic',
            'buy_quantity' => 2,
            'get_quantity' => 1,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 3, 10.0, null, 'Product 1'), // Buy 2, get 1 free
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        // Subtotal: 3 * 10 = 30
        // Discount: 1 * 10 = 10 (cheapest item free)
        $this->assertEquals(30.0, $result->subtotal);
        $this->assertEquals(10.0, $result->product_discount_total);
        $this->assertEquals(20.0, $result->total);
    }

    public function it_applies_buy_x_get_y_to_cheapest_items(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Buy 2 Get 1 Free',
            'type' => 'buy_x_get_y',
            'method' => 'automatic',
            'buy_quantity' => 2,
            'get_quantity' => 1,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 50.0, null, 'Expensive'),
                new CartLine(2, null, 1, 30.0, null, 'Medium'),
                new CartLine(3, null, 1, 10.0, null, 'Cheap'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        // Should make the cheapest item (10.0) free
        $this->assertEquals(90.0, $result->subtotal);
        $this->assertEquals(10.0, $result->product_discount_total);
        $this->assertEquals(80.0, $result->total);
    }

    public function it_respects_usage_limit(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Limited Offer',
            'type' => 'order',
            'method' => 'automatic',
            'value_type' => 'percentage',
            'value' => 10.0,
            'usage_limit' => 2,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        // Create 2 usages
        $discount->usages()->create(['customer_id' => null, 'usage_count' => 2]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        // Discount should not be applied
        $this->assertEquals(0.0, $result->order_discount_total);
        $this->assertEquals(100.0, $result->total);
    }

    public function it_respects_usage_limit_per_customer(): void
    {
        $customer = Customer::factory()->create();

        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'One Time Offer',
            'type' => 'order',
            'method' => 'automatic',
            'value_type' => 'percentage',
            'value' => 10.0,
            'usage_limit_per_customer' => 1,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        // Customer already used this discount
        $discount->usages()->create(['customer_id' => $customer->id, 'usage_count' => 1]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: $customer->id,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        // Discount should not be applied
        $this->assertEquals(0.0, $result->order_discount_total);
    }

    public function it_only_applies_discount_with_matching_code(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Code Discount',
            'type' => 'order',
            'method' => 'code',
            'code' => 'SAVE10',
            'value_type' => 'percentage',
            'value' => 10.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        // Without code
        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);
        $this->assertEquals(0.0, $result->order_discount_total);

        // With correct code
        $cartWithCode = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: 'SAVE10',
        );

        $result = $this->engine->calculate($cartWithCode);
        $this->assertEquals(10.0, $result->order_discount_total);
    }

    public function it_respects_customer_selection_all(): void
    {
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Everyone',
            'type' => 'order',
            'method' => 'automatic',
            'value_type' => 'percentage',
            'value' => 10.0,
            'customer_selection' => 'all',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);
        $this->assertEquals(10.0, $result->order_discount_total);
    }

    public function it_respects_customer_selection_specific_customers(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'VIP Only',
            'type' => 'order',
            'method' => 'automatic',
            'value_type' => 'percentage',
            'value' => 10.0,
            'customer_selection' => 'customers',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $discount->customers()->attach($customer->id);

        // VIP customer
        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: $customer->id,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);
        $this->assertEquals(10.0, $result->order_discount_total);

        // Non-VIP customer
        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: $otherCustomer->id,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);
        $this->assertEquals(0.0, $result->order_discount_total);
    }

    public function it_respects_customer_selection_groups(): void
    {
        $group = CustomerGroup::factory()->create();
        $customer = Customer::factory()->create();

        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Group Discount',
            'type' => 'order',
            'method' => 'automatic',
            'value_type' => 'percentage',
            'value' => 10.0,
            'customer_selection' => 'groups',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $discount->customerGroups()->attach($group->id);

        // Customer in group
        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: $customer->id,
            customer_group_ids: [$group->id],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);
        $this->assertEquals(10.0, $result->order_discount_total);

        // Customer not in group
        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: $customer->id,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);
        $this->assertEquals(0.0, $result->order_discount_total);
    }

    public function it_ensures_total_never_goes_negative(): void
    {
        Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => 'Huge Discount',
            'type' => 'order',
            'method' => 'automatic',
            'value_type' => 'fixed_amount',
            'value' => 1000.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 10.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        $this->assertGreaterThanOrEqual(0, $result->total);
    }

    public function it_combines_product_and_order_discounts(): void
    {
        Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => '10% off products',
            'type' => 'product',
            'method' => 'automatic',
            'value_type' => 'percentage',
            'value' => 10.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'name' => '$5 off order',
            'type' => 'order',
            'method' => 'automatic',
            'value_type' => 'fixed_amount',
            'value' => 5.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $cart = new Cart(
            lines: [
                new CartLine(1, null, 1, 100.0, null, 'Product 1'),
            ],
            shipping_amount: 0,
            customer_id: null,
            customer_group_ids: [],
            discount_code: null,
        );

        $result = $this->engine->calculate($cart);

        // Product discount: 100 * 0.10 = 10
        // Subtotal after product discount: 100 - 10 = 90
        // Order discount: 5
        // Total: 90 - 5 = 85
        $this->assertEquals(100.0, $result->subtotal);
        $this->assertEquals(10.0, $result->product_discount_total);
        $this->assertEquals(5.0, $result->order_discount_total);
        $this->assertEquals(15.0, $result->total_discounts);
        $this->assertEquals(85.0, $result->total);
    }
}
