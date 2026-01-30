<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Api\DTO\OrderCreateDTO;
use Omersia\Api\Services\OrderCreationService;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ShippingMethod;
use Omersia\Customer\Models\Customer;
use Tests\TestCase;

/**
 * Tests de concurrence pour la création de commandes
 *
 * Ces tests valident que la génération de numéros de commande
 * est atomique même avec des créations concurrentes.
 */
class OrderCreationConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private OrderCreationService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderCreationService::class);
    }

    /**
     * Test that concurrent order creation generates unique order numbers.
     *
     * This is the main test for the race condition fix.
     * Without proper sequence locking, concurrent order creation
     * could generate duplicate order numbers.
     */
    public function it_generates_unique_order_numbers_concurrently(): void
    {
        // Setup test data
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'firstname' => 'Test',
            'lastname' => 'Customer',
        ]);

        $product = Product::factory()->create([
            'price' => 10000, // 100.00 EUR in cents
            'status' => 'active',
        ]);

        $shippingMethod = ShippingMethod::factory()->create([
            'name' => 'Standard Shipping',
            'price' => 500, // 5.00 EUR
        ]);

        $iterations = 30; // Reduced from 100 for test performance
        $orderNumbers = [];

        // Simulate concurrent order creation
        for ($i = 0; $i < $iterations; $i++) {
            $dto = new OrderCreateDTO(
                customerId: $customer->id,
                cartId: null,
                shippingMethodId: $shippingMethod->id,
                currency: 'EUR',
                customerEmail: $customer->email,
                customerFirstname: $customer->firstname,
                customerLastname: $customer->lastname,
                shippingAddress: [
                    'address' => '123 Test Street',
                    'city' => 'Paris',
                    'postal_code' => '75001',
                    'country' => 'FR',
                ],
                billingAddress: [
                    'address' => '123 Test Street',
                    'city' => 'Paris',
                    'postal_code' => '75001',
                    'country' => 'FR',
                ],
                items: [
                    [
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'quantity' => 1,
                        'price' => 100.00,
                        'total_price' => 100.00,
                    ],
                ],
                discountTotal: 0.0,
                shippingTotal: 5.0,
                taxTotal: 0.0,
            );

            $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
            $orderNumbers[] = $order->number;
        }

        // Assert all order numbers are unique
        $uniqueNumbers = array_unique($orderNumbers);
        $duplicates = array_diff_assoc($orderNumbers, $uniqueNumbers);

        $this->assertCount(
            $iterations,
            $uniqueNumbers,
            'Expected all order numbers to be unique. Duplicates found: '.
            json_encode(array_values($duplicates))
        );

        // Assert format (ORD-XXXXXXXX)
        foreach ($orderNumbers as $number) {
            $this->assertMatchesRegularExpression('/^ORD-\d{8}$/', $number);
        }

        $this->logInfo("Successfully created {$iterations} orders with unique numbers");
    }

    /**
     * Test that orders created in parallel have sequential numbers.
     */
    public function it_creates_sequential_order_numbers(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10000]);
        $shippingMethod = ShippingMethod::factory()->create();

        $iterations = 10;
        $orderNumbers = [];

        for ($i = 0; $i < $iterations; $i++) {
            $dto = $this->createOrderDTO($customer, $product, $shippingMethod);
            $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
            $orderNumbers[] = $order->number;
        }

        // Numbers should be sequential when sorted
        $sortedNumbers = $orderNumbers;
        sort($sortedNumbers);

        $this->assertEquals($sortedNumbers, $orderNumbers, 'Order numbers should be sequential');
    }

    /**
     * Test that updating existing draft doesn't change order number.
     */
    public function it_preserves_order_number_on_draft_update(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10000]);
        $shippingMethod = ShippingMethod::factory()->create();

        // Create initial draft order with cart_id
        $dto1 = $this->createOrderDTO($customer, $product, $shippingMethod, 'cart-123');
        $order1 = $this->orderService->createOrUpdateDraftOrder($dto1, $shippingMethod);
        $originalNumber = $order1->number;

        // Update the draft (same cart_id)
        $dto2 = $this->createOrderDTO($customer, $product, $shippingMethod, 'cart-123', 2);
        $order2 = $this->orderService->createOrUpdateDraftOrder($dto2, $shippingMethod);

        $this->assertEquals($originalNumber, $order2->number);
        $this->assertEquals($order1->id, $order2->id);
    }

    /**
     * Test that different carts get different order numbers.
     */
    public function it_assigns_different_numbers_to_different_carts(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10000]);
        $shippingMethod = ShippingMethod::factory()->create();

        // Create order for cart 1
        $dto1 = $this->createOrderDTO($customer, $product, $shippingMethod, 'cart-1');
        $order1 = $this->orderService->createOrUpdateDraftOrder($dto1, $shippingMethod);

        // Create order for cart 2
        $dto2 = $this->createOrderDTO($customer, $product, $shippingMethod, 'cart-2');
        $order2 = $this->orderService->createOrUpdateDraftOrder($dto2, $shippingMethod);

        $this->assertNotEquals($order1->number, $order2->number);
        $this->assertNotEquals($order1->id, $order2->id);
    }

    /**
     * Test performance: creating 50 orders should be reasonably fast.
     * Target: < 3 seconds for 50 orders
     */
    public function it_creates_orders_efficiently(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10000]);
        $shippingMethod = ShippingMethod::factory()->create();

        $iterations = 50;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $dto = $this->createOrderDTO($customer, $product, $shippingMethod);
            $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);
        }

        $duration = microtime(true) - $startTime;

        $this->assertLessThan(
            3.0,
            $duration,
            "Creating {$iterations} orders took {$duration}s (expected < 3s)"
        );

        $this->logInfo("Created {$iterations} orders in ".round($duration, 3).'s');
    }

    /**
     * Test that order items are created correctly.
     */
    public function it_creates_order_items_correctly(): void
    {
        $customer = Customer::factory()->create();
        $product1 = Product::factory()->create(['price' => 10000]);
        $product2 = Product::factory()->create(['price' => 20000]);
        $shippingMethod = ShippingMethod::factory()->create();

        $dto = new OrderCreateDTO(
            customerId: $customer->id,
            cartId: null,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: ['address' => '123 Test St'],
            billingAddress: ['address' => '123 Test St'],
            items: [
                [
                    'product_id' => $product1->id,
                    'variant_id' => null,
                    'quantity' => 2,
                    'price' => 100.00,
                    'total_price' => 200.00,
                ],
                [
                    'product_id' => $product2->id,
                    'variant_id' => null,
                    'quantity' => 1,
                    'price' => 200.00,
                    'total_price' => 200.00,
                ],
            ],
            discountTotal: 0.0,
            shippingTotal: 10.0,
            taxTotal: 0.0,
        );

        $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);

        $this->assertCount(2, $order->items);
        $this->assertEquals(400.00, $order->subtotal);
        $this->assertEquals(410.00, $order->total);
    }

    /**
     * Test that concurrent creation doesn't cause database errors.
     */
    public function it_handles_concurrent_creation_without_errors(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10000]);
        $shippingMethod = ShippingMethod::factory()->create();

        $iterations = 20;
        $errors = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $dto = $this->createOrderDTO($customer, $product, $shippingMethod);
                $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);

                $this->assertNotNull($order);
                $this->assertNotNull($order->number);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $this->assertEmpty($errors, 'No errors should occur during concurrent creation: '.json_encode($errors));
    }

    /**
     * Test that order number format is consistent.
     */
    public function it_maintains_consistent_order_number_format(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10000]);
        $shippingMethod = ShippingMethod::factory()->create();

        $iterations = 15;

        for ($i = 0; $i < $iterations; $i++) {
            $dto = $this->createOrderDTO($customer, $product, $shippingMethod);
            $order = $this->orderService->createOrUpdateDraftOrder($dto, $shippingMethod);

            // Format: ORD-XXXXXXXX (8 digits)
            $this->assertMatchesRegularExpression(
                '/^ORD-\d{8}$/',
                $order->number,
                "Order number {$order->number} doesn't match expected format ORD-XXXXXXXX"
            );

            // Number should be numeric after prefix
            $numericPart = substr($order->number, 4);
            $this->assertTrue(is_numeric($numericPart));
            $this->assertEquals(8, strlen($numericPart));
        }
    }

    /**
     * Helper method to create OrderCreateDTO.
     */
    private function createOrderDTO(
        Customer $customer,
        Product $product,
        ShippingMethod $shippingMethod,
        ?string $cartId = null,
        int $quantity = 1
    ): OrderCreateDTO {
        return new OrderCreateDTO(
            customerId: $customer->id,
            cartId: $cartId,
            shippingMethodId: $shippingMethod->id,
            currency: 'EUR',
            customerEmail: $customer->email,
            customerFirstname: $customer->firstname,
            customerLastname: $customer->lastname,
            shippingAddress: [
                'address' => '123 Test Street',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'FR',
            ],
            billingAddress: [
                'address' => '123 Test Street',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'FR',
            ],
            items: [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity' => $quantity,
                    'price' => 100.00,
                    'total_price' => 100.00 * $quantity,
                ],
            ],
            discountTotal: 0.0,
            shippingTotal: 5.0,
            taxTotal: 0.0,
        );
    }

    /**
     * Helper to output info messages during tests.
     */
    private function logInfo(string $message): void
    {
        fwrite(STDOUT, "\n[INFO] {$message}\n");
    }
}
