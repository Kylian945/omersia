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
        // Use theme-specific schema if available
        if ($theme->hasSettingsSchema()) {
            return $theme->getSettingsSchema();
        }

        // Fallback to default settings
        return $this->getDefaultSettings();
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
        foreach ($settings as $key => $value) {
            $setting = $theme->settings()->where('key', $key)->first();

            if ($setting) {
                $setting->setEncodedValue($value);
                $setting->save();
            }
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

            return $theme->getSettingsArray();
        });
    }

    /**
     * Get default settings values only (for fallback)
     */
    private function getDefaultSettingsValues(): array
    {
        $defaults = $this->getDefaultSettings();
        $values = [];

        foreach ($defaults as $group => $settings) {
            foreach ($settings as $key => $config) {
                $values[$group][$key] = $config['default'];
            }
        }

        return $values;
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

        $css .= "}\n";

        return $css;
    }
}
