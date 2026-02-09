"use client";

import { useEffect, useMemo, useState, ComponentType } from "react";
import { getModuleHookComponents, ModuleHookContext } from "@/lib/module-system";
import { logger } from "@/lib/logger";

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
  const contextKey = useMemo(() => JSON.stringify(context), [context]);
  const parsedContext = useMemo<ModuleHookContext>(() => {
    try {
      return JSON.parse(contextKey) as ModuleHookContext;
    } catch {
      return {};
    }
  }, [contextKey]);

  useEffect(() => {
    async function loadComponents() {
      try {
        const hookComponents = await getModuleHookComponents(hookName, parsedContext);
        setComponents(hookComponents);
      } catch (error) {
        logger.error(`[ModuleHooks] Error loading components for ${hookName}:`, error);
        setComponents([]);
      } finally {
        setLoading(false);
      }
    }

    loadComponents();
  }, [hookName, parsedContext]);

  if (loading) {
    return <>{fallback}</>;
  }

  if (components.length === 0) {
    return <>{fallback}</>;
  }

  return (
    <>
      {components.map(({ component: Component, moduleSlug }) => (
        <Component key={moduleSlug} {...parsedContext} />
      ))}
    </>
  );
}
