import { ComponentType } from "react";
import { logger } from "./logger";

/**
 * Dynamic component resolver for theme-specific components
 *
 * This system allows themes to provide custom implementations of components.
 * If a theme doesn't provide a specific component, it falls back to the default theme.
 *
 * Usage:
 * ```tsx
 * const ProductCard = await getThemeComponent('product/ProductCard', componentPath);
 * ```
 */

type ComponentModule<TProps extends object> = {
  default?: ComponentType<TProps>;
  [key: string]: ComponentType<TProps> | undefined;
};

/**
 * Load a theme-specific component dynamically
 *
 * @param componentPath - Path relative to the theme folder (e.g., 'product/ProductCard')
 * @param themePath - The theme identifier (e.g., 'material-design', 'vision')
 * @returns The component, either from the theme or fallback to vision
 */
export async function getThemeComponent(
  componentPath: string,
  themePath: string | null | undefined
): Promise<ComponentType<Record<string, unknown>>> {
  const theme = themePath || "vision";

  try {
    // Try to load from the specified theme
    const themeModule = await import(
      `@/components/themes/${theme}/${componentPath}`
    ) as ComponentModule<Record<string, unknown>>;

    // Get the named export or default export
    const componentName = componentPath.split("/").pop() || "Component";
    const component =
      themeModule[componentName] || themeModule.default;

    if (!component) {
      throw new Error(`Component ${componentName} not found in theme ${theme}`);
    }

    return component;
  } catch (error) {
    // If theme component doesn't exist, fallback to vision theme
    if (theme !== "vision") {
      logger.warn(
        `Component ${componentPath} not found in theme '${theme}', falling back to vision theme`
      );

      const visionModule = await import(
        `@/components/themes/vision/${componentPath}`
      ) as ComponentModule<Record<string, unknown>>;
      const componentName = componentPath.split("/").pop() || "Component";
      const component =
        visionModule[componentName] || visionModule.default;

      if (!component) {
        throw new Error(
          `Component ${componentName} not found in fallback vision theme`
        );
      }

      return component;
    }

    // If vision itself doesn't have it, throw the error
    // This is better than silently failing
    logger.error(
      `Component ${componentPath} not found in vision theme. This should not happen as vision contains all widgets.`
    );
    throw error;
  }
}

/**
 * Check if a theme has a specific component override
 *
 * @param componentPath - Path relative to the theme folder
 * @param themePath - The theme identifier
 * @returns true if the theme has this component
 */
export async function themeHasComponent(
  componentPath: string,
  themePath: string | null | undefined
): Promise<boolean> {
  const theme = themePath || "vision";

  try {
    await import(`@/components/themes/${theme}/${componentPath}`);
    return true;
  } catch {
    return false;
  }
}
