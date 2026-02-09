<?php

declare(strict_types=1);

namespace Omersia\Catalog\Services;

use Illuminate\Support\Facades\DB;
use Omersia\Catalog\Models\Sequence;

class SequenceService
{
    /**
     * Generate the next sequential number atomically.
     *
     * This method uses pessimistic locking (SELECT FOR UPDATE) to ensure
     * that concurrent requests cannot generate duplicate sequence numbers.
     *
     * @param  string  $sequenceName  Unique sequence identifier
     * @param  string|null  $prefix  Optional prefix for the generated number
     * @param  int  $initialValue  Starting value if sequence doesn't exist (default: 1000)
     * @param  int  $padding  Padding length for str_pad (default: 8)
     * @return string The generated sequential number with optional prefix
     *
     * @throws \Exception if transaction fails or deadlock occurs
     */
    public function next(
        string $sequenceName,
        ?string $prefix = null,
        int $initialValue = 1000,
        int $padding = 8
    ): string {
        return DB::transaction(function () use ($sequenceName, $prefix, $initialValue, $padding) {
            // Fetch and lock the sequence row to prevent concurrent access
            $sequence = Sequence::where('name', $sequenceName)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                // Create new sequence if it doesn't exist
                $sequence = Sequence::create([
                    'name' => $sequenceName,
                    'prefix' => $prefix,
                    'current_value' => $initialValue,
                    'padding' => $padding,
                ]);
            }

            // Atomically increment the sequence value
            $sequence->increment('current_value');

            // Refresh to get the updated value
            $sequence->refresh();

            // Format the number with padding
            $formattedNumber = str_pad(
                (string) $sequence->current_value,
                $sequence->padding,
                '0',
                STR_PAD_LEFT
            );

            // Return with prefix if provided
            return ($sequence->prefix ?? $prefix ?? '').$formattedNumber;
        });
    }

    /**
     * Get current value of a sequence without incrementing.
     */
    public function current(string $sequenceName): ?int
    {
        $sequence = Sequence::where('name', $sequenceName)->first();

        return $sequence?->current_value;
    }

    /**
     * Reset a sequence to a specific value.
     * Useful for testing or data migration.
     */
    public function reset(string $sequenceName, int $value): void
    {
        DB::transaction(function () use ($sequenceName, $value) {
            $sequence = Sequence::where('name', $sequenceName)
                ->lockForUpdate()
                ->first();

            if ($sequence) {
                $sequence->update(['current_value' => $value]);
            }
        });
    }
}
