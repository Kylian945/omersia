<?php

declare(strict_types=1);

namespace Omersia\Apparence\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Apparence\Models\EcommercePageTranslation;
use Omersia\Apparence\Models\Theme;
use Omersia\Core\Models\Shop;

class ThemePageConfigService
{
    /**
     * Get pages configuration from theme's pages.json file
     *
     * @param  bool  $useDemo  Use demo configuration with sample data (categories, products)
     */
    public function getThemePagesConfig(Theme $theme, bool $useDemo = false): ?array
    {
        // Check if theme has a pages.json file stored
        if (! $theme->pages_config_path) {
            // Try to load default pages.json from examples
            return $this->getDefaultPagesConfig($useDemo);
        }

        // Read pages.json from storage
        $pagesJsonPath = storage_path('app/'.$theme->pages_config_path);

        if (! File::exists($pagesJsonPath)) {
            Log::warning("Pages config file not found for theme {$theme->slug}: {$pagesJsonPath}");

            return $this->getDefaultPagesConfig($useDemo);
        }

        $jsonContent = File::get($pagesJsonPath);
        $pagesConfig = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Invalid JSON in pages config for theme {$theme->slug}: ".json_last_error_msg());

            return null;
        }

        return $pagesConfig;
    }

    /**
     * Get default pages configuration
     *
     * @param  bool  $useDemo  Use demo configuration with sample data
     */
    public function getDefaultPagesConfig(bool $useDemo = false): array
    {
        $filename = $useDemo ? 'demo-theme-pages.json' : 'default-theme-pages.json';
        $defaultPath = base_path('packages/Apparence/examples/'.$filename);

        // Fallback to default if demo file doesn't exist
        if ($useDemo && ! File::exists($defaultPath)) {
            Log::warning('Demo pages config not found, falling back to default');
            $defaultPath = base_path('packages/Apparence/examples/default-theme-pages.json');
        }

        if (! File::exists($defaultPath)) {
            Log::error("Default pages config file not found: {$defaultPath}");

            return ['pages' => []];
        }

        $jsonContent = File::get($defaultPath);
        $config = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON in default pages config: '.json_last_error_msg());

            return ['pages' => []];
        }

        Log::info($useDemo ? 'Using demo pages configuration' : 'Using default pages configuration');

        return $config;
    }

    /**
     * Apply theme's page configuration to create/update e-commerce pages
     *
     * @param  bool  $forceUpdate  Force update existing pages
     * @param  bool  $useDemo  Use demo configuration with sample data
     * @return array Statistics about created/updated/skipped pages
     */
    public function applyThemePagesConfig(Theme $theme, Shop $shop, bool $forceUpdate = false, bool $useDemo = false): array
    {
        $pagesConfig = $this->getThemePagesConfig($theme, $useDemo);

        if (! $pagesConfig || ! isset($pagesConfig['pages'])) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['No valid pages configuration found'],
            ];
        }

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($pagesConfig['pages'] as $pageConfig) {
            try {
                $result = $this->createOrUpdatePage($shop, $pageConfig, $forceUpdate);
                $stats[$result]++;
            } catch (\Exception $e) {
                $stats['errors'][] = "Error processing {$pageConfig['type']} page: ".$e->getMessage();
                Log::error('Error creating/updating page', [
                    'theme' => $theme->slug,
                    'page_type' => $pageConfig['type'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Create or update a single e-commerce page
     *
     * @return string 'created', 'updated', or 'skipped'
     */
    protected function createOrUpdatePage(Shop $shop, array $pageConfig, bool $forceUpdate): string
    {
        $type = $pageConfig['type'] ?? null;
        $slug = $pageConfig['slug'] ?? null;
        $translations = $pageConfig['translations'] ?? [];

        if (! $type || empty($translations)) {
            throw new \Exception('Page type and translations are required');
        }

        // Check if page already exists
        $existingPage = EcommercePage::where('shop_id', $shop->id)
            ->where('type', $type)
            ->when($slug, fn ($q) => $q->where('slug', $slug))
            ->first();

        if ($existingPage && ! $forceUpdate) {
            return 'skipped';
        }

        if ($existingPage) {
            // Delete existing translations
            $existingPage->translations()->delete();
            $existingPage->delete();
            Log::info("Deleted existing {$type} page for update");
        }

        // Create the page
        $page = EcommercePage::create([
            'shop_id' => $shop->id,
            'type' => $type,
            'slug' => $slug,
            'is_active' => true,
        ]);

        // Create translations
        foreach ($translations as $locale => $translation) {
            $this->createPageTranslation($page, $locale, $translation, $shop);
        }

        return $existingPage ? 'updated' : 'created';
    }

    /**
     * Create a page translation
     */
    protected function createPageTranslation(EcommercePage $page, string $locale, array $translation, Shop $shop): void
    {
        $title = $translation['title'] ?? 'Page';
        $metaTitle = $translation['meta_title'] ?? "{$title} - {$shop->display_name}";
        $metaDescription = $translation['meta_description'] ?? '';
        $noindex = $translation['noindex'] ?? false;
        $content = $translation['content'] ?? ['sections' => []];

        EcommercePageTranslation::create([
            'ecommerce_page_id' => $page->id,
            'locale' => $locale,
            'title' => $title,
            'content_json' => $content, // Array - Laravel cast handles JSON encoding
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'noindex' => $noindex,
        ]);
    }

    /**
     * Validate a pages.json configuration
     *
     * @return array Validation errors (empty if valid)
     */
    public function validatePagesConfig(array $config): array
    {
        $errors = [];

        if (! isset($config['pages']) || ! is_array($config['pages'])) {
            $errors[] = 'Missing or invalid "pages" array';

            return $errors;
        }

        foreach ($config['pages'] as $index => $page) {
            $pageErrors = $this->validatePageConfig($page, $index);
            $errors = array_merge($errors, $pageErrors);
        }

        return $errors;
    }

    /**
     * Validate a single page configuration
     *
     * @return array Validation errors
     */
    protected function validatePageConfig(array $page, int $index): array
    {
        $errors = [];
        $prefix = "Page {$index}";

        // Check required fields
        if (! isset($page['type'])) {
            $errors[] = "{$prefix}: Missing 'type' field";
        } elseif (! in_array($page['type'], ['home', 'product', 'category'])) {
            $errors[] = "{$prefix}: Invalid type '{$page['type']}'. Must be: home, product, or category";
        }

        // Check translations
        if (! isset($page['translations']) || ! is_array($page['translations'])) {
            $errors[] = "{$prefix}: Missing or invalid 'translations' object";
        } else {
            foreach ($page['translations'] as $locale => $translation) {
                if (! isset($translation['title'])) {
                    $errors[] = "{$prefix} ({$locale}): Missing 'title' field";
                }

                if (! isset($translation['content']) || ! is_array($translation['content'])) {
                    $errors[] = "{$prefix} ({$locale}): Missing or invalid 'content' object";
                } elseif (! isset($translation['content']['sections']) || ! is_array($translation['content']['sections'])) {
                    $errors[] = "{$prefix} ({$locale}): Missing or invalid 'content.sections' array";
                }
            }
        }

        return $errors;
    }

    /**
     * Extract pages.json from theme ZIP and save it
     *
     * @return string|null Path to saved pages.json, or null if not found
     */
    public function extractAndSavePagesConfig(\ZipArchive $zip, Theme $theme): ?string
    {
        // Find pages.json in the ZIP
        $pagesJsonPath = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];

            if (basename($name) === 'pages.json') {
                $pagesJsonPath = $name;
                break;
            }
        }

        if (! $pagesJsonPath) {
            Log::warning("No pages.json found in theme ZIP for {$theme->slug}");

            return null;
        }

        // Extract pages.json content
        $pagesJsonContent = $zip->getFromName($pagesJsonPath);
        if ($pagesJsonContent === false) {
            Log::error("Failed to read pages.json from ZIP for {$theme->slug}");

            return null;
        }

        // Validate JSON
        $config = json_decode($pagesJsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Invalid JSON in pages.json for {$theme->slug}: ".json_last_error_msg());

            return null;
        }

        // Validate config structure
        $errors = $this->validatePagesConfig($config);
        if (! empty($errors)) {
            Log::error("Invalid pages.json structure for {$theme->slug}", ['errors' => $errors]);

            return null;
        }

        // Save pages.json to storage
        $storagePath = "themes/{$theme->slug}/pages.json";
        Storage::put($storagePath, $pagesJsonContent);

        return $storagePath;
    }
}
