<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit\Services;

use App\Events\Realtime\ProductStockUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\OrderItem;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductVariant;
use Omersia\Catalog\Services\OrderInventoryService;
use RuntimeException;
use Tests\TestCase;

class OrderInventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_decrements_simple_product_stock_and_dispatches_realtime_event(): void
    {
        Event::fake([ProductStockUpdated::class]);

        $product = Product::factory()->create([
            'type' => 'simple',
            'manage_stock' => true,
            'stock_qty' => 10,
        ]);

        $order = Order::factory()->create([
            'status' => 'draft',
            'meta' => [],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 3,
        ]);

        app(OrderInventoryService::class)->deductStockForOrder($order);

        $this->assertEquals(7, (int) $product->fresh()->stock_qty);
        $this->assertArrayHasKey('inventory_deducted_at', $order->fresh()->meta ?? []);

        Event::assertDispatched(ProductStockUpdated::class, function (ProductStockUpdated $event) use ($product) {
            return (int) ($event->product['id'] ?? 0) === (int) $product->id
                && (int) ($event->product['stock_qty'] ?? -1) === 7;
        });
    }

    public function test_it_decrements_variant_stock_once_even_if_called_multiple_times(): void
    {
        Event::fake([ProductStockUpdated::class]);

        $product = Product::factory()->create([
            'type' => 'variant',
            'manage_stock' => false,
            'stock_qty' => 0,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'TS-S-BLACK',
            'name' => 'S / Noir',
            'is_active' => true,
            'manage_stock' => true,
            'stock_qty' => 8,
            'price' => 19.99,
            'compare_at_price' => null,
        ]);

        $order = Order::factory()->create([
            'status' => 'draft',
            'meta' => [],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $service = app(OrderInventoryService::class);
        $service->deductStockForOrder($order);
        $service->deductStockForOrder($order);

        $this->assertEquals(6, (int) $variant->fresh()->stock_qty);
        Event::assertDispatchedTimes(ProductStockUpdated::class, 1);
    }

    public function test_it_throws_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'manage_stock' => true,
            'stock_qty' => 1,
        ]);

        $order = Order::factory()->create([
            'status' => 'draft',
            'meta' => [],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 2,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stock insuffisant');

        app(OrderInventoryService::class)->deductStockForOrder($order);
    }
}
