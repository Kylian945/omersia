<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $customer_id
 * @property mixed $customer_email
 * @property mixed $data_request_id
 * @property array<string, mixed>|null $deleted_tables
 * @property array<string, mixed>|null $anonymized_tables
 * @property mixed $total_records_deleted
 * @property mixed $total_records_anonymized
 * @property mixed $deleted_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property mixed $deletion_method
 * @property mixed $notes
 * @property-read DataRequest|null $dataRequest
 * @property-read \App\Models\User|null $deletedBy
 */
class DataDeletionLog extends Model
{
    protected $fillable = [
        'customer_id',
        'customer_email',
        'data_request_id',
        'deleted_tables',
        'anonymized_tables',
        'total_records_deleted',
        'total_records_anonymized',
        'deleted_by',
        'deleted_at',
        'deletion_method',
        'notes',
    ];

    protected $casts = [
        'deleted_tables' => 'array',
        'anonymized_tables' => 'array',
        'deleted_at' => 'datetime',
    ];

    public function dataRequest(): BelongsTo
    {
        return $this->belongsTo(DataRequest::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}
