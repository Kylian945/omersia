<?php

declare(strict_types=1);

namespace Omersia\Sales\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use Omersia\Core\Models\Shop;
use Omersia\Customer\Models\Customer;
use Omersia\Customer\Models\CustomerGroup;
use Omersia\Sales\Models\Discount;
use Omersia\Sales\Models\DiscountUsage;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_discount(): void
    {
        $shop = Shop::factory()->create();

        $discount = Discount::create([
            'shop_id' => $shop->id,
            'name' => 'Summer Sale',
            'type' => 'order',
            'method' => 'code',
            'code' => 'SUMMER20',
            'value_type' => 'percentage',
            'value' => 20,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('discounts', [
            'name' => 'Summer Sale',
            'code' => 'SUMMER20',
            'value' => 20,
        ]);
    }

    public function it_belongs_to_many_products(): void
    {
        $discount = Discount::factory()->create();
        $product = Product::factory()->create();
        $discount->products()->attach($product);

        $this->assertCount(1, $discount->products);
        $this->assertEquals($product->id, $discount->products->first()->id);
    }

    public function it_belongs_to_many_collections(): void
    {
        $discount = Discount::factory()->create();
        $category = Category::factory()->create();
        $discount->collections()->attach($category);

        $this->assertCount(1, $discount->collections);
    }

    public function it_belongs_to_many_customer_groups(): void
    {
        $discount = Discount::factory()->create();
        $group = CustomerGroup::factory()->create();
        $discount->customerGroups()->attach($group);

        $this->assertCount(1, $discount->customerGroups);
    }

    public function it_belongs_to_many_customers(): void
    {
        $discount = Discount::factory()->create();
        $customer = Customer::factory()->create();
        $discount->customers()->attach($customer);

        $this->assertCount(1, $discount->customers);
    }

    public function it_has_many_usages(): void
    {
        $discount = Discount::factory()->create();
        DiscountUsage::factory()->count(3)->create(['discount_id' => $discount->id]);

        $this->assertCount(3, $discount->usages);
    }

    public function it_can_scope_active_discounts(): void
    {
        Discount::factory()->create([
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
        Discount::factory()->create([
            'is_active' => false,
        ]);

        $active = Discount::active()->get();

        $this->assertCount(1, $active);
    }

    public function it_filters_discounts_by_start_date(): void
    {
        Discount::factory()->create([
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);
        Discount::factory()->create([
            'is_active' => true,
            'starts_at' => now()->subDay(),
        ]);

        $active = Discount::active()->get();

        $this->assertCount(1, $active);
    }

    public function it_filters_discounts_by_end_date(): void
    {
        Discount::factory()->create([
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);
        Discount::factory()->create([
            'is_active' => true,
            'ends_at' => now()->addDay(),
        ]);

        $active = Discount::active()->get();

        $this->assertCount(1, $active);
    }

    public function it_can_scope_for_shop(): void
    {
        $shop = Shop::factory()->create();
        Discount::factory()->count(2)->create(['shop_id' => $shop->id]);
        Discount::factory()->create();

        $shopDiscounts = Discount::forShop($shop->id)->get();

        $this->assertCount(2, $shopDiscounts);
    }

    public function it_casts_boolean_attributes(): void
    {
        $discount = Discount::factory()->create([
            'is_active' => 1,
            'get_is_free' => 1,
            'applies_once_per_order' => 1,
            'combines_with_product_discounts' => 1,
        ]);

        $this->assertIsBool($discount->is_active);
        $this->assertIsBool($discount->get_is_free);
        $this->assertIsBool($discount->applies_once_per_order);
        $this->assertIsBool($discount->combines_with_product_discounts);
    }

    public function it_casts_datetime_attributes(): void
    {
        $discount = Discount::factory()->create([
            'starts_at' => '2025-01-01 00:00:00',
            'ends_at' => '2025-12-31 23:59:59',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $discount->starts_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $discount->ends_at);
    }

    public function it_has_fillable_attributes(): void
    {
        $discount = new Discount;
        $fillable = $discount->getFillable();

        $this->assertContains('shop_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('value', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    public function it_stores_discount_type(): void
    {
        $discount = Discount::factory()->create(['type' => 'product']);

        $this->assertEquals('product', $discount->type);
    }

    public function it_stores_value_type(): void
    {
        $discount = Discount::factory()->create(['value_type' => 'fixed_amount']);

        $this->assertEquals('fixed_amount', $discount->value_type);
    }

    public function it_stores_usage_limits(): void
    {
        $discount = Discount::factory()->create([
            'usage_limit' => 100,
            'usage_limit_per_customer' => 1,
        ]);

        $this->assertEquals(100, $discount->usage_limit);
        $this->assertEquals(1, $discount->usage_limit_per_customer);
    }

    public function it_stores_minimum_requirements(): void
    {
        $discount = Discount::factory()->create([
            'min_subtotal' => 50.00,
            'min_quantity' => 3,
        ]);

        $this->assertEquals(50.00, $discount->min_subtotal);
        $this->assertEquals(3, $discount->min_quantity);
    }

    public function it_stores_buy_x_get_y_configuration(): void
    {
        $discount = Discount::factory()->create([
            'buy_quantity' => 2,
            'get_quantity' => 1,
            'get_is_free' => true,
        ]);

        $this->assertEquals(2, $discount->buy_quantity);
        $this->assertEquals(1, $discount->get_quantity);
        $this->assertTrue($discount->get_is_free);
    }

    public function it_stores_combination_flags(): void
    {
        $discount = Discount::factory()->create([
            'combines_with_product_discounts' => true,
            'combines_with_order_discounts' => false,
            'combines_with_shipping_discounts' => true,
        ]);

        $this->assertTrue($discount->combines_with_product_discounts);
        $this->assertFalse($discount->combines_with_order_discounts);
        $this->assertTrue($discount->combines_with_shipping_discounts);
    }
}
