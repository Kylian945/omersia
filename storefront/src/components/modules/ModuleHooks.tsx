"use client";

import { useEffect, useState, ComponentType } from "react";
import { getModuleHookComponents, ModuleHookContext } from "@/lib/module-system";

type ModuleHooksProps = {
  hookName: string;
  context?: ModuleHookContext;
  fallback?: React.ReactNode;
};

/**
 * Renders all module components registered at a specific hook point
 *
 * Usage in any component:
 * ```tsx
 * <ModuleHooks
 *   hookName="checkout.shipping.after-methods"
 *   context={{ shippingMethodCode, shippingAddress }}
 * />
 * ```
 *
 * This will render all modules that have registered components for this hook
 * and meet their condition requirements.
 */
export function ModuleHooks({ hookName, context = {}, fallback = null }: ModuleHooksProps) {
  const [components, setComponents] = useState<
    Array<{ component: ComponentType<Record<string, unknown>>; moduleSlug: string; priority: number }>
  >([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadComponents() {
      try {
        const hookComponents = await getModuleHookComponents(hookName, context);
        setComponents(hookComponents);
      } catch (error) {
        console.error(`[ModuleHooks] Error loading components for ${hookName}:`, error);
        setComponents([]);
      } finally {
        setLoading(false);
      }
    }

    loadComponents();
  }, [hookName, JSON.stringify(context)]);

  if (loading) {
    return <>{fallback}</>;
  }

  if (components.length === 0) {
    return <>{fallback}</>;
  }

  return (
    <>
      {components.map(({ component: Component, moduleSlug }) => (
        <Component key={moduleSlug} {...context} />
      ))}
    </>
  );
}
