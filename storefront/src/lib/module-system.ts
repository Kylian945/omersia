import { ComponentType } from "react";
import { logger } from "./logger";

/**
 * Universal Module Hook System for Omersia
 *
 * Permet à plusieurs modules (shipping, payment, upsell, etc.) d'injecter
 * du contenu à des emplacements spécifiques dans l'application.
 *
 * Architecture:
 * - Les modules déclarent leurs hooks dans module-config.json
 * - Le système charge dynamiquement les composants des modules
 * - Plusieurs modules peuvent s'injecter au même hook
 * - Les conditions déterminent quand afficher chaque module
 *
 * Exemple de hooks disponibles:
 * - checkout.shipping.after-methods (sélecteurs de point relais)
 * - checkout.payment.methods (méthodes de paiement personnalisées)
 * - product.detail.upsell (cross-selling, bundles)
 * - cart.sidebar.recommendations (recommandations panier)
 * - header.navigation.extra (liens navigation personnalisés)
 */

export interface Address {
  firstName?: string;
  lastName?: string;
  address1?: string;
  address2?: string;
  city?: string;
  postalCode?: string;
  country?: string;
  phone?: string;
}

export interface CartItem {
  id: number;
  productId?: number; // Make optional for compatibility
  quantity?: number; // Make optional for compatibility
  qty?: number; // Alternative field name
  price: number;
  name?: string;
  imageUrl?: string | null;
  variantId?: number;
  oldPrice?: number;
}

export type ModuleHookContext = {
  // Checkout context
  shippingMethodId?: number | null;
  shippingMethodCode?: string | null;
  paymentMethod?: string | null;
  shippingAddress?: Address;
  billingAddress?: Address;

  // Product context
  productId?: number;
  categoryId?: number;
  price?: number;

  // Cart context
  cartTotal?: number;
  cartItems?: CartItem[];

  // User context
  userId?: number | null;
  isLoggedIn?: boolean;

  // Generic - allow additional unknown properties
  [key: string]: unknown;
};

export type ModuleHookConfig = {
  hookName: string;
  componentPath: string;
  condition?: string; // JS expression to evaluate
  priority?: number;
};

export type ModuleConfig = {
  slug: string;
  name: string;
  version: string;
  hooks?: ModuleHookConfig[];
};

// Global registry of loaded module configs
const MODULE_CONFIGS: Record<string, ModuleConfig> = {};
let MODULE_SYSTEM_READY = false;
let MODULE_SYSTEM_INIT_PROMISE: Promise<void> | null = null;

/**
 * Initialize module system by loading all module configurations
 * This should be called once at app startup
 */
export async function initializeModuleSystem(): Promise<void> {
  // Return existing promise if already initializing
  if (MODULE_SYSTEM_INIT_PROMISE) {
    return MODULE_SYSTEM_INIT_PROMISE;
  }

  MODULE_SYSTEM_INIT_PROMISE = (async () => {
    try {
      // Scan for all module-config.json files in modules directory
      // In a real implementation, this would be done at build time
      const moduleConfigs = await loadModuleConfigs();

      moduleConfigs.forEach((config) => {
        MODULE_CONFIGS[config.slug] = config;
      });

      MODULE_SYSTEM_READY = true;
    } catch (error) {
      logger.error('[Module System] Failed to initialize:', error);
      MODULE_SYSTEM_READY = true; // Mark as ready even on error to avoid blocking
    }
  })();

  return MODULE_SYSTEM_INIT_PROMISE;
}

/**
 * Load all module configurations
 * Loads from /api/module-hooks (reads storefront/public/module-hooks.json)
 */
async function loadModuleConfigs(): Promise<ModuleConfig[]> {
  try {
    // Fetch module hooks configuration via API route (always returns JSON)
    const response = await fetch('/api/module-hooks', { cache: 'no-store' });
    if (!response.ok) {
      logger.warn('[Module System] Module hooks endpoint unavailable, no modules registered');
      return [];
    }

    const configs = await response.json();
    return Array.isArray(configs) ? configs : [];
  } catch (error) {
    logger.warn('[Module System] Error loading module configs:', error);
    return [];
  }
}

/**
 * Get all module hook components for a specific hook point
 *
 * @param hookName - The hook identifier (e.g., 'checkout.shipping.after-methods')
 * @param context - Context for condition evaluation
 * @returns Array of module components to render
 */
export async function getModuleHookComponents(
  hookName: string,
  context: ModuleHookContext = {}
): Promise<Array<{ component: ComponentType<ModuleHookContext>; moduleSlug: string; priority: number }>> {
  // Wait for module system to be ready
  if (!MODULE_SYSTEM_READY && MODULE_SYSTEM_INIT_PROMISE) {
    await MODULE_SYSTEM_INIT_PROMISE;
  }

  const components: Array<{ component: ComponentType<ModuleHookContext>; moduleSlug: string; priority: number }> = [];

  // Find all modules that have hooks for this hookName
  for (const [slug, config] of Object.entries(MODULE_CONFIGS)) {
    if (!config.hooks) continue;

    const moduleHooks = config.hooks.filter((h) => h.hookName === hookName);

    for (const hook of moduleHooks) {
      // Check condition
      if (hook.condition && !evaluateCondition(hook.condition, context)) {
        continue;
      }

      try {
        // Dynamically import the component
        const componentModule = await import(
          `@/components/modules/${slug}/${hook.componentPath}`
        );

        const componentName = hook.componentPath.split('/').pop()?.replace('.tsx', '');
        const component = componentModule[componentName!] || componentModule.default;

        if (component) {
          components.push({
            component,
            moduleSlug: slug,
            priority: hook.priority || 100,
          });
        } else {
          logger.warn(`[Module System] Component ${componentName} not found in module ${slug}`);
        }
      } catch {
        // Component loading errors are silently ignored
      }
    }
  }

  // Sort by priority (lower = higher priority)
  components.sort((a, b) => a.priority - b.priority);

  return components;
}

/**
 * Evaluate a condition string with given context
 * Safely evaluates simple JS expressions
 *
 * @param condition - JS expression as string (e.g., "shippingMethodCode === 'colissimo_point_relais'")
 * @param context - Context object for evaluation
 * @returns true if condition passes
 */
function evaluateCondition(condition: string, context: ModuleHookContext): boolean {
  try {
    // Create a function with context variables in scope
    const func = new Function(
      ...Object.keys(context),
      `return ${condition};`
    );

    return func(...Object.values(context));
  } catch (error) {
    logger.error('[Module System] Condition evaluation error:', { condition, error });
    return false;
  }
}

/**
 * Register a module config programmatically
 * Useful for dynamic module loading
 */
export function registerModuleConfig(config: ModuleConfig): void {
  MODULE_CONFIGS[config.slug] = config;
}

/**
 * Get all registered modules
 */
export function getRegisteredModules(): ModuleConfig[] {
  return Object.values(MODULE_CONFIGS);
}

/**
 * Check if a specific hook has any registered modules
 */
export function hasModuleHooks(hookName: string, context: ModuleHookContext = {}): boolean {
  return Object.values(MODULE_CONFIGS).some((config) =>
    config.hooks?.some(
      (h) => h.hookName === hookName && (!h.condition || evaluateCondition(h.condition, context))
    )
  );
}
