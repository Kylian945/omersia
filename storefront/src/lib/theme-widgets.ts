import { getThemeSettings } from './api-theme';
import { logger } from './logger';

/**
 * Get theme widgets dynamically based on the active theme
 * This function loads widgets from the active theme or falls back to 'vision' theme
 */
export async function getThemeWidgets() {
  const theme = await getThemeSettings();
  const themeSlug = theme.theme_slug || 'vision';

  try {
    // Try to load widgets from the active theme
    const widgets = await import(`@/components/themes/${themeSlug}/widgets`);
    return widgets;
  } catch (error) {
    // If the active theme doesn't have widgets, fallback to vision
    if (themeSlug !== 'vision') {
      logger.warn(
        `Widgets not found in theme '${themeSlug}', falling back to vision theme`
      );
      const widgets = await import('@/components/themes/vision/widgets');
      return widgets;
    }

    // If even vision doesn't have widgets, throw the error
    logger.error('Widgets not found in vision theme. This should not happen.');
    throw error;
  }
}

/**
 * Get the active theme slug
 */
export async function getActiveThemeSlug(): Promise<string> {
  const theme = await getThemeSettings();
  return theme.theme_slug || 'vision';
}
