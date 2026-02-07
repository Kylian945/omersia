<?php

declare(strict_types=1);

namespace App\Events\Realtime;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Omersia\Catalog\Models\Order;

class OrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array{
     *     id:int,
     *     number:string|null,
     *     customer_id:int|null,
     *     status:string|null,
     *     payment_status:string|null,
     *     fulfillment_status:string|null,
     *     subtotal:float,
     *     discount_total:float,
     *     shipping_total:float,
     *     tax_total:float,
     *     total:float,
     *     currency:string|null,
     *     items_count:int,
     *     placed_at:string|null,
     *     meta:array<string,mixed>|null
     * }  $order
     */
    public function __construct(
        public readonly array $order
    ) {}

    public static function fromModel(Order $order): self
    {
        return new self([
            'id' => (int) $order->id,
            'number' => $order->number,
            'customer_id' => $order->customer_id ? (int) $order->customer_id : null,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'fulfillment_status' => $order->fulfillment_status,
            'subtotal' => (float) $order->subtotal,
            'discount_total' => (float) $order->discount_total,
            'shipping_total' => (float) $order->shipping_total,
            'tax_total' => (float) $order->tax_total,
            'total' => (float) $order->total,
            'currency' => $order->currency,
            'items_count' => (int) $order->items()->count(),
            'placed_at' => $order->placed_at?->toIso8601String(),
            'meta' => $order->meta,
        ]);
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.orders')];
        $customerId = $this->order['customer_id'];

        if (is_int($customerId) && $customerId > 0) {
            $channels[] = new PrivateChannel("customer.orders.{$customerId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'orders.updated';
    }

    /**
     * @return array{
     *     order:array{
     *         id:int,
     *         number:string|null,
     *         customer_id:int|null,
     *         status:string|null,
     *         payment_status:string|null,
     *         fulfillment_status:string|null,
     *         subtotal:float,
     *         discount_total:float,
     *         shipping_total:float,
     *         tax_total:float,
     *         total:float,
     *         currency:string|null,
     *         items_count:int,
     *         placed_at:string|null,
     *         meta:array<string,mixed>|null
     *     }
     * }
     */
    public function broadcastWith(): array
    {
        return [
            'order' => $this->order,
        ];
    }
}
