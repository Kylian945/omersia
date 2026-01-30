<?php

declare(strict_types=1);

namespace Omersia\Apparence\Services;

class DefaultPageConfigService
{
    /**
     * Get default configuration for home page
     */
    public function getHomePageConfig(): array
    {
        return [
            'sections' => [
                // Hero Banner Section
                [
                    'id' => 'section-hero',
                    'settings' => [
                        'background' => '#ffffff',
                        'paddingTop' => 0,
                        'paddingBottom' => 0,
                        'fullWidth' => true,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-hero',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-hero',
                                    'type' => 'hero_banner',
                                    'props' => [
                                        'badge' => 'Nouvelle collection',
                                        'title' => 'Découvrez notre sélection',
                                        'subtitle' => 'Les meilleurs produits pour vous',
                                        'description' => 'Explorez notre collection exclusive de produits soigneusement sélectionnés pour vous offrir la meilleure expérience.',
                                        'primaryCta' => [
                                            'text' => 'Voir les produits',
                                            'href' => '/products',
                                        ],
                                        'secondaryCta' => [
                                            'text' => 'En savoir plus',
                                            'href' => '/about',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Features Bar Section
                [
                    'id' => 'section-features',
                    'settings' => [
                        'background' => '#f9fafb',
                        'paddingTop' => 40,
                        'paddingBottom' => 40,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-features',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-features',
                                    'type' => 'features_bar',
                                    'props' => [
                                        'items' => [
                                            [
                                                'icon' => 'truck',
                                                'title' => 'Livraison gratuite',
                                                'description' => 'Sur toutes les commandes de plus de 50€',
                                            ],
                                            [
                                                'icon' => 'shield-check',
                                                'title' => 'Paiement sécurisé',
                                                'description' => 'Transactions 100% sécurisées',
                                            ],
                                            [
                                                'icon' => 'refresh',
                                                'title' => 'Retours gratuits',
                                                'description' => 'Sous 30 jours sans questions',
                                            ],
                                            [
                                                'icon' => 'headset',
                                                'title' => 'Support 24/7',
                                                'description' => 'Service client toujours disponible',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Categories Grid Section
                [
                    'id' => 'section-categories',
                    'settings' => [
                        'background' => '#ffffff',
                        'paddingTop' => 60,
                        'paddingBottom' => 60,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-categories-heading',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-categories-title',
                                    'type' => 'heading',
                                    'props' => [
                                        'tag' => 'h2',
                                        'text' => 'Nos catégories',
                                    ],
                                ],
                                [
                                    'id' => 'widget-categories-desc',
                                    'type' => 'text',
                                    'props' => [
                                        'html' => '<p>Explorez notre large gamme de produits organisés par catégorie</p>',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'col-categories-grid',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-categories-grid',
                                    'type' => 'categories_grid',
                                    'props' => [
                                        'columns' => 3,
                                        'itemsPerRow' => 3,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Product Slider Section
                [
                    'id' => 'section-featured-products',
                    'settings' => [
                        'background' => '#f9fafb',
                        'paddingTop' => 60,
                        'paddingBottom' => 60,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-products-heading',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-products-title',
                                    'type' => 'heading',
                                    'props' => [
                                        'tag' => 'h2',
                                        'text' => 'Produits populaires',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'col-products-slider',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-product-slider',
                                    'type' => 'product_slider',
                                    'props' => [
                                        'title' => 'Les plus vendus',
                                        'mode' => 'category',
                                        'limit' => 8,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Promo Banner Section
                [
                    'id' => 'section-promo',
                    'settings' => [
                        'background' => '#ffffff',
                        'paddingTop' => 60,
                        'paddingBottom' => 60,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-promo',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-promo',
                                    'type' => 'promo_banner',
                                    'props' => [
                                        'badge' => 'Offre limitée',
                                        'title' => '-20% sur votre première commande',
                                        'description' => 'Inscrivez-vous à notre newsletter et bénéficiez d\'une réduction immédiate',
                                        'ctaText' => 'J\'en profite',
                                        'ctaHref' => '/register',
                                        'variant' => 'gradient',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Testimonials Section
                [
                    'id' => 'section-testimonials',
                    'settings' => [
                        'background' => '#f9fafb',
                        'paddingTop' => 60,
                        'paddingBottom' => 60,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-testimonials-heading',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-testimonials-title',
                                    'type' => 'heading',
                                    'props' => [
                                        'tag' => 'h2',
                                        'text' => 'Ce que nos clients disent',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'col-testimonials-content',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-testimonials',
                                    'type' => 'testimonials',
                                    'props' => [
                                        'testimonials' => [
                                            [
                                                'name' => 'Marie Dupont',
                                                'role' => 'Cliente satisfaite',
                                                'content' => 'Excellente qualité et service impeccable. Je recommande vivement !',
                                                'rating' => 5,
                                            ],
                                            [
                                                'name' => 'Jean Martin',
                                                'role' => 'Client régulier',
                                                'content' => 'Livraison rapide et produits conformes à mes attentes.',
                                                'rating' => 5,
                                            ],
                                            [
                                                'name' => 'Sophie Bernard',
                                                'role' => 'Nouvelle cliente',
                                                'content' => 'Une très belle découverte, je reviendrai c\'est sûr !',
                                                'rating' => 5,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Newsletter Section
                [
                    'id' => 'section-newsletter',
                    'settings' => [
                        'background' => '#ffffff',
                        'paddingTop' => 60,
                        'paddingBottom' => 60,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-newsletter',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-newsletter',
                                    'type' => 'newsletter',
                                    'props' => [
                                        'title' => 'Restez informé',
                                        'description' => 'Inscrivez-vous à notre newsletter pour recevoir nos dernières offres et nouveautés',
                                        'placeholder' => 'Votre adresse email',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get default configuration for products listing page
     */
    public function getProductsPageConfig(): array
    {
        return [
            'sections' => [
                // Page Header
                [
                    'id' => 'section-products-header',
                    'settings' => [
                        'background' => '#ffffff',
                        'paddingTop' => 40,
                        'paddingBottom' => 40,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-products-header',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-products-title',
                                    'type' => 'heading',
                                    'props' => [
                                        'tag' => 'h1',
                                        'text' => 'Tous nos produits',
                                    ],
                                ],
                                [
                                    'id' => 'widget-products-desc',
                                    'type' => 'text',
                                    'props' => [
                                        'html' => '<p>Découvrez notre collection complète de produits soigneusement sélectionnés pour vous</p>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Features Bar
                [
                    'id' => 'section-products-features',
                    'settings' => [
                        'background' => '#f9fafb',
                        'paddingTop' => 30,
                        'paddingBottom' => 30,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-products-features',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-products-features',
                                    'type' => 'features_bar',
                                    'props' => [
                                        'items' => [
                                            [
                                                'icon' => 'truck',
                                                'title' => 'Livraison gratuite',
                                                'description' => 'Dès 50€ d\'achat',
                                            ],
                                            [
                                                'icon' => 'shield-check',
                                                'title' => 'Garantie qualité',
                                                'description' => 'Produits certifiés',
                                            ],
                                            [
                                                'icon' => 'refresh',
                                                'title' => 'Retours faciles',
                                                'description' => 'Sous 30 jours',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Note: The actual product grid will be handled by the frontend
                // with filters, pagination, etc.

                // Bottom CTA
                [
                    'id' => 'section-products-cta',
                    'settings' => [
                        'background' => '#ffffff',
                        'paddingTop' => 60,
                        'paddingBottom' => 60,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-products-cta',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-products-cta-title',
                                    'type' => 'heading',
                                    'props' => [
                                        'tag' => 'h2',
                                        'text' => 'Vous ne trouvez pas ce que vous cherchez ?',
                                    ],
                                ],
                                [
                                    'id' => 'widget-products-cta-text',
                                    'type' => 'text',
                                    'props' => [
                                        'html' => '<p>Contactez notre équipe, nous serons ravis de vous aider à trouver le produit parfait</p>',
                                    ],
                                ],
                                [
                                    'id' => 'widget-products-cta-button',
                                    'type' => 'button',
                                    'props' => [
                                        'label' => 'Nous contacter',
                                        'url' => '/contact',
                                        'style' => 'primary',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get default configuration for category page
     */
    public function getCategoryPageConfig(): array
    {
        return [
            'sections' => [
                // Category Header
                [
                    'id' => 'section-category-header',
                    'settings' => [
                        'background' => '#ffffff',
                        'paddingTop' => 40,
                        'paddingBottom' => 40,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-category-header',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-category-title',
                                    'type' => 'heading',
                                    'props' => [
                                        'tag' => 'h1',
                                        'text' => 'Catégorie', // Will be replaced dynamically
                                    ],
                                ],
                                [
                                    'id' => 'widget-category-desc',
                                    'type' => 'text',
                                    'props' => [
                                        'html' => '<p>Explorez tous les produits de cette catégorie</p>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Subcategories (if any)
                [
                    'id' => 'section-subcategories',
                    'settings' => [
                        'background' => '#f9fafb',
                        'paddingTop' => 40,
                        'paddingBottom' => 40,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-subcategories',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-subcategories-title',
                                    'type' => 'heading',
                                    'props' => [
                                        'tag' => 'h2',
                                        'text' => 'Sous-catégories',
                                    ],
                                ],
                                [
                                    'id' => 'widget-subcategories-grid',
                                    'type' => 'categories_grid',
                                    'props' => [
                                        'columns' => 4,
                                        'itemsPerRow' => 4,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Featured Products from Category
                [
                    'id' => 'section-category-featured',
                    'settings' => [
                        'background' => '#ffffff',
                        'paddingTop' => 60,
                        'paddingBottom' => 60,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-category-featured-heading',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-category-featured-title',
                                    'type' => 'heading',
                                    'props' => [
                                        'tag' => 'h2',
                                        'text' => 'Produits mis en avant',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'col-category-featured-slider',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-category-product-slider',
                                    'type' => 'product_slider',
                                    'props' => [
                                        'title' => '',
                                        'mode' => 'category',
                                        'limit' => 8,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Note: The main product grid will be handled by the frontend

                // Category Description/Info
                [
                    'id' => 'section-category-info',
                    'settings' => [
                        'background' => '#f9fafb',
                        'paddingTop' => 60,
                        'paddingBottom' => 60,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-category-info-1',
                            'width' => 50,
                            'widgets' => [
                                [
                                    'id' => 'widget-category-info-title',
                                    'type' => 'heading',
                                    'props' => [
                                        'tag' => 'h2',
                                        'text' => 'À propos de cette catégorie',
                                    ],
                                ],
                                [
                                    'id' => 'widget-category-info-text',
                                    'type' => 'text',
                                    'props' => [
                                        'html' => '<p>Cette catégorie regroupe une sélection de produits de qualité, soigneusement choisis pour répondre à vos besoins. Découvrez notre gamme complète et profitez de nos meilleurs prix.</p>',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'col-category-info-2',
                            'width' => 50,
                            'widgets' => [
                                [
                                    'id' => 'widget-category-accordion',
                                    'type' => 'accordion',
                                    'props' => [
                                        'items' => [
                                            [
                                                'title' => 'Livraison et retours',
                                                'content' => 'Livraison gratuite dès 50€ d\'achat. Retours gratuits sous 30 jours.',
                                            ],
                                            [
                                                'title' => 'Garantie',
                                                'content' => 'Tous nos produits sont garantis et certifiés conformes aux normes en vigueur.',
                                            ],
                                            [
                                                'title' => 'Service client',
                                                'content' => 'Notre équipe est disponible 24/7 pour répondre à toutes vos questions.',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // Related Categories
                [
                    'id' => 'section-related-categories',
                    'settings' => [
                        'background' => '#ffffff',
                        'paddingTop' => 60,
                        'paddingBottom' => 60,
                        'fullWidth' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'col-related-categories-heading',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-related-categories-title',
                                    'type' => 'heading',
                                    'props' => [
                                        'tag' => 'h2',
                                        'text' => 'Catégories similaires',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'col-related-categories-grid',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-related-categories-grid',
                                    'type' => 'categories_grid',
                                    'props' => [
                                        'columns' => 3,
                                        'itemsPerRow' => 3,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get all default configurations
     */
    public function getAllConfigs(): array
    {
        return [
            'home' => $this->getHomePageConfig(),
            'product' => $this->getProductsPageConfig(),
            'category' => $this->getCategoryPageConfig(),
        ];
    }
}
