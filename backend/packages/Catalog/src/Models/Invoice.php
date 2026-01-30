<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'number',
        'issued_at',
        'total',
        'currency',
        'pdf_path',
        'data',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'data' => 'array',
        'total' => 'decimal:2',
    ];

    /**
     * Relation vers la commande
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Generate unique invoice number atomically.
     * Format: INV-YYYY-NNNN (e.g., INV-2026-0001)
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $sequenceName = "invoice_number_{$year}";
        $prefix = "INV-{$year}-";

        $sequenceService = app(\Omersia\Catalog\Services\SequenceService::class);

        return $sequenceService->next(
            sequenceName: $sequenceName,
            prefix: $prefix,
            initialValue: 0,  // Start at 0, will become 0001
            padding: 4         // 4-digit padding
        );
    }

    /**
     * Retourne le chemin complet du PDF
     */
    public function getPdfFullPathAttribute(): ?string
    {
        return $this->pdf_path ? storage_path('app/'.$this->pdf_path) : null;
    }

    /**
     * Retourne l'URL publique du PDF
     */
    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf_path ? url('storage/'.$this->pdf_path) : null;
    }
}
