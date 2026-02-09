import { getThemeSettings } from '@/lib/api-theme';
import { validateCSSVariables } from '@/lib/css-variable-sanitizer';
import { logger } from '@/lib/logger';

/**
 * Server-side component that injects dynamic theme CSS variables
 *
 * Security: DCA-003 P0-2 Fix - Sanitizes CSS variables before injection
 */
export async function ThemeProvider() {
  let globalStyles = `
    body {
      font-family: var(--theme-body-font, 'Inter'), system-ui, -apple-system, sans-serif;
      font-size: var(--theme-body-size, 16)px;
      color: var(--theme-body-color, #374151);
      background-color: var(--theme-page-bg, #f6f6f7);
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: var(--theme-heading-font, 'Inter'), system-ui, -apple-system, sans-serif;
      font-weight: var(--theme-heading-weight, 600);
      color: var(--theme-heading-color, #111827);
    }
  `;
  let fontsHref: string | null = null;

  try {
    const theme = await getThemeSettings();

    // ✅ SANITIZE CSS variables to prevent XSS injection (DCA-003 fix)
    const cssContent = validateCSSVariables(theme.css_variables);

    // Additional global styles to apply typography variables
    globalStyles = `
      ${cssContent}

      /* Apply typography variables globally */
      body {
        font-family: var(--theme-body-font, 'Inter'), system-ui, -apple-system, sans-serif;
        font-size: var(--theme-body-size, 16)px;
        color: var(--theme-body-color, #374151);
        background-color: var(--theme-page-bg, #f6f6f7);
      }

      h1, h2, h3, h4, h5, h6 {
        font-family: var(--theme-heading-font, 'Inter'), system-ui, -apple-system, sans-serif;
        font-weight: var(--theme-heading-weight, 600);
        color: var(--theme-heading-color, #111827);
      }
    `;
    if (theme.settings.typography) {
      fontsHref = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(theme.settings.typography.heading_font)}:wght@400;500;600;700;800&family=${encodeURIComponent(theme.settings.typography.body_font)}:wght@400;500;600;700&display=swap`;
    }
  } catch (error) {
    logger.error('Failed to load theme settings:', error);
  }

  return (
    <>
      {/* Preconnect to Google Fonts for faster font loading */}
      <link rel="preconnect" href="https://fonts.googleapis.com" />
      <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
      <style dangerouslySetInnerHTML={{ __html: globalStyles }} />
      {/* Inject Google Fonts dynamically with display=swap for better performance */}
      {fontsHref && (
        <link rel="stylesheet" href={fontsHref} />
      )}
    </>
  );
}
