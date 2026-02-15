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
    private const THEME_MEDIA_SNAPSHOT_META_KEY = '_theme_page_media_snapshot';

    private const MEDIA_PROP_KEYS = [
        'image',
        'image_url',
        'imageUrl',
        'images',
        'src',
        'backgroundImage',
        'background_image',
        'desktopImage',
        'desktop_image',
        'mobileImage',
        'mobile_image',
        'gallery',
        'galleries',
        'media',
        'medias',
        'asset',
        'assets',
        'file',
        'files',
        'thumbnail',
        'thumbnails',
        'poster',
        'cover',
    ];

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
    public function applyThemePagesConfig(
        Theme $theme,
        Shop $shop,
        bool $forceUpdate = false,
        bool $useDemo = false,
        bool $preserveMediaByTheme = false
    ): array {
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

        $themeSnapshotPages = $preserveMediaByTheme ? $this->getThemePageMediaSnapshotPages($theme) : [];

        foreach ($pagesConfig['pages'] as $pageConfig) {
            try {
                $pageType = isset($pageConfig['type']) && is_string($pageConfig['type']) ? $pageConfig['type'] : null;
                $pageSlug = isset($pageConfig['slug']) && is_string($pageConfig['slug']) ? $pageConfig['slug'] : null;
                $snapshotContentByLocale = $preserveMediaByTheme
                    ? $this->getSnapshotContentByLocaleForPage($themeSnapshotPages, $pageType, $pageSlug)
                    : [];

                $result = $this->createOrUpdatePage(
                    $shop,
                    $pageConfig,
                    $forceUpdate,
                    $snapshotContentByLocale,
                    $preserveMediaByTheme
                );
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
    protected function createOrUpdatePage(
        Shop $shop,
        array $pageConfig,
        bool $forceUpdate,
        array $snapshotContentByLocale = [],
        bool $preserveMediaByTheme = false
    ): string {
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
            if ($preserveMediaByTheme) {
                if (! empty($snapshotContentByLocale)) {
                    $translations = $this->mergeTranslationsPreservingMediaFromContentByLocale(
                        $translations,
                        $snapshotContentByLocale
                    );
                }
            } else {
                $existingPage->loadMissing('translations');
                $translations = $this->mergeTranslationsPreservingMedia($translations, $existingPage);
            }
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
     * Save current page contents as media snapshot for a specific theme.
     */
    public function saveThemePageMediaSnapshot(Theme $theme, Shop $shop): void
    {
        $pages = EcommercePage::query()
            ->where('shop_id', $shop->id)
            ->with('translations')
            ->get();

        $snapshotPages = [];

        foreach ($pages as $page) {
            $type = is_string($page->type) ? $page->type : '';
            if ($type === '') {
                continue;
            }

            $slug = is_string($page->slug) ? $page->slug : null;
            $pageKey = $this->buildPageSnapshotKey($type, $slug);

            $translations = [];
            foreach ($page->translations as $translation) {
                if (! is_string($translation->locale) || $translation->locale === '') {
                    continue;
                }

                $translations[$translation->locale] = is_array($translation->content_json)
                    ? $translation->content_json
                    : ['sections' => []];
            }

            if ($translations === []) {
                continue;
            }

            $snapshotPages[$pageKey] = [
                'type' => $type,
                'slug' => $slug,
                'translations' => $translations,
            ];
        }

        $metadata = is_array($theme->metadata) ? $theme->metadata : [];
        $metadata[self::THEME_MEDIA_SNAPSHOT_META_KEY] = [
            'saved_at' => now()->toIso8601String(),
            'pages' => $snapshotPages,
        ];

        $theme->forceFill([
            'metadata' => $metadata,
        ])->save();
    }

    /**
     * @return array<string, array{type: string, slug: ?string, translations: array<string, array<string, mixed>>}>
     */
    protected function getThemePageMediaSnapshotPages(Theme $theme): array
    {
        $metadata = is_array($theme->metadata) ? $theme->metadata : [];
        $snapshot = $metadata[self::THEME_MEDIA_SNAPSHOT_META_KEY] ?? null;
        if (! is_array($snapshot)) {
            return [];
        }

        $snapshotPages = $snapshot['pages'] ?? null;
        if (! is_array($snapshotPages)) {
            return [];
        }

        $pages = [];
        foreach ($snapshotPages as $pageKey => $pageData) {
            if (! is_string($pageKey) || ! is_array($pageData)) {
                continue;
            }

            $type = isset($pageData['type']) && is_string($pageData['type']) ? $pageData['type'] : '';
            if ($type === '') {
                continue;
            }

            $slug = isset($pageData['slug']) && is_string($pageData['slug']) ? $pageData['slug'] : null;
            $translationsRaw = $pageData['translations'] ?? null;
            if (! is_array($translationsRaw)) {
                continue;
            }

            $translations = [];
            foreach ($translationsRaw as $locale => $content) {
                if (! is_string($locale) || $locale === '' || ! is_array($content)) {
                    continue;
                }
                $translations[$locale] = $content;
            }

            if ($translations === []) {
                continue;
            }

            $pages[$pageKey] = [
                'type' => $type,
                'slug' => $slug,
                'translations' => $translations,
            ];
        }

        return $pages;
    }

    /**
     * @param  array<string, array{type: string, slug: ?string, translations: array<string, array<string, mixed>>}>  $snapshotPages
     * @return array<string, array<string, mixed>>
     */
    protected function getSnapshotContentByLocaleForPage(array $snapshotPages, ?string $type, ?string $slug): array
    {
        if (! is_string($type) || trim($type) === '') {
            return [];
        }

        $snapshotKey = $this->buildPageSnapshotKey($type, $slug);
        if (! isset($snapshotPages[$snapshotKey])) {
            return [];
        }

        return $snapshotPages[$snapshotKey]['translations'];
    }

    protected function buildPageSnapshotKey(string $type, ?string $slug): string
    {
        $normalizedType = strtolower(trim($type));
        $normalizedSlug = is_string($slug) ? trim($slug) : '';

        return $normalizedType.'::'.$normalizedSlug;
    }

    /**
     * Merge new theme translations while preserving existing media fields.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function mergeTranslationsPreservingMedia(array $translations, EcommercePage $existingPage): array
    {
        $existingContentByLocale = [];
        foreach ($existingPage->translations as $existingTranslation) {
            $existingContentByLocale[$existingTranslation->locale] = is_array($existingTranslation->content_json)
                ? $existingTranslation->content_json
                : ['sections' => []];
        }

        return $this->mergeTranslationsPreservingMediaFromContentByLocale($translations, $existingContentByLocale);
    }

    /**
     * Merge new theme translations while preserving media from locale content map.
     *
     * @param  array<string, mixed>  $translations
     * @param  array<string, array<string, mixed>>  $existingContentByLocale
     * @return array<string, array<string, mixed>>
     */
    protected function mergeTranslationsPreservingMediaFromContentByLocale(
        array $translations,
        array $existingContentByLocale
    ): array {
        if ($existingContentByLocale === []) {
            return $translations;
        }

        foreach ($translations as $locale => &$translation) {
            if (! is_array($translation)) {
                continue;
            }

            $newContent = isset($translation['content']) && is_array($translation['content'])
                ? $translation['content']
                : ['sections' => []];

            $existingContent = $existingContentByLocale[$locale] ?? null;
            if (! is_array($existingContent)) {
                continue;
            }

            $translation['content'] = $this->mergeContentPreservingMedia($newContent, $existingContent);
        }
        unset($translation);

        return $translations;
    }

    /**
     * Merge media props from existing content into new content.
     */
    protected function mergeContentPreservingMedia(array $newContent, array $existingContent): array
    {
        /** @var array<string, array<int, array<string, mixed>>> $existingWidgetsByType */
        $existingWidgetsByType = [];
        /** @var array<string, array{widget: array<string, mixed>, type: string, index: int}> $existingWidgetsById */
        $existingWidgetsById = [];
        if (isset($existingContent['sections']) && is_array($existingContent['sections'])) {
            foreach ($existingContent['sections'] as $section) {
                if (! is_array($section) || ! isset($section['columns']) || ! is_array($section['columns'])) {
                    continue;
                }
                $this->collectWidgetsByTypeFromColumns($section['columns'], $existingWidgetsByType, $existingWidgetsById);
            }
        }

        if (empty($existingWidgetsByType)) {
            return $newContent;
        }

        $existingWidgetIndexByType = [];
        $consumedWidgetIds = [];
        if (isset($newContent['sections']) && is_array($newContent['sections'])) {
            foreach ($newContent['sections'] as &$section) {
                if (! is_array($section) || ! isset($section['columns']) || ! is_array($section['columns'])) {
                    continue;
                }

                $this->mergeWidgetsInColumnsPreservingMedia(
                    $section['columns'],
                    $existingWidgetsByType,
                    $existingWidgetIndexByType,
                    $existingWidgetsById,
                    $consumedWidgetIds
                );
            }
            unset($section);
        }

        return $newContent;
    }

    /**
     * Collect widgets grouped by type from standard and nested columns.
     *
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<string, array<int, array<string, mixed>>>  $widgetsByType
     * @param  array<string, array{widget: array<string, mixed>, type: string, index: int}>  $widgetsById
     */
    protected function collectWidgetsByTypeFromColumns(array $columns, array &$widgetsByType, array &$widgetsById): void
    {
        foreach ($columns as $column) {
            if (isset($column['widgets']) && is_array($column['widgets'])) {
                foreach ($column['widgets'] as $widget) {
                    if (! is_array($widget)) {
                        continue;
                    }

                    $type = $widget['type'] ?? null;
                    if (is_string($type) && $type !== '') {
                        /** @var array<string, mixed> $typedWidget */
                        $typedWidget = $widget;
                        $widgetsByType[$type][] = $typedWidget;

                        $widgetId = $widget['id'] ?? null;
                        if (is_string($widgetId) && $widgetId !== '') {
                            $widgetsById[$widgetId] = [
                                'widget' => $typedWidget,
                                'type' => $type,
                                'index' => count($widgetsByType[$type]) - 1,
                            ];
                        }
                    }

                    if (
                        ($widget['type'] ?? null) === 'container'
                        && isset($widget['props'])
                        && is_array($widget['props'])
                        && isset($widget['props']['columns'])
                        && is_array($widget['props']['columns'])
                    ) {
                        $this->collectWidgetsByTypeFromColumns($widget['props']['columns'], $widgetsByType, $widgetsById);
                    }
                }
            }

            if (isset($column['columns']) && is_array($column['columns'])) {
                $this->collectWidgetsByTypeFromColumns($column['columns'], $widgetsByType, $widgetsById);
            }
        }
    }

    /**
     * Merge media props into widgets for a set of columns.
     *
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<string, array<int, array<string, mixed>>>  $existingWidgetsByType
     * @param  array<string, int>  $existingWidgetIndexByType
     * @param  array<string, array{widget: array<string, mixed>, type: string, index: int}>  $existingWidgetsById
     * @param  array<string, bool>  $consumedWidgetIds
     */
    protected function mergeWidgetsInColumnsPreservingMedia(
        array &$columns,
        array $existingWidgetsByType,
        array &$existingWidgetIndexByType,
        array $existingWidgetsById,
        array &$consumedWidgetIds
    ): void {
        foreach ($columns as &$column) {
            if (isset($column['widgets']) && is_array($column['widgets'])) {
                foreach ($column['widgets'] as &$widget) {
                    if (! is_array($widget)) {
                        continue;
                    }

                    $type = $widget['type'] ?? null;
                    if (! is_string($type) || $type === '') {
                        continue;
                    }

                    $existingWidget = null;
                    $widgetId = $widget['id'] ?? null;
                    if (
                        is_string($widgetId)
                        && $widgetId !== ''
                        && isset($existingWidgetsById[$widgetId])
                    ) {
                        $existingWidgetMeta = $existingWidgetsById[$widgetId];
                        $existingWidget = $existingWidgetMeta['widget'];
                        $consumedWidgetIds[$widgetId] = true;
                    } elseif (isset($existingWidgetsByType[$type])) {
                        $currentIndex = $existingWidgetIndexByType[$type] ?? 0;
                        while (isset($existingWidgetsByType[$type][$currentIndex])) {
                            $candidateWidget = $existingWidgetsByType[$type][$currentIndex];
                            $currentIndex++;

                            $candidateId = $candidateWidget['id'] ?? null;
                            if (is_string($candidateId) && $candidateId !== '' && isset($consumedWidgetIds[$candidateId])) {
                                continue;
                            }

                            if (is_string($candidateId) && $candidateId !== '') {
                                $consumedWidgetIds[$candidateId] = true;
                            }
                            $existingWidget = $candidateWidget;
                            break;
                        }
                        $existingWidgetIndexByType[$type] = $currentIndex;
                    }

                    if (! is_array($existingWidget)) {
                        continue;
                    }

                    $newProps = isset($widget['props']) && is_array($widget['props']) ? $widget['props'] : [];
                    $existingProps = isset($existingWidget['props']) && is_array($existingWidget['props'])
                        ? $existingWidget['props']
                        : [];

                    $widget['props'] = $this->mergeWidgetPropsPreservingMedia($type, $newProps, $existingProps);

                    if (
                        ($widget['type'] ?? null) === 'container'
                        && isset($widget['props']['columns'])
                        && is_array($widget['props']['columns'])
                    ) {
                        $this->mergeWidgetsInColumnsPreservingMedia(
                            $widget['props']['columns'],
                            $existingWidgetsByType,
                            $existingWidgetIndexByType,
                            $existingWidgetsById,
                            $consumedWidgetIds
                        );
                    }
                }
                unset($widget);
            }

            if (isset($column['columns']) && is_array($column['columns'])) {
                $this->mergeWidgetsInColumnsPreservingMedia(
                    $column['columns'],
                    $existingWidgetsByType,
                    $existingWidgetIndexByType,
                    $existingWidgetsById,
                    $consumedWidgetIds
                );
            }
        }
        unset($column);
    }

    /**
     * Merge props recursively and preserve media values from existing props.
     *
     * @param  array<string|int, mixed>  $newProps
     * @param  array<string|int, mixed>  $existingProps
     * @return array<string|int, mixed>
     */
    protected function mergeWidgetPropsPreservingMedia(string $widgetType, array $newProps, array $existingProps): array
    {
        $mediaKeys = self::MEDIA_PROP_KEYS;
        if ($widgetType === 'image') {
            $mediaKeys[] = 'url';
        }

        foreach ($newProps as $key => &$value) {
            if (! array_key_exists($key, $existingProps)) {
                continue;
            }

            $existingValue = $existingProps[$key];
            if ($this->isMediaPropKey((string) $key, $mediaKeys)) {
                if (is_string($existingValue) && trim($existingValue) !== '') {
                    $value = $existingValue;
                } elseif (is_array($existingValue) && ! empty($existingValue)) {
                    $value = $existingValue;
                }

                continue;
            }

            if (is_array($value) && is_array($existingValue)) {
                $value = $this->mergeWidgetPropsPreservingMedia($widgetType, $value, $existingValue);
            }
        }
        unset($value);

        return $newProps;
    }

    /**
     * Determine if a prop key should be considered media-related.
     *
     * @param  array<int, string>  $mediaKeys
     */
    protected function isMediaPropKey(string $key, array $mediaKeys): bool
    {
        if (in_array($key, $mediaKeys, true)) {
            return true;
        }

        $normalizedKey = strtolower($key);

        return str_ends_with($normalizedKey, 'image')
            || str_ends_with($normalizedKey, '_image')
            || str_ends_with($normalizedKey, 'images')
            || str_ends_with($normalizedKey, '_images')
            || str_ends_with($normalizedKey, 'imageurl')
            || str_ends_with($normalizedKey, 'image_url')
            || str_ends_with($normalizedKey, 'gallery')
            || str_ends_with($normalizedKey, 'galleries')
            || str_ends_with($normalizedKey, 'thumbnail')
            || str_ends_with($normalizedKey, 'thumbnails')
            || str_ends_with($normalizedKey, 'poster')
            || str_ends_with($normalizedKey, 'cover');
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
