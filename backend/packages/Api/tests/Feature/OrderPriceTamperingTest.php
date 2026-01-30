<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Api\DTO\OrderCreateDTO;
use Omersia\Api\Exceptions\PriceTamperingException;
use Omersia\Api\Services\OrderCreationService;
use Omersia\Api\Services\OrderItemService;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductVariant;
use Omersia\Catalog\Models\ShippingMethod;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Models\Discount;
use Omersia\Sales\Models\DiscountUsage;
use Tests\TestCase;

/**
 * Tests for DCA-012: Server-side validation of order prices and discounts
 *
 * These tests ensure that:
 * 1. Product prices cannot be tampered with by clients
 * 2. Variant prices are validated against database
 * 3. Discount codes are validated (exists, active, not expired)
 * 4. Discount amounts are recalculated server-side
 * 5. Usage limits (global and per-customer) are enforced
 * 6. Automatic discounts are applied correctly
 */
class OrderPriceTamperingTest extends TestCase
{
    use RefreshDatabase;

    private OrderCreationService $orderService;

    private OrderItemService $orderItemService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderCreationService::class);
        $this->orderItemService = app(OrderItemService::class);
    }

    // =====================================
    // 1. PRICE TAMPERING TESTS
    // =====================================

    /**
     * Test that order with correct prices succeeds.
     */
    public function it_accepts_order_with_correct_prices(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 99.99,
            'is_active' => true,
        ]);
        $shippingMethod = ShippingMethod::factory()->create();

        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => 99.99,
                    'total_price' => 99.99,
                ],
            ],
            discountTotal: 0.0,
            shippingTotal: 10.0,
            taxTotal: 0.0,
        );

        $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);

        $this->assertNotNull($order);
        $this->assertEquals('draft', $order->status);
        $this->assertEquals(99.99, $order->subtotal);
        $this->assertEquals(109.99, $order->total); // subtotal + shipping
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'subtotal' => 99.99,
        ]);
    }

    /**
     * Test that order with reduced item price fails validation.
     *
     * This simulates a malicious client sending a lower price than
     * what's stored in the database.
     */
    public function it_rejects_order_with_reduced_item_price(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 99.99,
            'is_active' => true,
        ]);
        $shippingMethod = ShippingMethod::factory()->create();

        // In a real implementation, this should throw a validation exception
        // For now, we test that the service recalculates prices server-side
        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => 0.01, // TAMPERED: should be 99.99
                    'total_price' => 0.01, // TAMPERED
                ],
            ],
            discountTotal: 0.0,
            shippingTotal: 10.0,
            taxTotal: 0.0,
        );

        $this->expectException(PriceTamperingException::class);

        $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
    }

    /**
     * Test that order with variant price is validated against database.
     */
    public function it_validates_variant_price_against_database(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'type' => 'variant',
            'price' => 100.00,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-001',
            'name' => 'Large',
            'price' => 149.99,
            'is_active' => true,
            'manage_stock' => false,
        ]);

        $shippingMethod = ShippingMethod::factory()->create();

        // Test 1: Correct variant price - should succeed
        $dtoCorrect = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'name' => 'Test Product - Large',
                    'sku' => $variant->sku,
                    'quantity' => 1,
                    'unit_price' => 149.99, // Correct variant price
                    'total_price' => 149.99,
                ],
            ],
            discountTotal: 0.0,
            shippingTotal: 10.0,
            taxTotal: 0.0,
        );

        $order = $this->orderService->createOrUpdateDraftOrder($dtoCorrect, $shippingMethod);
        $this->assertEquals(149.99, $order->subtotal);

        // Test 2: Tampered variant price - should fail
        $dtoTampered = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'name' => 'Test Product - Large',
                    'sku' => $variant->sku,
                    'quantity' => 1,
                    'unit_price' => 0.01, // TAMPERED
                    'total_price' => 0.01,
                ],
            ],
            discountTotal: 0.0,
            shippingTotal: 10.0,
            taxTotal: 0.0,
        );

        $this->expectException(PriceTamperingException::class);
        $this->orderService->createOrUpdateDraftOrder($dtoTampered, $shippingMethod);
    }

    /**
     * Test that order with multiple items validates all prices.
     */
    public function it_validates_all_item_prices_in_multi_item_order(): void
    {
        $customer = Customer::factory()->create();
        $product1 = Product::factory()->create(['price' => 50.00, 'is_active' => true]);
        $product2 = Product::factory()->create(['price' => 75.00, 'is_active' => true]);
        $product3 = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        // Tamper with 2 out of 3 prices
        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product1->id,
                    'variant_id' => null,
                    'name' => 'Product 1',
                    'sku' => $product1->sku,
                    'quantity' => 1,
                    'unit_price' => 50.00, // Correct
                    'total_price' => 50.00,
                ],
                [
                    'product_id' => $product2->id,
                    'variant_id' => null,
                    'name' => 'Product 2',
                    'sku' => $product2->sku,
                    'quantity' => 1,
                    'unit_price' => 1.00, // TAMPERED: should be 75.00
                    'total_price' => 1.00,
                ],
                [
                    'product_id' => $product3->id,
                    'variant_id' => null,
                    'name' => 'Product 3',
                    'sku' => $product3->sku,
                    'quantity' => 1,
                    'unit_price' => 5.00, // TAMPERED: should be 100.00
                    'total_price' => 5.00,
                ],
            ],
            discountTotal: 0.0,
            shippingTotal: 10.0,
            taxTotal: 0.0,
        );

        $this->expectException(PriceTamperingException::class);
        $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
    }

    // =====================================
    // 2. DISCOUNT TAMPERING TESTS
    // =====================================

    /**
     * Test that order with valid manual discount succeeds.
     */
    public function it_accepts_order_with_valid_manual_discount(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        $discount = Discount::factory()->create([
            'name' => 'Save 10%',
            'method' => 'code',
            'code' => 'SAVE10',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addMonth(),
        ]);

        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'total_price' => 100.00,
                ],
            ],
            discountTotal: 10.0, // 10% of 100.00
            shippingTotal: 10.0,
            taxTotal: 0.0,
            appliedDiscountCodes: ['SAVE10'],
        );

        $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);

        $this->assertEquals(100.00, $order->subtotal);
        $this->assertEquals(10.0, $order->discount_total);
        $this->assertEquals(100.0, $order->total); // 100 - 10 + 10 shipping
    }

    /**
     * Test that order with inflated discount total fails validation.
     */
    public function it_rejects_order_with_inflated_discount_total(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        Discount::factory()->create([
            'method' => 'code',
            'code' => 'SAVE10',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10, // 10% = $10 discount
            'is_active' => true,
        ]);

        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'total_price' => 100.00,
                ],
            ],
            discountTotal: 50.0, // TAMPERED: should be 10.0
            shippingTotal: 10.0,
            taxTotal: 0.0,
            appliedDiscountCodes: ['SAVE10'],
        );

        $this->expectException(PriceTamperingException::class);
        $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
    }

    /**
     * Test that order with invalid discount code fails validation.
     */
    public function it_rejects_order_with_invalid_discount_code(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'total_price' => 100.00,
                ],
            ],
            discountTotal: 10.0,
            shippingTotal: 10.0,
            taxTotal: 0.0,
            appliedDiscountCodes: ['FAKECODE'],
        );

        $this->expectException(PriceTamperingException::class);
        $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
    }

    /**
     * Test that order with expired discount code fails validation.
     */
    public function it_rejects_order_with_expired_discount_code(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        Discount::factory()->create([
            'method' => 'code',
            'code' => 'EXPIRED',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'starts_at' => Carbon::now()->subMonth(),
            'ends_at' => Carbon::now()->subDay(), // Expired yesterday
        ]);

        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'total_price' => 100.00,
                ],
            ],
            discountTotal: 10.0,
            shippingTotal: 10.0,
            taxTotal: 0.0,
            appliedDiscountCodes: ['EXPIRED'],
        );

        $this->expectException(PriceTamperingException::class);
        $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
    }

    /**
     * Test that order with inactive discount code fails validation.
     */
    public function it_rejects_order_with_inactive_discount_code(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        Discount::factory()->create([
            'method' => 'code',
            'code' => 'INACTIVE',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => false, // Inactive
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addMonth(),
        ]);

        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'total_price' => 100.00,
                ],
            ],
            discountTotal: 10.0,
            shippingTotal: 10.0,
            taxTotal: 0.0,
            appliedDiscountCodes: ['INACTIVE'],
        );

        $this->expectException(PriceTamperingException::class);
        $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
    }

    // =====================================
    // 3. USAGE LIMIT TESTS
    // =====================================

    /**
     * Test that discount within usage limit succeeds.
     */
    public function it_accepts_discount_within_global_usage_limit(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        $discount = Discount::factory()->create([
            'method' => 'code',
            'code' => 'LIMITED5',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'usage_limit' => 5, // Limit: 5 uses
        ]);

        // Create 4 existing usages
        DiscountUsage::factory()->count(4)->create([
            'discount_id' => $discount->id,
        ]);

        // 5th usage should be allowed
        $dto = $this->createOrderDTO($customer, $product, $shippingMethod, 10.0, ['LIMITED5']);

        $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
        $this->assertEquals(10.0, $order->discount_total);
    }

    /**
     * Test that discount exceeding global usage limit fails.
     */
    public function it_rejects_discount_exceeding_global_usage_limit(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        $discount = Discount::factory()->create([
            'method' => 'code',
            'code' => 'LIMITED5',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'usage_limit' => 5,
        ]);

        // Create 5 existing usages (limit reached)
        DiscountUsage::factory()->count(5)->create([
            'discount_id' => $discount->id,
        ]);

        $dto = $this->createOrderDTO($customer, $product, $shippingMethod, 10.0, ['LIMITED5']);

        $this->expectException(PriceTamperingException::class);
        $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
    }

    /**
     * Test that discount exceeding per-customer limit fails.
     */
    public function it_rejects_discount_exceeding_per_customer_limit(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        $discount = Discount::factory()->create([
            'method' => 'code',
            'code' => 'PERCUST2',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'usage_limit_per_customer' => 2, // Max 2 per customer
        ]);

        // Create 2 existing usages for this customer
        DiscountUsage::factory()->count(2)->create([
            'discount_id' => $discount->id,
            'customer_id' => $customer->id,
        ]);

        // 3rd attempt by same customer should fail
        $dto = $this->createOrderDTO($customer, $product, $shippingMethod, 10.0, ['PERCUST2']);

        $this->expectException(PriceTamperingException::class);
        $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
    }

    /**
     * Test that per-customer limit allows different customers.
     */
    public function it_allows_discount_for_different_customer_when_per_customer_limit_reached(): void
    {
        $customerA = Customer::factory()->create(['email' => 'a@test.com']);
        $customerB = Customer::factory()->create(['email' => 'b@test.com']);
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        $discount = Discount::factory()->create([
            'method' => 'code',
            'code' => 'PERCUST2',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'usage_limit_per_customer' => 2,
        ]);

        // Customer A uses discount 2 times (limit reached for A)
        DiscountUsage::factory()->count(2)->create([
            'discount_id' => $discount->id,
            'customer_id' => $customerA->id,
        ]);

        // Customer B should still be able to use it
        $dto = $this->createOrderDTO($customerB, $product, $shippingMethod, 10.0, ['PERCUST2']);

        $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
        $this->assertEquals(10.0, $order->discount_total);
    }

    // =====================================
    // 4. AUTOMATIC DISCOUNT TESTS
    // =====================================

    /**
     * Test that automatic discounts are applied server-side.
     */
    public function it_applies_automatic_discounts_server_side(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        Discount::factory()->create([
            'method' => 'automatic',
            'code' => null,
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'product_scope' => 'all',
        ]);

        // Client sends order WITHOUT specifying any discount
        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'total_price' => 100.00,
                ],
            ],
            discountTotal: 10.0, // Server will validate this matches automatic discount
            shippingTotal: 10.0,
            taxTotal: 0.0,
        );

        // Automatic discount should be detected and validated
        $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
        $this->assertEquals(10.0, $order->discount_total);
    }

    /**
     * Test that automatic discount respects usage limit.
     */
    public function it_respects_usage_limit_for_automatic_discounts(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        $discount = Discount::factory()->create([
            'method' => 'automatic',
            'code' => null,
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'usage_limit' => 1, // Only 1 use allowed
        ]);

        // Create 1 existing usage
        DiscountUsage::factory()->create([
            'discount_id' => $discount->id,
        ]);

        // Automatic discount NOT applied, so discount_total should be 0
        $dto = $this->createOrderDTO($customer, $product, $shippingMethod, 0.0);

        $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
        $this->assertEquals(0.0, $order->discount_total);
    }

    // =====================================
    // 5. EDGE CASES
    // =====================================

    /**
     * Test that order with zero discount when none applied succeeds.
     */
    public function it_accepts_order_with_zero_discount_when_no_discounts_exist(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        // No discounts in database
        $this->assertEquals(0, Discount::count());

        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'total_price' => 100.00,
                ],
            ],
            discountTotal: 0.0,
            shippingTotal: 10.0,
            taxTotal: 0.0,
        );

        $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);

        $this->assertEquals(0.0, $order->discount_total);
        $this->assertEquals(110.0, $order->total);
    }

    /**
     * Test that combination of price and discount tampering is detected.
     */
    public function it_detects_combination_of_price_and_discount_tampering(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        Discount::factory()->create([
            'method' => 'code',
            'code' => 'SAVE10',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);

        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => 10.00, // TAMPERED: should be 100.00
                    'total_price' => 10.00,
                ],
            ],
            discountTotal: 50.0, // TAMPERED: should be 10.00
            shippingTotal: 10.0,
            taxTotal: 0.0,
        );

        $this->expectException(PriceTamperingException::class);
        $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
    }

    /**
     * Test that fixed amount discount is capped at subtotal.
     */
    public function it_caps_fixed_amount_discount_at_subtotal(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        Discount::factory()->create([
            'method' => 'code',
            'code' => 'SAVE100',
            'type' => 'order',
            'value_type' => 'fixed_amount',
            'value' => 100.0, // Discount bigger than subtotal
            'is_active' => true,
        ]);

        // Discount is capped at subtotal (50.0), not full 100.0
        $dto = $this->createOrderDTO($customer, $product, $shippingMethod, 50.0, ['SAVE100']);

        $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
        $this->assertEquals(50.0, $order->discount_total);
    }

    /**
     * Test that discount code is case-insensitive.
     */
    public function it_validates_discount_codes_case_insensitively(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        $shippingMethod = ShippingMethod::factory()->create();

        Discount::factory()->create([
            'method' => 'code',
            'code' => 'SAVE10',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);

        // Client sends lowercase version - should match 'SAVE10' in database
        $dto = $this->createOrderDTO($customer, $product, $shippingMethod, 10.0, ['save10']);

        $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
        $this->assertEquals(10.0, $order->discount_total);
    }

    // =====================================
    // HELPER METHODS
    // =====================================

    /**
     * Create OrderCreateDTO for testing.
     */
    private function createOrderDTO(
        Customer $customer,
        Product $product,
        ShippingMethod $shippingMethod,
        float $discountTotal = 0.0,
        array $appliedDiscountCodes = []
    ): OrderCreateDTO {
        return new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: $this->getTestAddress(),
            billingAddress: $this->getTestAddress(),
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => 'Test Product',
                    'sku' => $product->sku,
                    'quantity' => 1,
                    'unit_price' => $product->price,
                    'total_price' => $product->price,
                ],
            ],
            discountTotal: $discountTotal,
            shippingTotal: 10.0,
            taxTotal: 0.0,
            appliedDiscountCodes: $appliedDiscountCodes,
        );
    }

    /**
     * Get test address data.
     */
    private function getTestAddress(): array
    {
        return [
            'address' => '123 Test Street',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'FR',
        ];
    }
}
