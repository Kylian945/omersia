<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Meilisearch\Client;

class ConfigureProductsIndex extends Command
{
    protected $signature = 'products:meili-config';

    protected $description = 'Configure Meilisearch settings for products index';

    public function handle()
    {
        $client = new Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );

        $index = $client->index('products');

        // Filtres / facettes
        $index->updateFilterableAttributes([
            'is_active',
            'shop_id',
            'type',
            'manage_stock',
            'categories',      // tableau d’IDs => parfait pour facettes
            'price',
            'stock_qty',
        ]);

        // Tri (optionnel)
        $index->updateSortableAttributes([
            'price',
            'compare_at_price',
            'stock_qty',
        ]);

        // Champs recherchables (optionnel mais recommandé)
        $index->updateSearchableAttributes([
            'name',
            'description',
            'short_description',
            'sku',
            'slug',
        ]);

        $this->info('✅ Meilisearch settings updated for products');

        return self::SUCCESS;
    }
}
