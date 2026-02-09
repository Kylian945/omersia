import "./bootstrap";

import Alpine from "alpinejs";

window.Alpine = Alpine;

// Importer les composants globaux (toujours chargés)
import '../../packages/Admin/src/resources/js/products.js';
import '../../packages/Admin/src/resources/js/page-builder.js';
import '../../packages/Admin/src/resources/js/page-builder-native.js';

// Fonction d'initialisation conditionnelle des composants
async function loadConditionalComponents() {
    const promises = [];

    // Charger upload-form uniquement si utilisé
    if (document.querySelector('[x-data*="uploadForm"]')) {
        promises.push(import('../../packages/Admin/src/resources/js/modules/upload-form.js'));
    }

    // Charger media-library uniquement si utilisé
    if (document.querySelector('[x-data*="mediaLibrary"]')) {
        promises.push(import('../../packages/Admin/src/resources/js/media/media-library.js'));
    }

    // Charger modules-positions uniquement si utilisé
    if (document.querySelector('[x-data*="modulesPositions"]') || document.querySelector('[x-data*="modulePosition"]')) {
        promises.push(import('../../packages/Admin/src/resources/js/modules-positions.js'));
    }

    // Charger theme-activation uniquement sur la page des thèmes
    if (document.querySelector('[x-data*="themeActivation"]')) {
        promises.push(import('../../packages/Admin/src/resources/js/theme-activation.js'));
    }

    // Charger api-key uniquement sur la page des clés API
    if (document.querySelector('[x-data*="apiKey"]')) {
        promises.push(import('../../packages/Admin/src/resources/js/api-key.js'));
    }

    // Charger modal-manager + permissions uniquement sur la page des permissions
    if (window.location.pathname.includes('/settings/permissions')) {
        promises.push(
            (async () => {
                await import('../../packages/Admin/src/resources/js/settings/modal-manager.js');
                await import('../../packages/Admin/src/resources/js/settings/permissions.js');
            })()
        );
    }

    // Charger modal-manager + roles uniquement sur la page des rôles
    if (window.location.pathname.includes('/settings/roles')) {
        promises.push(
            (async () => {
                await import('../../packages/Admin/src/resources/js/settings/modal-manager.js');
                await import('../../packages/Admin/src/resources/js/settings/roles.js');
            })()
        );
    }

    // Charger modal-manager + users uniquement sur la page des utilisateurs
    if (window.location.pathname.includes('/settings/users')) {
        promises.push(
            (async () => {
                await import('../../packages/Admin/src/resources/js/settings/modal-manager.js');
                await import('../../packages/Admin/src/resources/js/settings/users.js');
            })()
        );
    }

    // Charger ecommerce-pages-form uniquement sur les pages e-commerce
    if (document.querySelector('[x-data*="ecommercePageForm"]') || window.location.pathname.includes('/apparence/ecommerce-pages')) {
        promises.push(import('../../packages/Admin/src/resources/js/apparence/ecommerce-pages-form.js'));
    }

    // Charger stripe-config uniquement sur la page de configuration Stripe
    if (document.querySelector('[x-data*="stripeConfig"]') || window.location.pathname.includes('/settings/payments/stripe')) {
        promises.push(import('../../packages/Admin/src/resources/js/settings/stripe-config.js'));
    }

    // Charger tax-rate-form uniquement sur les pages de taxes
    if (document.querySelector('[x-data*="taxRateForm"]') || window.location.pathname.includes('/settings/taxes')) {
        promises.push(import('../../packages/Admin/src/resources/js/settings/tax-rate-form.js'));
    }

    // Charger shipping-methods uniquement sur les pages de méthodes de livraison
    if (document.querySelector('[x-data*="shippingMethod"]') || window.location.pathname.includes('/settings/shipping-methods')) {
        promises.push(import('../../packages/Admin/src/resources/js/shipping-methods.js'));
    }

    // Attendre que tous les imports soient terminés
    await Promise.all(promises);
}

// Démarrer Alpine après le chargement des composants conditionnels
document.addEventListener('DOMContentLoaded', async () => {
    await loadConditionalComponents();
    Alpine.start();
});
