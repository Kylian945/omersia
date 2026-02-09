<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Omersia\Customer\Models\Customer;

/**
 * @property int $id
 * @property mixed $customer_id
 * @property mixed $type
 * @property mixed $status
 * @property mixed $reason
 * @property mixed $admin_notes
 * @property mixed $processed_by
 * @property \Illuminate\Support\Carbon|null $requested_at
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property mixed $export_file_path
 * @property \Illuminate\Support\Carbon|null $export_expires_at
 * @property bool $data_deleted
 * @property array<string, mixed>|null $deleted_data_summary
 * @property-read Customer|null $customer
 * @property-read \App\Models\User|null $processedBy
 */
class DataRequest extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'status',
        'reason',
        'admin_notes',
        'processed_by',
        'requested_at',
        'processed_at',
        'completed_at',
        'export_file_path',
        'export_expires_at',
        'data_deleted',
        'deleted_data_summary',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'export_expires_at' => 'datetime',
        'data_deleted' => 'boolean',
        'deleted_data_summary' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    /**
     * Vérifier si la demande est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Vérifier si la demande est complétée
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Vérifier si l'export est encore disponible
     */
    public function isExportAvailable(): bool
    {
        if (! $this->export_file_path || ! $this->export_expires_at) {
            return false;
        }

        return $this->export_expires_at->isFuture();
    }

    /**
     * Marquer comme en cours de traitement
     */
    public function markAsProcessing(int $userId): void
    {
        $this->update([
            'status' => 'processing',
            'processed_by' => $userId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Marquer comme complétée
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Marquer comme rejetée
     */
    public function markAsRejected(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $reason,
            'completed_at' => now(),
        ]);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
