<?php

declare(strict_types=1);

namespace Omersia\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Omersia\Catalog\Models\Invoice;
use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\Sequence;

class SequenceSeeder extends Seeder
{
    /**
     * Seed sequences table with current max values from existing data.
     * This ensures no number collisions when migrating to sequence-based generation.
     */
    public function run(): void
    {
        // Seed order_number sequence
        $maxOrderId = Order::max('id') ?? 0;
        $orderSequenceValue = 1000 + $maxOrderId;

        Sequence::updateOrCreate(
            ['name' => 'order_number'],
            [
                'prefix' => 'ORD-',
                'current_value' => $orderSequenceValue,
                'padding' => 8,
            ]
        );

        $this->command->info("Order sequence initialized at: {$orderSequenceValue}");

        // Seed invoice_number sequences per year
        $years = Invoice::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->pluck('year');

        foreach ($years as $year) {
            $maxInvoiceNumber = Invoice::where('number', 'like', "INV-{$year}-%")
                ->selectRaw('CAST(SUBSTRING(number, -4) AS UNSIGNED) as num')
                ->max('num') ?? 0;

            Sequence::updateOrCreate(
                ['name' => "invoice_number_{$year}"],
                [
                    'prefix' => "INV-{$year}-",
                    'current_value' => $maxInvoiceNumber,
                    'padding' => 4,
                ]
            );

            $this->command->info("Invoice sequence for {$year} initialized at: {$maxInvoiceNumber}");
        }

        // Create current year sequence if not exists
        $currentYear = date('Y');
        if (! $years->contains($currentYear)) {
            Sequence::updateOrCreate(
                ['name' => "invoice_number_{$currentYear}"],
                [
                    'prefix' => "INV-{$currentYear}-",
                    'current_value' => 0,
                    'padding' => 4,
                ]
            );

            $this->command->info("Invoice sequence for {$currentYear} created at: 0");
        }
    }
}
