<?php

declare(strict_types=1);

namespace Omersia\Apparence\Services;

use Illuminate\Support\Facades\Cache;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Models\ThemeSetting;

class ThemeCustomizationService
{
    /**
     * Get default theme settings configuration (Phase 1 MVP)
     */
    public function getDefaultSettings(): array
    {
        return [
            // Couleurs principales
            'colors' => [
                'primary' => [
                    'label' => 'Couleur principale',
                    'type' => 'color',
                    'default' => '#111827',
                    'description' => 'Utilisée pour les boutons CTA, liens actifs',
                ],
                'secondary' => [
                    'label' => 'Couleur secondaire',
                    'type' => 'color',
                    'default' => '#6366f1',
                    'description' => 'Accents secondaires',
                ],
            ],

            // Fonds
            'backgrounds' => [
                'page_bg' => [
                    'label' => 'Fond de page',
                    'type' => 'color',
                    'default' => '#ffffff',
                    'description' => 'Couleur de fond principale du site',
                ],
                'card_bg' => [
                    'label' => 'Fond des cartes',
                    'type' => 'color',
                    'default' => '#ffffff',
                    'description' => 'Cartes produits, conteneurs',
                ],
                'input_bg' => [
                    'label' => 'Fond des champs',
                    'type' => 'color',
                    'default' => '#ffffff',
                    'description' => 'Inputs, selects, textarea',
                ],
                'header_bg' => [
                    'label' => 'Fond du header',
                    'type' => 'color',
                    'default' => '#ffffff',
                    'description' => 'Barre de navigation',
                ],
                'footer_bg' => [
                    'label' => 'Fond du footer',
                    'type' => 'color',
                    'default' => '#ffffff',
                    'description' => 'Zone du pied de page',
                ],
            ],

            // Textes
            'texts' => [
                'heading_color' => [
                    'label' => 'Titres',
                    'type' => 'color',
                    'default' => '#111827',
                    'description' => 'H1, H2, H3...',
                ],
                'body_color' => [
                    'label' => 'Texte principal',
                    'type' => 'color',
                    'default' => '#374151',
                    'description' => 'Paragraphes, contenu',
                ],
                'muted_color' => [
                    'label' => 'Texte secondaire',
                    'type' => 'color',
                    'default' => '#6b7280',
                    'description' => 'Descriptions, labels',
                ],
                'link_color' => [
                    'label' => 'Liens',
                    'type' => 'color',
                    'default' => '#111827',
                    'description' => 'Liens cliquables',
                ],
            ],

            // Bordures
            'borders' => [
                'border_default' => [
                    'label' => 'Bordure par défaut',
                    'type' => 'color',
                    'default' => '#e5e7eb',
                    'description' => 'Cartes, inputs, dividers',
                ],
                'border_hover' => [
                    'label' => 'Bordure au survol',
                    'type' => 'color',
                    'default' => '#111827',
                    'description' => 'Hover sur éléments interactifs',
                ],
            ],

            // États & Badges
            'states' => [
                'success_color' => [
                    'label' => 'Succès (texte)',
                    'type' => 'color',
                    'default' => '#10b981',
                    'description' => 'Badge "En stock"',
                ],
                'success_bg' => [
                    'label' => 'Succès (fond)',
                    'type' => 'color',
                    'default' => '#d1fae5',
                    'description' => 'Fond badge succès',
                ],
                'error_color' => [
                    'label' => 'Erreur (texte)',
                    'type' => 'color',
                    'default' => '#ef4444',
                    'description' => 'Badge "Indisponible"',
                ],
                'error_bg' => [
                    'label' => 'Erreur (fond)',
                    'type' => 'color',
                    'default' => '#fee2e2',
                    'description' => 'Fond badge erreur',
                ],
                'promo_bg' => [
                    'label' => 'Promo (fond)',
                    'type' => 'color',
                    'default' => '#fbbf24',
                    'description' => 'Badge promotion',
                ],
                'promo_text' => [
                    'label' => 'Promo (texte)',
                    'type' => 'color',
                    'default' => '#92400e',
                    'description' => 'Texte promotion',
                ],
                'variant_badge_bg' => [
                    'label' => 'Badge variante (fond)',
                    'type' => 'color',
                    'default' => '#ffffff',
                    'description' => 'Fond sélecteurs de variantes',
                ],
                'variant_badge_text' => [
                    'label' => 'Badge variante (texte)',
                    'type' => 'color',
                    'default' => '#374151',
                    'description' => 'Texte sélecteurs de variantes',
                ],
                'variant_badge_active_bg' => [
                    'label' => 'Badge variante active (fond)',
                    'type' => 'color',
                    'default' => '#111827',
                    'description' => 'Fond variante sélectionnée',
                ],
                'variant_badge_active_text' => [
                    'label' => 'Badge variante active (texte)',
                    'type' => 'color',
                    'default' => '#ffffff',
                    'description' => 'Texte variante sélectionnée',
                ],
            ],
            'typography' => [
                'heading_font' => [
                    'label' => 'Police des titres',
                    'type' => 'select',
                    'default' => 'Inter',
                    'options' => ['Inter', 'Poppins', 'Montserrat', 'Roboto', 'Open Sans'],
                ],
                'body_font' => [
                    'label' => 'Police du corps',
                    'type' => 'select',
                    'default' => 'Inter',
                    'options' => ['Inter', 'Poppins', 'Montserrat', 'Roboto', 'Open Sans'],
                ],
                'heading_weight' => [
                    'label' => 'Graisse des titres',
                    'type' => 'select',
                    'default' => '700',
                    'options' => ['400' => 'Normal', '500' => 'Medium', '600' => 'Semi-bold', '700' => 'Bold', '800' => 'Extra-bold'],
                ],
                'h1_size' => [
                    'label' => 'Taille H1 (px)',
                    'type' => 'number',
                    'default' => '48',
                    'min' => 32,
                    'max' => 72,
                    'description' => 'Titre principal',
                ],
                'h2_size' => [
                    'label' => 'Taille H2 (px)',
                    'type' => 'number',
                    'default' => '36',
                    'min' => 24,
                    'max' => 56,
                    'description' => 'Titre de section',
                ],
                'h3_size' => [
                    'label' => 'Taille H3 (px)',
                    'type' => 'number',
                    'default' => '28',
                    'min' => 20,
                    'max' => 44,
                    'description' => 'Sous-titre',
                ],
                'h4_size' => [
                    'label' => 'Taille H4 (px)',
                    'type' => 'number',
                    'default' => '22',
                    'min' => 16,
                    'max' => 32,
                    'description' => 'Titre de carte',
                ],
                'h5_size' => [
                    'label' => 'Taille H5 (px)',
                    'type' => 'number',
                    'default' => '18',
                    'min' => 14,
                    'max' => 26,
                    'description' => 'Petit titre',
                ],
                'h6_size' => [
                    'label' => 'Taille H6 (px)',
                    'type' => 'number',
                    'default' => '16',
                    'min' => 12,
                    'max' => 22,
                    'description' => 'Label / légende',
                ],
                'body_size' => [
                    'label' => 'Taille du texte (px)',
                    'type' => 'number',
                    'default' => '16',
                    'min' => 14,
                    'max' => 18,
                ],
            ],
            'layout' => [
                'border_radius' => [
                    'label' => 'Arrondi des bordures',
                    'type' => 'select',
                    'default' => '12px',
                    'options' => ['0px' => 'Aucun', '8px' => 'Petit', '12px' => 'Moyen', '16px' => 'Grand', '24px' => 'Très grand'],
                ],
                'card_style' => [
                    'label' => 'Style des cartes produit',
                    'type' => 'select',
                    'default' => 'bordered',
                    'options' => ['minimal' => 'Minimal', 'bordered' => 'Bordure', 'shadowed' => 'Ombre', 'elevated' => 'Élevé'],
                ],
            ],
            'header' => [
                'header_style' => [
                    'label' => 'Style visuel',
                    'type' => 'select',
                    'default' => 'classic',
                    'options' => ['minimal' => 'Minimal', 'classic' => 'Classique', 'bordered' => 'Avec bordure'],
                    'description' => 'Apparence visuelle du header',
                ],
                'header_sticky' => [
                    'label' => 'Header fixe au scroll',
                    'type' => 'select',
                    'default' => 'yes',
                    'options' => ['yes' => 'Oui', 'no' => 'Non'],
                    'description' => 'Le header reste visible lors du défilement',
                ],
            ],
            'buttons' => [
                'button_style' => [
                    'label' => 'Style des boutons',
                    'type' => 'select',
                    'default' => 'rounded',
                    'options' => ['square' => 'Carré', 'rounded' => 'Arrondi', 'pill' => 'Pilule'],
                ],
                'button_primary_text' => [
                    'label' => 'Texte bouton principal',
                    'type' => 'color',
                    'default' => '#ffffff',
                    'description' => 'Couleur du texte sur fond principal',
                ],
                'button_secondary_text' => [
                    'label' => 'Texte bouton secondaire',
                    'type' => 'color',
                    'default' => '#111827',
                    'description' => 'Couleur du texte bouton outline',
                ],
            ],
            'cart' => [
                'cart_type' => [
                    'label' => 'Type de panier',
                    'type' => 'select',
                    'default' => 'drawer',
                    'options' => ['drawer' => 'Tiroir (drawer)', 'page' => 'Page panier'],
                    'description' => 'Mode d\'affichage du panier lors du clic sur l\'icône',
                ],
            ],
        ];
    }

    /**
     * Get settings schema for a theme (from theme or fallback to default)
     */
    public function getThemeSettingsSchema(Theme $theme): array
    {
        $defaultSchema = $this->getDefaultSettings();
        $themeSchema = $theme->hasSettingsSchema() && is_array($theme->getSettingsSchema())
            ? $theme->getSettingsSchema()
            : [];

        $bundledSchema = $this->loadBundledThemeSchema((string) $theme->slug);
        if ($bundledSchema !== null) {
            // Le fichier JSON livré avec le thème est la source de vérité.
            $themeSchema = $this->mergeSettingsSchema($themeSchema, $bundledSchema);
        }

        if ($themeSchema === []) {
            return $defaultSchema;
        }

        // Merge defaults with theme schema to keep backward compatibility
        // when new settings are introduced after theme creation.
        return $this->mergeSettingsSchema($defaultSchema, $themeSchema);
    }

    private function loadBundledThemeSchema(string $slug): ?array
    {
        $normalizedSlug = trim($slug);
        if ($normalizedSlug === '' || $normalizedSlug !== basename($normalizedSlug)) {
            return null;
        }

        $configPath = storage_path("app/theme-{$normalizedSlug}.json");
        if (! is_file($configPath)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($configPath), true);
        if (! is_array($decoded)) {
            return null;
        }

        $schema = $decoded['settings_schema'] ?? null;

        return is_array($schema) ? $schema : null;
    }

    /**
     * Initialize default settings for a theme
     */
    public function initializeDefaultSettings(Theme $theme): void
    {
        $schema = $this->getThemeSettingsSchema($theme);

        foreach ($schema as $group => $settings) {
            foreach ($settings as $key => $config) {
                ThemeSetting::updateOrCreate(
                    [
                        'theme_id' => $theme->id,
                        'key' => $key,
                    ],
                    [
                        'value' => $config['default'],
                        'type' => $config['type'],
                        'group' => $group,
                    ]
                );
            }
        }
    }

    /**
     * Update multiple settings at once
     */
    public function updateSettings(Theme $theme, array $settings): void
    {
        $theme->loadMissing('settings');
        $existingSettings = $theme->settings->keyBy('key');
        $schemaIndex = $this->buildSettingsSchemaIndex($this->getThemeSettingsSchema($theme));

        foreach ($settings as $key => $value) {
            $setting = $existingSettings->get($key);

            if ($setting) {
                $setting->setEncodedValue($value);
                $setting->save();

                continue;
            }

            $schemaMeta = $schemaIndex[$key] ?? null;
            $newSetting = new ThemeSetting([
                'theme_id' => $theme->id,
                'key' => $key,
                'type' => $schemaMeta['type'] ?? $this->inferSettingType($value),
                'group' => $schemaMeta['group'] ?? 'general',
            ]);
            $newSetting->setEncodedValue($value);
            $newSetting->save();

            $existingSettings->put($key, $newSetting);
        }

        // Clear cache
        $this->clearThemeCache($theme);
    }

    /**
     * Get active theme settings (cached)
     */
    public function getActiveThemeSettings(int $shopId): array
    {
        return Cache::remember("theme_settings_{$shopId}", 3600, function () use ($shopId) {
            $theme = Theme::where('shop_id', $shopId)
                ->where('is_active', true)
                ->with('settings')
                ->first();

            if (! $theme) {
                return $this->getDefaultSettingsValues();
            }

            $schema = $this->getThemeSettingsSchema($theme);
            $defaultValues = $this->getDefaultSettingsValues($schema);
            $currentValues = $theme->getSettingsArray();

            return array_replace_recursive($defaultValues, $currentValues);
        });
    }

    /**
     * Get default settings values only (for fallback)
     */
    private function getDefaultSettingsValues(?array $schema = null): array
    {
        $defaults = $schema ?? $this->getDefaultSettings();
        $values = [];

        foreach ($defaults as $group => $settings) {
            foreach ($settings as $key => $config) {
                $values[$group][$key] = $config['default'];
            }
        }

        return $values;
    }

    /**
     * Merge default schema with theme-specific schema.
     */
    private function mergeSettingsSchema(array $defaultSchema, array $themeSchema): array
    {
        $merged = $defaultSchema;

        foreach ($themeSchema as $group => $settings) {
            if (! is_array($settings)) {
                continue;
            }

            if (! isset($merged[$group]) || ! is_array($merged[$group])) {
                $merged[$group] = $settings;

                continue;
            }

            foreach ($settings as $key => $config) {
                $merged[$group][$key] = $config;
            }
        }

        return $merged;
    }

    /**
     * Flatten schema into an index keyed by setting key.
     *
     * @return array<string, array{group: string, type: string}>
     */
    private function buildSettingsSchemaIndex(array $schema): array
    {
        $index = [];

        foreach ($schema as $group => $settings) {
            if (! is_array($settings)) {
                continue;
            }

            foreach ($settings as $key => $config) {
                $index[$key] = [
                    'group' => $group,
                    'type' => is_array($config) && isset($config['type']) ? (string) $config['type'] : 'text',
                ];
            }
        }

        return $index;
    }

    /**
     * Best-effort type inference when a setting is not found in schema.
     */
    private function inferSettingType(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return 'number';
        }

        if (is_string($value) && preg_match('/^#([A-Fa-f0-9]{3}){1,2}$/', $value)) {
            return 'color';
        }

        return 'text';
    }

    /**
     * Clear theme cache
     */
    public function clearThemeCache(Theme $theme): void
    {
        Cache::forget("theme_settings_{$theme->shop_id}");
    }

    /**
     * Generate CSS variables from theme settings
     */
    public function generateCssVariables(array $settings): string
    {
        $css = ":root {\n";
        $layoutBorderWidth = (string) ($settings['layout']['border_width'] ?? '1px');
        $formsInputHeight = (string) ($settings['forms']['input_height'] ?? 'default');
        $formsInputStyle = (string) ($settings['forms']['input_style'] ?? 'outlined');
        $shadowStyle = (string) ($settings['surfaces']['shadow_style'] ?? 'soft');
        $panelStyle = (string) ($settings['surfaces']['panel_style'] ?? 'bordered');
        $cardPadding = (string) ($settings['surfaces']['card_padding'] ?? 'normal');
        $headerIconButtonStyle = (string) ($settings['header']['icon_button_style'] ?? 'outline');
        $footerDensity = (string) ($settings['footer']['footer_density'] ?? 'normal');
        $checkoutStepperStyle = (string) ($settings['checkout']['stepper_style'] ?? 'line');
        $checkoutSummaryStyle = (string) ($settings['checkout']['summary_style'] ?? 'card');
        $accountCardStyle = (string) ($settings['account']['account_card_style'] ?? 'card');
        $qtyControlStyle = (string) ($settings['cart']['qty_control_style'] ?? 'boxed');
        $productBadgeShape = (string) ($settings['products']['product_badge_shape'] ?? 'rounded');
        $productTitleLines = (string) ($settings['products']['product_title_lines'] ?? '2');
        $productPriceSize = (string) ($settings['products']['product_price_size'] ?? 'md');
        $productQuickAddStyle = (string) ($settings['products']['product_quick_add_style'] ?? 'button');
        $focusRingWidth = (string) ($settings['forms']['focus_ring_width'] ?? '1px');

        // Colors - Couleurs principales
        if (isset($settings['colors'])) {
            foreach ($settings['colors'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Backgrounds - Fonds
        if (isset($settings['backgrounds'])) {
            foreach ($settings['backgrounds'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Texts - Textes
        if (isset($settings['texts'])) {
            foreach ($settings['texts'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Borders - Bordures
        if (isset($settings['borders'])) {
            foreach ($settings['borders'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // States - États & Badges
        if (isset($settings['states'])) {
            foreach ($settings['states'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Typography
        if (isset($settings['typography'])) {
            foreach ($settings['typography'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                if (str_ends_with($key, '_size') && is_numeric($value)) {
                    $css .= "  --theme-{$cssKey}: {$value}px;\n";
                } else {
                    $css .= "  --theme-{$cssKey}: {$value};\n";
                }
            }
        }

        // Layout
        if (isset($settings['layout'])) {
            foreach ($settings['layout'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Buttons
        if (isset($settings['buttons'])) {
            if (isset($settings['buttons']['button_style'])) {
                $buttonStyle = $settings['buttons']['button_style'];
                $buttonRadius = match ($buttonStyle) {
                    'square' => '0px',
                    'rounded' => '8px',
                    'pill' => '9999px',
                    default => '8px',
                };
                $css .= "  --theme-button-radius: {$buttonRadius};\n";
                $css .= "  --theme-button-style: {$buttonStyle};\n";
            }

            // Button text colors
            if (isset($settings['buttons']['button_primary_text'])) {
                $css .= "  --theme-button-primary-text: {$settings['buttons']['button_primary_text']};\n";
            }
            if (isset($settings['buttons']['button_secondary_text'])) {
                $css .= "  --theme-button-secondary-text: {$settings['buttons']['button_secondary_text']};\n";
            }
        }

        // Header
        if (isset($settings['header'])) {
            foreach ($settings['header'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Cart
        if (isset($settings['cart'])) {
            foreach ($settings['cart'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Forms
        if (isset($settings['forms'])) {
            foreach ($settings['forms'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Surfaces
        if (isset($settings['surfaces'])) {
            foreach ($settings['surfaces'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Footer
        if (isset($settings['footer'])) {
            foreach ($settings['footer'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Checkout
        if (isset($settings['checkout'])) {
            foreach ($settings['checkout'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Account
        if (isset($settings['account'])) {
            foreach ($settings['account'] as $key => $value) {
                $cssKey = str_replace('_', '-', $key);
                $css .= "  --theme-{$cssKey}: {$value};\n";
            }
        }

        // Products - Product card settings
        if (isset($settings['products'])) {
            // Product card style
            if (isset($settings['products']['product_card_style'])) {
                $css .= "  --theme-product-card-style: {$settings['products']['product_card_style']};\n";
            }

            // Product hover effect
            if (isset($settings['products']['product_hover_effect'])) {
                $css .= "  --theme-product-hover-effect: {$settings['products']['product_hover_effect']};\n";
            }

            // Product badges visibility
            if (isset($settings['products']['show_product_badges'])) {
                $showBadges = $settings['products']['show_product_badges'] === 'yes' ? 'block' : 'none';
                $css .= "  --theme-product-badges-display: {$showBadges};\n";
            }

            // Quick add button visibility
            if (isset($settings['products']['show_quick_add'])) {
                $showQuickAdd = $settings['products']['show_quick_add'] === 'yes' ? 'block' : 'none';
                $css .= "  --theme-product-quick-add-display: {$showQuickAdd};\n";
            }

            // Product image ratio
            if (isset($settings['products']['product_image_ratio'])) {
                $ratio = match ($settings['products']['product_image_ratio']) {
                    'square' => '100%',
                    'portrait' => '133.33%',
                    'landscape' => '75%',
                    default => '100%',
                };
                $css .= "  --theme-product-image-ratio: {$ratio};\n";
            }

            // Products per row
            if (isset($settings['products']['products_per_row_desktop'])) {
                $css .= "  --theme-products-per-row-desktop: {$settings['products']['products_per_row_desktop']};\n";
            }
            if (isset($settings['products']['products_per_row_tablet'])) {
                $css .= "  --theme-products-per-row-tablet: {$settings['products']['products_per_row_tablet']};\n";
            }
            if (isset($settings['products']['products_per_row_mobile'])) {
                $css .= "  --theme-products-per-row-mobile: {$settings['products']['products_per_row_mobile']};\n";
            }
        }

        if (is_numeric($layoutBorderWidth)) {
            $layoutBorderWidth .= 'px';
        }
        $css .= "  --theme-border-width: {$layoutBorderWidth};\n";

        if (is_numeric($focusRingWidth)) {
            $focusRingWidth .= 'px';
        }
        $css .= "  --theme-focus-ring-width: {$focusRingWidth};\n";

        $inputHeightPx = match ($formsInputHeight) {
            'compact' => '34px',
            'comfortable' => '44px',
            default => '38px',
        };
        $css .= "  --theme-input-height-px: {$inputHeightPx};\n";

        $inputSurface = match ($formsInputStyle) {
            'filled' => 'var(--theme-page-bg, #f6f6f7)',
            'minimal' => 'transparent',
            default => 'var(--theme-input-bg, #ffffff)',
        };
        $inputBorderColor = match ($formsInputStyle) {
            'filled' => 'transparent',
            default => 'var(--theme-border-default, #e5e7eb)',
        };
        $css .= "  --theme-input-surface: {$inputSurface};\n";
        $css .= "  --theme-input-border-color: {$inputBorderColor};\n";

        $shadowMap = match ($shadowStyle) {
            'none' => [
                'sm' => 'none',
                'md' => 'none',
                'lg' => 'none',
                'xl' => 'none',
            ],
            'hard' => [
                'sm' => '0 2px 0 rgba(17, 24, 39, 0.25)',
                'md' => '0 6px 0 rgba(17, 24, 39, 0.28)',
                'lg' => '0 10px 0 rgba(17, 24, 39, 0.30)',
                'xl' => '0 14px 0 rgba(17, 24, 39, 0.34)',
            ],
            default => [
                'sm' => '0 1px 2px rgba(17, 24, 39, 0.08)',
                'md' => '0 8px 18px rgba(17, 24, 39, 0.10)',
                'lg' => '0 14px 30px rgba(17, 24, 39, 0.14)',
                'xl' => '0 20px 42px rgba(17, 24, 39, 0.18)',
            ],
        };
        $css .= "  --theme-shadow-sm: {$shadowMap['sm']};\n";
        $css .= "  --theme-shadow-md: {$shadowMap['md']};\n";
        $css .= "  --theme-shadow-lg: {$shadowMap['lg']};\n";
        $css .= "  --theme-shadow-xl: {$shadowMap['xl']};\n";

        $panelPadding = match ($cardPadding) {
            'compact' => '0.75rem',
            'large' => '1.5rem',
            default => '1rem',
        };
        $css .= "  --theme-panel-padding: {$panelPadding};\n";

        $panelBackground = 'var(--theme-card-bg, #ffffff)';
        $panelBorderColor = 'var(--theme-border-default, #e5e7eb)';
        $panelShadow = 'var(--theme-shadow-sm)';
        if ($panelStyle === 'flat') {
            $panelBackground = 'var(--theme-page-bg, #f6f6f7)';
            $panelBorderColor = 'transparent';
            $panelShadow = 'none';
        } elseif ($panelStyle === 'elevated') {
            $panelBorderColor = 'transparent';
            $panelShadow = 'var(--theme-shadow-lg)';
        }
        $css .= "  --theme-panel-background: {$panelBackground};\n";
        $css .= "  --theme-panel-border-color: {$panelBorderColor};\n";
        $css .= "  --theme-panel-shadow: {$panelShadow};\n";

        $headerControlBg = 'var(--theme-card-bg, #ffffff)';
        $headerControlBorder = 'var(--theme-border-default, #e5e7eb)';
        $headerControlText = 'var(--theme-heading-color, #111827)';
        if ($headerIconButtonStyle === 'ghost') {
            $headerControlBg = 'transparent';
            $headerControlBorder = 'transparent';
            $headerControlText = 'var(--theme-body-color, #374151)';
        } elseif ($headerIconButtonStyle === 'solid') {
            $headerControlBg = 'var(--theme-primary, #111827)';
            $headerControlBorder = 'var(--theme-primary, #111827)';
            $headerControlText = 'var(--theme-button-primary-text, #ffffff)';
        }
        $css .= "  --theme-header-control-bg: {$headerControlBg};\n";
        $css .= "  --theme-header-control-border: {$headerControlBorder};\n";
        $css .= "  --theme-header-control-text: {$headerControlText};\n";

        $footerPadding = match ($footerDensity) {
            'compact' => '0.875rem',
            'comfortable' => '2.25rem',
            default => '1.5rem',
        };
        $css .= "  --theme-footer-padding-y: {$footerPadding};\n";

        $stepperRadius = match ($checkoutStepperStyle) {
            'minimal' => '8px',
            default => '9999px',
        };
        $stepperPanelBg = $checkoutStepperStyle === 'minimal'
            ? 'transparent'
            : 'var(--theme-card-bg, #ffffff)';
        $stepperPanelBorder = $checkoutStepperStyle === 'minimal'
            ? 'transparent'
            : 'var(--theme-border-default, #e5e7eb)';
        $stepperPanelShadow = $checkoutStepperStyle === 'pills'
            ? 'var(--theme-shadow-sm)'
            : 'none';
        $css .= "  --theme-checkout-stepper-node-radius: {$stepperRadius};\n";
        $css .= "  --theme-checkout-stepper-panel-bg: {$stepperPanelBg};\n";
        $css .= "  --theme-checkout-stepper-panel-border: {$stepperPanelBorder};\n";
        $css .= "  --theme-checkout-stepper-panel-shadow: {$stepperPanelShadow};\n";

        $summaryBackground = $checkoutSummaryStyle === 'flat'
            ? 'var(--theme-page-bg, #f6f6f7)'
            : 'var(--theme-card-bg, #ffffff)';
        $summaryBorderColor = $checkoutSummaryStyle === 'flat'
            ? 'transparent'
            : 'var(--theme-border-default, #e5e7eb)';
        $summaryShadow = $checkoutSummaryStyle === 'flat'
            ? 'none'
            : 'var(--theme-shadow-sm)';
        $css .= "  --theme-checkout-summary-bg: {$summaryBackground};\n";
        $css .= "  --theme-checkout-summary-border: {$summaryBorderColor};\n";
        $css .= "  --theme-checkout-summary-shadow: {$summaryShadow};\n";

        $accountBackground = $accountCardStyle === 'flat'
            ? 'var(--theme-page-bg, #f6f6f7)'
            : 'var(--theme-card-bg, #ffffff)';
        $accountBorder = $accountCardStyle === 'flat'
            ? 'transparent'
            : 'var(--theme-border-default, #e5e7eb)';
        $accountShadow = $accountCardStyle === 'card'
            ? 'var(--theme-shadow-sm)'
            : 'none';
        if ($accountCardStyle === 'outlined') {
            $accountBorder = 'var(--theme-border-hover, #111827)';
        }
        $css .= "  --theme-account-card-bg: {$accountBackground};\n";
        $css .= "  --theme-account-card-border: {$accountBorder};\n";
        $css .= "  --theme-account-card-shadow: {$accountShadow};\n";

        $qtyBackground = $qtyControlStyle === 'minimal'
            ? 'transparent'
            : 'var(--theme-card-bg, #ffffff)';
        $qtyBorder = $qtyControlStyle === 'minimal'
            ? 'transparent'
            : 'var(--theme-border-default, #e5e7eb)';
        $qtyPaddingX = $qtyControlStyle === 'minimal' ? '0rem' : '0.5rem';
        $css .= "  --theme-qty-bg: {$qtyBackground};\n";
        $css .= "  --theme-qty-border: {$qtyBorder};\n";
        $css .= "  --theme-qty-padding-x: {$qtyPaddingX};\n";

        $badgeRadius = match ($productBadgeShape) {
            'square' => '0px',
            'pill' => '9999px',
            default => 'var(--theme-border-radius, 12px)',
        };
        $css .= "  --theme-product-badge-radius: {$badgeRadius};\n";

        $titleLines = in_array($productTitleLines, ['1', '2'], true) ? $productTitleLines : '2';
        $css .= "  --theme-product-title-lines: {$titleLines};\n";

        $priceFontSize = match ($productPriceSize) {
            'sm' => '0.8125rem',
            'lg' => '1.0625rem',
            default => '0.9375rem',
        };
        $css .= "  --theme-product-price-size: {$priceFontSize};\n";
        $css .= "  --theme-product-quick-add-style: {$productQuickAddStyle};\n";

        $css .= "}\n";

        return $css;
    }
}
