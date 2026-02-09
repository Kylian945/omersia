<?php

declare(strict_types=1);

namespace App\Events\Realtime;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Omersia\Gdpr\Models\DataRequest;

class GdprRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array{
     *     id:int,
     *     customer_id:int,
     *     type:string,
     *     status:string,
     *     reason:string|null,
     *     requested_at:string|null,
     *     processed_at:string|null,
     *     completed_at:string|null,
     *     export_file_path:string|null,
     *     export_expires_at:string|null,
     *     data_deleted:bool
     * }  $request
     */
    public function __construct(
        public readonly array $request
    ) {}

    public static function fromModel(DataRequest $request): self
    {
        return new self([
            'id' => (int) $request->id,
            'customer_id' => (int) $request->customer_id,
            'type' => (string) $request->type,
            'status' => (string) $request->status,
            'reason' => $request->reason,
            'requested_at' => $request->requested_at?->toIso8601String(),
            'processed_at' => $request->processed_at?->toIso8601String(),
            'completed_at' => $request->completed_at?->toIso8601String(),
            'export_file_path' => $request->export_file_path,
            'export_expires_at' => $request->export_expires_at?->toIso8601String(),
            'data_deleted' => (bool) $request->data_deleted,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.gdpr'),
            new PrivateChannel("customer.gdpr.{$this->request['customer_id']}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'gdpr.requests.updated';
    }

    /**
     * @return array{
     *     request:array{
     *         id:int,
     *         customer_id:int,
     *         type:string,
     *         status:string,
     *         reason:string|null,
     *         requested_at:string|null,
     *         processed_at:string|null,
     *         completed_at:string|null,
     *         export_file_path:string|null,
     *         export_expires_at:string|null,
     *         data_deleted:bool
     *     }
     * }
     */
    public function broadcastWith(): array
    {
        return [
            'request' => $this->request,
        ];
    }
}
