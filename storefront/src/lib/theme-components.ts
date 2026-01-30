import { ComponentType } from "react";

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

type ComponentModule = {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  [key: string]: ComponentType<any>;
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
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
): Promise<ComponentType<any>> {
  const theme = themePath || "vision";

  try {
    // Try to load from the specified theme
    const themeModule: ComponentModule = await import(
      `@/components/themes/${theme}/${componentPath}`
    );

    // Get the named export or default export
    const componentName = componentPath.split("/").pop() || "Component";
    return themeModule[componentName] || themeModule.default;
  } catch (error) {
    // If theme component doesn't exist, fallback to vision theme
    if (theme !== "vision") {
      console.warn(
        `Component ${componentPath} not found in theme '${theme}', falling back to vision theme`
      );

      const visionModule: ComponentModule = await import(
        `@/components/themes/vision/${componentPath}`
      );
      const componentName = componentPath.split("/").pop() || "Component";
      return visionModule[componentName] || visionModule.default;
    }

    // If vision itself doesn't have it, throw the error
    // This is better than silently failing
    console.error(
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
