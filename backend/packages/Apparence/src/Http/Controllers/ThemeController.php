<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Omersia\Apparence\Http\Requests\ThemeCustomizationUpdateRequest;
use Omersia\Apparence\Http\Requests\ThemeLogoUpdateRequest;
use Omersia\Apparence\Http\Requests\ThemeShopNameUpdateRequest;
use Omersia\Apparence\Http\Requests\ThemeUploadRequest;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Services\ThemeCustomizationService;
use Omersia\Apparence\Services\ThemePageConfigService;
use Omersia\Apparence\Services\ThemeWidgetService;
use Omersia\Core\Models\Shop;
use ZipArchive;

class ThemeController
{
    public function __construct(
        protected ThemeCustomizationService $customizationService,
        protected ThemePageConfigService $pageConfigService,
        protected ThemeWidgetService $widgetService
    ) {}

    public function index()
    {
        $shop = Shop::first();

        // Récupérer tous les thèmes
        $themes = Theme::where('shop_id', $shop->id)
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Si aucun thème par défaut n'existe, le créer
        if ($themes->isEmpty()) {
            $this->createDefaultTheme($shop);
            $themes = Theme::where('shop_id', $shop->id)->get();
        }

        return view('admin::apparence.theme.index', compact('shop', 'themes'));
    }

    protected function createDefaultTheme(Shop $shop)
    {
        // Load Vision theme configuration
        $visionConfigPath = storage_path('app/theme-vision.json');
        $visionConfig = json_decode(file_get_contents($visionConfigPath), true);

        $theme = Theme::create([
            'shop_id' => $shop->id,
            'name' => 'Vision',
            'slug' => 'vision',
            'description' => 'Thème moderne et élégant pour votre e-commerce avec tous les widgets essentiels',
            'version' => '1.0.0',
            'author' => 'Omersia',
            'component_path' => 'vision',
            'widgets_config' => $visionConfig['widgets'] ?? [],
            'settings_schema' => $visionConfig['settings_schema'] ?? null,
            'is_active' => true,
            'is_default' => true,
            'metadata' => [
                'technologies' => ['Next.js 14', 'Tailwind CSS', 'TypeScript'],
                'features' => ['Responsive', 'SEO optimisé', 'Performance élevée', '17 widgets'],
            ],
        ]);

        // Initialize default customization settings
        $this->customizationService->initializeDefaultSettings($theme);

        // Create default e-commerce pages from theme config
        $this->pageConfigService->applyThemePagesConfig($theme, $shop);

        return $theme;
    }

    public function updateLogo(ThemeLogoUpdateRequest $request)
    {

        $shop = Shop::first();

        // Supprimer l'ancien logo si existant
        if ($shop->logo_path && Storage::disk('public')->exists($shop->logo_path)) {
            Storage::disk('public')->delete($shop->logo_path);
        }

        // Sauvegarder le nouveau logo
        $path = $request->file('logo')->store('logos', 'public');

        $shop->update([
            'logo_path' => $path,
        ]);

        return redirect()->route('admin.apparence.theme.index')
            ->with('success', 'Logo mis à jour avec succès.');
    }

    public function updateShopName(ThemeShopNameUpdateRequest $request)
    {

        $shop = Shop::first();

        $shop->update([
            'display_name' => $request->display_name,
        ]);

        return redirect()->route('admin.apparence.theme.index')
            ->with('success', 'Nom de la boutique mis à jour avec succès.');
    }

    public function uploadTheme(ThemeUploadRequest $request)
    {

        $shop = Shop::first();

        DB::beginTransaction();
        try {
            // Stocker le fichier ZIP temporairement
            $zipFile = $request->file('theme');
            $tempPath = $zipFile->getPathname();

            // Ouvrir le ZIP et lire theme.json
            $zip = new ZipArchive;
            if ($zip->open($tempPath) !== true) {
                throw new \Exception('Impossible d\'ouvrir le fichier ZIP');
            }

            // Lire theme.json
            $themeJsonPath = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];

                if (basename($name) === 'theme.json') {
                    $themeJsonPath = $name;
                    break;
                }
            }

            if (! $themeJsonPath) {
                $zip->close();
                throw new \Exception('Le fichier theme.json est manquant dans le ZIP');
            }

            $themeJsonContent = $zip->getFromName($themeJsonPath);
            if ($themeJsonContent === false) {
                $zip->close();
                throw new \Exception('Impossible de lire le fichier theme.json');
            }

            $themeData = json_decode($themeJsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $zip->close();
                throw new \Exception('Le fichier theme.json est invalide');
            }

            // Utiliser le nom du theme.json ou du request
            $themeName = $request->name ?? $themeData['name'] ?? 'Nouveau thème';
            $themeDescription = $request->description ?? $themeData['description'] ?? '';

            // Créer le slug
            $slug = Str::slug($themeName);
            $counter = 1;
            while (Theme::where('slug', $slug)->exists()) {
                $slug = Str::slug($themeName).'-'.$counter;
                $counter++;
            }

            // Extraire les composants React si présents dans le ZIP
            $componentPath = null;
            $frontendBasePath = is_dir('/var/www/storefront')
                ? '/var/www/storefront/src/components/themes'
                : base_path('../storefront/src/components/themes');

            // Vérifier si le dossier components/ existe dans le ZIP
            $hasComponents = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (strpos($filename, 'components/') !== false) {
                    $hasComponents = true;
                    break;
                }
            }

            if ($hasComponents) {
                $themeComponentDir = $frontendBasePath.'/'.$slug;

                if (! is_dir($themeComponentDir)) {
                    mkdir($themeComponentDir, 0755, true);
                }

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);

                    $pos = strpos($filename, 'components/');
                    if ($pos === false) {
                        continue;
                    }

                    // chemin relatif à partir de "components/"
                    $relativePath = substr($filename, $pos + strlen('components/'));

                    // Ignorer les dossiers
                    if (empty($relativePath) || str_ends_with($relativePath, '/')) {
                        continue;
                    }

                    $destPath = $themeComponentDir.'/'.$relativePath;
                    $destDir = dirname($destPath);

                    if (! is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }

                    $fileContent = $zip->getFromIndex($i);
                    if ($fileContent !== false) {
                        file_put_contents($destPath, $fileContent);
                    }
                }

                $componentPath = $slug;
            }

            // Extract backend views if present in the ZIP
            $hasBackendViews = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (strpos($filename, 'backend-views/') !== false) {
                    $hasBackendViews = true;
                    break;
                }
            }

            if ($hasBackendViews) {
                $backendViewsPath = base_path('packages/Apparence/src/resources/views/themes/'.$slug);

                if (! is_dir($backendViewsPath)) {
                    mkdir($backendViewsPath, 0755, true);
                }

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);

                    $pos = strpos($filename, 'backend-views/');
                    if ($pos === false) {
                        continue;
                    }

                    // chemin relatif à partir de "backend-views/"
                    $relativePath = substr($filename, $pos + strlen('backend-views/'));

                    // Ignorer les dossiers
                    if (empty($relativePath) || str_ends_with($relativePath, '/')) {
                        continue;
                    }

                    $destPath = $backendViewsPath.'/'.$relativePath;
                    $destDir = dirname($destPath);

                    if (! is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }

                    $fileContent = $zip->getFromIndex($i);
                    if ($fileContent !== false) {
                        file_put_contents($destPath, $fileContent);
                    }
                }
            }

            // Extract and save pages.json before closing ZIP
            $pagesConfigPath = $this->pageConfigService->extractAndSavePagesConfig($zip, (object) ['slug' => $slug]);

            $zip->close();

            // Stocker le fichier ZIP
            $zipPath = $zipFile->store('themes', 'public');

            // Stocker l'image de prévisualisation si fournie
            $previewPath = null;
            if ($request->hasFile('preview_image')) {
                $previewPath = $request->file('preview_image')->store('themes/previews', 'public');
            }

            // Extract widgets configuration from theme.json
            $widgetsConfig = $themeData['widgets'] ?? null;

            // Créer le thème
            $theme = Theme::create([
                'shop_id' => $shop->id,
                'name' => $themeName,
                'slug' => $slug,
                'description' => $themeDescription,
                'version' => $themeData['version'] ?? '1.0.0',
                'author' => $themeData['author'] ?? 'Custom',
                'preview_image' => $previewPath,
                'zip_path' => $zipPath,
                'component_path' => $componentPath,
                'pages_config_path' => $pagesConfigPath,
                'widgets_config' => $widgetsConfig,
                'is_active' => false,
                'is_default' => false,
                'metadata' => $themeData['metadata'] ?? [],
            ]);

            // Créer les settings si présents dans theme.json
            if (isset($themeData['settings']) && is_array($themeData['settings'])) {
                $this->createThemeSettings($theme, $themeData['settings']);
            } else {
                // Initialiser avec les settings par défaut si aucun settings fourni
                $this->customizationService->initializeDefaultSettings($theme);
            }

            DB::commit();

            return redirect()->route('admin.apparence.theme.index')
                ->with('success', 'Thème uploadé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Supprimer les fichiers uploadés en cas d'erreur
            if (isset($zipPath) && Storage::disk('public')->exists($zipPath)) {
                Storage::disk('public')->delete($zipPath);
            }
            if (isset($previewPath) && Storage::disk('public')->exists($previewPath)) {
                Storage::disk('public')->delete($previewPath);
            }

            return redirect()->route('admin.apparence.theme.index')
                ->with('error', 'Erreur lors de l\'upload du thème : '.$e->getMessage());
        }
    }

    protected function createThemeSettings(Theme $theme, array $settings): void
    {
        foreach ($settings as $key => $value) {
            // Déterminer le groupe et le type selon la clé
            $group = $this->determineSettingGroup($key);
            $type = $this->determineSettingType($key);

            \Omersia\Apparence\Models\ThemeSetting::create([
                'theme_id' => $theme->id,
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => $group,
            ]);
        }
    }

    protected function determineSettingGroup(string $key): string
    {
        $groupMapping = [
            'primary' => 'colors',
            'secondary' => 'colors',
            'page_bg' => 'backgrounds',
            'card_bg' => 'backgrounds',
            'input_bg' => 'backgrounds',
            'header_bg' => 'backgrounds',
            'heading_color' => 'texts',
            'body_color' => 'texts',
            'muted_color' => 'texts',
            'link_color' => 'texts',
            'border_default' => 'borders',
            'border_hover' => 'borders',
            'success_color' => 'states',
            'success_bg' => 'states',
            'error_color' => 'states',
            'error_bg' => 'states',
            'promo_bg' => 'states',
            'promo_text' => 'states',
            'variant_badge_bg' => 'states',
            'variant_badge_text' => 'states',
            'variant_badge_active_bg' => 'states',
            'variant_badge_active_text' => 'states',
            'heading_font' => 'typography',
            'body_font' => 'typography',
            'heading_weight' => 'typography',
            'body_size' => 'typography',
            'border_radius' => 'layout',
            'card_style' => 'layout',
            'header_style' => 'header',
            'header_sticky' => 'header',
            'button_style' => 'buttons',
            'button_primary_text' => 'buttons',
            'button_secondary_text' => 'buttons',
        ];

        return $groupMapping[$key] ?? 'general';
    }

    protected function determineSettingType(string $key): string
    {
        // Détecter le type selon la clé
        if (
            str_contains($key, '_color') || str_contains($key, '_bg') || str_contains($key, '_text') ||
            in_array($key, ['primary', 'secondary'])
        ) {
            return 'color';
        }

        if (str_contains($key, '_font') || str_contains($key, '_style') || str_contains($key, '_sticky')) {
            return 'select';
        }

        if (str_contains($key, '_size') || str_contains($key, '_weight')) {
            return is_numeric($key) ? 'number' : 'select';
        }

        return 'text';
    }

    /**
     * Compare widgets between current theme and target theme
     * Returns differences to display in confirmation modal
     */
    public function compareWidgets(Theme $theme)
    {
        $shop = Shop::first();

        // Get current active theme
        $currentTheme = Theme::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->first();

        // If no active theme or same theme, no comparison needed
        if (! $currentTheme || $currentTheme->id === $theme->id) {
            return response()->json([
                'has_incompatibilities' => false,
                'can_activate_directly' => true,
            ]);
        }

        // Compare widgets
        $comparison = $this->widgetService->compareThemeWidgets($currentTheme, $theme);

        return response()->json($comparison);
    }

    public function activate(Request $request, Theme $theme)
    {
        $shop = Shop::first();

        DB::beginTransaction();
        try {
            // Get current active theme to compare widgets
            $currentTheme = Theme::where('shop_id', $shop->id)
                ->where('is_active', true)
                ->first();

            // If changing theme, clean incompatible widgets
            if ($currentTheme && $currentTheme->id !== $theme->id) {
                $comparison = $this->widgetService->compareThemeWidgets($currentTheme, $theme);

                if ($comparison['has_incompatibilities']) {
                    // Get widget types to remove
                    $widgetTypesToRemove = array_column($comparison['removed_widgets'], 'type');

                    // Clean incompatible widgets from all pages
                    $this->widgetService->cleanIncompatibleWidgets($shop->id, $widgetTypesToRemove);
                }
            }

            // Désactiver tous les autres thèmes
            Theme::where('shop_id', $shop->id)
                ->where('id', '!=', $theme->id)
                ->update(['is_active' => false]);

            // Activer le thème sélectionné
            $theme->update(['is_active' => true]);

            // Appliquer la configuration des pages du thème
            $stats = $this->pageConfigService->applyThemePagesConfig($theme, $shop, forceUpdate: true);

            // Effacer le cache pour appliquer les paramètres immédiatement
            $this->customizationService->clearThemeCache($theme);

            DB::commit();

            $message = 'Thème "'.$theme->name.'" activé avec succès.';
            if ($stats['created'] > 0 || $stats['updated'] > 0) {
                $message .= " Pages créées: {$stats['created']}, mises à jour: {$stats['updated']}.";
            }

            return redirect()->route('admin.apparence.theme.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.apparence.theme.index')
                ->with('error', 'Erreur lors de l\'activation du thème: '.$e->getMessage());
        }
    }

    public function destroy(Theme $theme)
    {
        if ($theme->is_default) {
            return redirect()->route('admin.apparence.theme.index')
                ->with('error', 'Impossible de supprimer le thème par défaut.');
        }

        if ($theme->is_active) {
            return redirect()->route('admin.apparence.theme.index')
                ->with('error', 'Impossible de supprimer le thème actif. Activez un autre thème d\'abord.');
        }

        // Supprimer les fichiers
        if ($theme->zip_path && Storage::disk('public')->exists($theme->zip_path)) {
            Storage::disk('public')->delete($theme->zip_path);
        }
        if ($theme->preview_image && Storage::disk('public')->exists($theme->preview_image)) {
            Storage::disk('public')->delete($theme->preview_image);
        }

        $theme->delete();

        return redirect()->route('admin.apparence.theme.index')
            ->with('success', 'Thème supprimé avec succès.');
    }

    /**
     * Show theme customization page
     */
    public function customize(Theme $theme)
    {
        $shop = Shop::first();

        // Load settings with theme
        $theme->load('settings');

        // Get theme settings schema (custom or default)
        $config = $this->customizationService->getThemeSettingsSchema($theme);

        // Get current values
        $currentSettings = $theme->getSettingsArray();

        return view('admin::apparence.theme.customize', compact('shop', 'theme', 'config', 'currentSettings'));
    }

    /**
     * Update theme customization settings
     */
    public function updateCustomization(ThemeCustomizationUpdateRequest $request, Theme $theme)
    {
        $validated = $request->validated();

        try {
            $this->customizationService->updateSettings($theme, $validated['settings']);

            return redirect()->route('admin.apparence.theme.customize', $theme)
                ->with('success', 'Personnalisation mise à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()->route('admin.apparence.theme.customize', $theme)
                ->with('error', 'Erreur lors de la mise à jour : '.$e->getMessage());
        }
    }

    /**
     * Reset theme to default settings
     */
    public function resetCustomization(Theme $theme)
    {
        try {
            // Delete all current settings
            $theme->settings()->delete();

            // Reinitialize with defaults
            $this->customizationService->initializeDefaultSettings($theme);

            return redirect()->route('admin.apparence.theme.customize', $theme)
                ->with('success', 'Paramètres réinitialisés aux valeurs par défaut.');
        } catch (\Exception $e) {
            return redirect()->route('admin.apparence.theme.customize', $theme)
                ->with('error', 'Erreur lors de la réinitialisation.');
        }
    }
}
