<?php

declare(strict_types=1);

namespace Omersia\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleHook extends Model
{
    protected $fillable = [
        'module_slug',
        'hook_name',
        'component_path',
        'condition',
        'priority',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Relation avec le module
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_slug', 'slug');
    }

    /**
     * Scope pour récupérer les hooks actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour récupérer les hooks d'un module
     */
    public function scopeForModule($query, string $moduleSlug)
    {
        return $query->where('module_slug', $moduleSlug);
    }

    /**
     * Scope pour récupérer les hooks d'une position
     */
    public function scopeForHook($query, string $hookName)
    {
        return $query->where('hook_name', $hookName);
    }

    /**
     * Obtenir tous les hooks groupés par position
     */
    public static function getGroupedByPosition(): array
    {
        $hooks = self::with('module')
            ->orderBy('hook_name')
            ->orderBy('priority')
            ->get();

        $grouped = [];
        foreach ($hooks as $hook) {
            $grouped[$hook->hook_name][] = $hook;
        }

        return $grouped;
    }

    /**
     * Noms lisibles des positions de hooks
     */
    public static function getHookLabels(): array
    {
        return [
            // Checkout
            'checkout.shipping.after-methods' => 'Checkout - Après méthodes de livraison',
            'checkout.payment.methods' => 'Checkout - Méthodes de paiement',
            'checkout.summary.extras' => 'Checkout - Récapitulatif extras',
            'checkout.address.validators' => 'Checkout - Validation d\'adresse',

            // Product
            'product.detail.upsell' => 'Produit - Cross-selling / Upsell',
            'product.detail.after-description' => 'Produit - Après description',
            'product.detail.badges' => 'Produit - Badges personnalisés',
            'product.detail.actions' => 'Produit - Actions personnalisées',

            // Cart - Sidebar
            'cart.sidebar.recommendations' => 'Panier Sidebar - Recommandations',

            // Cart - Drawer
            'cart.drawer.recommendations' => 'Panier Drawer - Recommandations',
            'cart.drawer.after_items' => 'Panier Drawer - Après les articles',
            'cart.drawer.before_items' => 'Panier Drawer - Avant les articles',
            'cart.drawer.footer' => 'Panier Drawer - Footer',

            // Cart - Modal
            'cart.modal.after_product' => 'Panier Modal - Après le produit',
            'cart.modal.before_product' => 'Panier Modal - Avant le produit',
            'cart.modal.footer' => 'Panier Modal - Footer',

            // Cart - Page
            'cart.page.recommendations' => 'Panier Page - Recommandations',
            'cart.page.after_items' => 'Panier Page - Après les articles',
            'cart.page.before_items' => 'Panier Page - Avant les articles',
            'cart.page.footer' => 'Panier Page - Footer',
            'cart.page.header' => 'Panier Page - En-tête',

            // Cart - General
            'cart.items.extras' => 'Panier - Extras par article',
            'cart.footer.actions' => 'Panier - Actions footer',

            // Global
            'header.top' => 'Header - Bannière en haut',
            'header.navigation.extra' => 'Header - Navigation extra',
            'footer.content.extra' => 'Footer - Contenu extra',
            'sidebar.widgets' => 'Sidebar - Widgets',
        ];
    }

    /**
     * Obtenir le label lisible d'un hook
     */
    public function getHookLabel(): string
    {
        $labels = self::getHookLabels();

        return $labels[$this->hook_name] ?? $this->hook_name;
    }
}
