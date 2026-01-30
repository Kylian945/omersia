<?php

declare(strict_types=1);

namespace Omersia\Admin\Config;

class BuilderWidgets
{
    /**
     * Get all available builder widgets
     */
    public static function all(): array
    {
        return [
            // Basic Content Widgets
            ...self::basicWidgets(),

            // E-commerce Widgets
            ...self::ecommerceWidgets(),
        ];
    }

    /**
     * Get basic content widgets
     */
    public static function basicWidgets(): array
    {
        return [
            [
                'type' => 'heading',
                'label' => 'Titre',
                'icon' => 'type',
                'category' => 'content',
            ],
            [
                'type' => 'text',
                'label' => 'Texte',
                'icon' => 'align-left',
                'category' => 'content',
            ],
            [
                'type' => 'image',
                'label' => 'Image',
                'icon' => 'image',
                'category' => 'content',
            ],
            [
                'type' => 'video',
                'label' => 'Vidéo',
                'icon' => 'video',
                'category' => 'content',
            ],
            [
                'type' => 'button',
                'label' => 'Bouton',
                'icon' => 'square-arrow-out-up-right',
                'category' => 'content',
            ],
            [
                'type' => 'accordion',
                'label' => 'Accordéon',
                'icon' => 'list',
                'category' => 'content',
            ],
            [
                'type' => 'tabs',
                'label' => 'Onglets',
                'icon' => 'columns-3',
                'category' => 'content',
            ],
            [
                'type' => 'spacer',
                'label' => 'Espacement',
                'icon' => 'move-vertical',
                'category' => 'layout',
            ],
            [
                'type' => 'container',
                'label' => 'Container',
                'icon' => 'box',
                'category' => 'layout',
            ],
        ];
    }

    /**
     * Get e-commerce widgets
     */
    public static function ecommerceWidgets(): array
    {
        return [
            [
                'type' => 'hero_banner',
                'label' => 'Hero Banner',
                'icon' => 'megaphone',
                'category' => 'ecommerce',
            ],
            [
                'type' => 'features_bar',
                'label' => 'Barre Features',
                'icon' => 'sparkles',
                'category' => 'ecommerce',
            ],
            [
                'type' => 'categories_grid',
                'label' => 'Grille Catégories',
                'icon' => 'grid-3x3',
                'category' => 'ecommerce',
            ],
            [
                'type' => 'promo_banner',
                'label' => 'Bannière Promo',
                'icon' => 'badge-percent',
                'category' => 'ecommerce',
            ],
            [
                'type' => 'testimonials',
                'label' => 'Témoignages',
                'icon' => 'message-circle',
                'category' => 'ecommerce',
            ],
            [
                'type' => 'newsletter',
                'label' => 'Newsletter',
                'icon' => 'mail',
                'category' => 'ecommerce',
            ],
            [
                'type' => 'product_slider',
                'label' => 'Produits (Slider/Grille)',
                'icon' => 'shopping-cart',
                'category' => 'ecommerce',
            ],
        ];
    }

    /**
     * Get widgets by category
     */
    public static function byCategory(string $category): array
    {
        return array_filter(self::all(), function ($widget) use ($category) {
            return $widget['category'] === $category;
        });
    }

    /**
     * Get widget categories grouped
     */
    public static function grouped(): array
    {
        $widgets = self::all();
        $grouped = [];

        foreach ($widgets as $widget) {
            $category = $widget['category'];
            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $widget;
        }

        return $grouped;
    }

    /**
     * Get category labels
     */
    public static function categoryLabels(): array
    {
        return [
            'content' => 'Contenu',
            'layout' => 'Mise en page',
            'ecommerce' => 'E-commerce',
        ];
    }
}
