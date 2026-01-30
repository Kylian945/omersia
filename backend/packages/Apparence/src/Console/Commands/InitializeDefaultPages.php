<?php

declare(strict_types=1);

namespace Omersia\Apparence\Console\Commands;

use Illuminate\Console\Command;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Services\ThemePageConfigService;
use Omersia\Core\Models\Shop;

class InitializeDefaultPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omersia:init-pages
        {--force : Force recreation of existing pages}
        {--theme= : Theme slug to use (default: active theme)}
        {--demo : Use demo configuration with sample categories and products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize e-commerce pages from active theme configuration';

    /**
     * Execute the console command.
     */
    public function handle(ThemePageConfigService $pageConfigService)
    {
        $shop = Shop::first();

        if (! $shop) {
            $this->error('No shop found. Please create a shop first.');

            return Command::FAILURE;
        }

        // Determine which theme to use
        $themeSlug = $this->option('theme');

        if ($themeSlug) {
            $theme = Theme::where('slug', $themeSlug)->first();
            if (! $theme) {
                $this->error("Theme '{$themeSlug}' not found.");

                return Command::FAILURE;
            }
        } else {
            $theme = Theme::where('shop_id', $shop->id)
                ->where('is_active', true)
                ->first();

            if (! $theme) {
                $this->error('No active theme found. Please activate a theme first or specify one with --theme=slug');

                return Command::FAILURE;
            }
        }

        $forceUpdate = $this->option('force');
        $useDemo = $this->option('demo');

        $this->info("Initializing pages for shop: {$shop->display_name}");
        $this->info("Using theme: {$theme->name} ({$theme->slug})");
        $this->info('Configuration: '.($useDemo ? 'Demo (with sample data)' : 'Default (generic)'));
        $this->newLine();

        // Apply theme pages configuration
        $stats = $pageConfigService->applyThemePagesConfig($theme, $shop, $forceUpdate, $useDemo);

        // Display results
        $this->newLine();
        $this->info('Summary:');
        $this->info("- Created: {$stats['created']} pages");
        $this->info("- Updated: {$stats['updated']} pages");
        $this->info("- Skipped: {$stats['skipped']} pages");

        if (! empty($stats['errors'])) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($stats['errors'] as $error) {
                $this->error("  - {$error}");
            }

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Pages initialized successfully!');

        return Command::SUCCESS;
    }
}
