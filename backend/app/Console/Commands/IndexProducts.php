<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Omersia\Catalog\Models\Product;

class IndexProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index all active products in Meilisearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Indexing products...');

        $products = Product::where('is_active', true)->get();

        if ($products->isEmpty()) {
            $this->warn('No active products found to index.');

            return 0;
        }

        $this->info("Found {$products->count()} active products.");

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $product->searchable();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Products indexed successfully!');

        return 0;
    }
}
