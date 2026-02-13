import { getThemeSettings } from '@/lib/api-theme';
import { validateCSSVariables } from '@/lib/css-variable-sanitizer';
import { logger } from '@/lib/logger';

/**
 * Server-side component that injects dynamic theme CSS variables
 *
 * Security: DCA-003 P0-2 Fix - Sanitizes CSS variables before injection
 */
export async function ThemeProvider() {
  const buildGlobalStyles = (cssVariables = "") => `
    ${cssVariables}

    :root {
      --theme-surface-radius: var(--theme-border-radius, 12px);
      --theme-control-radius: var(--theme-button-radius, var(--theme-surface-radius));
      --theme-pill-radius: var(--theme-control-radius);
      --theme-effective-border-width: var(--theme-border-width, 1px);
      --theme-effective-input-height-px: var(--theme-input-height-px, 38px);
      --theme-effective-input-border-color: var(--theme-input-border-color, var(--theme-border-default, #e5e7eb));
      --theme-effective-input-surface: var(--theme-input-surface, var(--theme-input-bg, #ffffff));
      --theme-effective-panel-padding: var(--theme-panel-padding, 1rem);
      --theme-effective-panel-background: var(--theme-panel-background, var(--theme-card-bg, #ffffff));
      --theme-effective-panel-border-color: var(--theme-panel-border-color, var(--theme-border-default, #e5e7eb));
      --theme-effective-panel-shadow: var(--theme-panel-shadow, var(--theme-shadow-sm, 0 1px 2px rgba(17,24,39,.08)));
    }

    html {
      font-size: var(--theme-body-size, 16px);
    }

    body {
      font-family: var(--theme-body-font, 'Inter'), system-ui, -apple-system, sans-serif;
      font-size: 1rem;
      color: var(--theme-body-color, #374151);
      background-color: var(--theme-page-bg, #f6f6f7);
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: var(--theme-heading-font, 'Inter'), system-ui, -apple-system, sans-serif;
      font-weight: var(--theme-heading-weight, 600);
      color: var(--theme-heading-color, #111827);
      line-height: 1.15;
    }

    h1 { font-size: var(--theme-h1-size, 48px); }
    h2 { font-size: var(--theme-h2-size, 36px); }
    h3 { font-size: var(--theme-h3-size, 28px); }
    h4 { font-size: var(--theme-h4-size, 22px); }
    h5 { font-size: var(--theme-h5-size, 18px); }
    h6 { font-size: var(--theme-h6-size, 16px); }

    /* Global shape consistency based on active theme settings */
    :where([class*="rounded-"], .rounded) {
      border-radius: var(--theme-surface-radius) !important;
    }

    :where(.rounded-none) {
      border-radius: 0 !important;
    }

    :where(.rounded-full) {
      border-radius: var(--theme-pill-radius) !important;
    }

    :where(
      input:not([type]),
      input[type="text"],
      input[type="email"],
      input[type="password"],
      input[type="search"],
      input[type="tel"],
      input[type="url"],
      input[type="number"],
      input[type="date"],
      input[type="datetime-local"],
      input[type="month"],
      input[type="time"],
      input[type="week"],
      select,
      textarea
    ) {
      border-radius: var(--theme-surface-radius) !important;
      min-height: var(--theme-effective-input-height-px) !important;
      border-width: var(--theme-input-border-width, var(--theme-effective-border-width, 1px)) !important;
      border-style: solid;
      border-color: var(--theme-effective-input-border-color) !important;
      background-color: var(--theme-effective-input-surface) !important;
      transition: box-shadow 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
    }

    :where(
      input:not([type]),
      input[type="text"],
      input[type="email"],
      input[type="password"],
      input[type="search"],
      input[type="tel"],
      input[type="url"],
      input[type="number"],
      input[type="date"],
      input[type="datetime-local"],
      input[type="month"],
      input[type="time"],
      input[type="week"],
      select,
      textarea
    ):focus-visible {
      outline: none;
      box-shadow: 0 0 0 var(--theme-focus-ring-width, 1px) var(--theme-focus-ring-color, var(--theme-primary, #111827));
    }

    :where(
      button,
      input[type="button"],
      input[type="submit"],
      input[type="reset"],
      a[role="button"],
      a.inline-flex[class*="rounded"],
      .theme-button-shape
    ) {
      border-radius: var(--theme-control-radius) !important;
    }

    .theme-container {
      max-width: var(--theme-container-max-width, 72rem) !important;
    }

    .theme-panel {
      background: var(--theme-effective-panel-background) !important;
      border-color: var(--theme-effective-panel-border-color) !important;
      border-width: var(--theme-effective-border-width, 1px) !important;
      box-shadow: var(--theme-effective-panel-shadow) !important;
      padding: var(--theme-effective-panel-padding, 1rem) !important;
    }

    .theme-header-inner {
      min-height: var(--theme-header-height, 64px) !important;
    }

    .theme-header-control {
      background-color: var(--theme-header-control-bg, transparent) !important;
      border-color: var(--theme-header-control-border, var(--theme-border-default, #e5e7eb)) !important;
      color: var(--theme-header-control-text, var(--theme-body-color, #374151)) !important;
      border-width: var(--theme-effective-border-width, 1px) !important;
      border-radius: var(--theme-control-radius) !important;
    }

    .theme-footer-surface {
      padding-top: var(--theme-footer-padding-y, 1.5rem) !important;
      padding-bottom: var(--theme-footer-padding-y, 1.5rem) !important;
    }

    .theme-checkout-stepper {
      background: var(--theme-checkout-stepper-panel-bg, var(--theme-card-bg, #ffffff)) !important;
      border-color: var(--theme-checkout-stepper-panel-border, var(--theme-border-default, #e5e7eb)) !important;
      box-shadow: var(--theme-checkout-stepper-panel-shadow, none) !important;
    }

    .theme-checkout-summary {
      background: var(--theme-checkout-summary-bg, var(--theme-card-bg, #ffffff)) !important;
      border-color: var(--theme-checkout-summary-border, var(--theme-border-default, #e5e7eb)) !important;
      box-shadow: var(--theme-checkout-summary-shadow, none) !important;
    }

    .theme-account-card {
      background: var(--theme-account-card-bg, var(--theme-card-bg, #ffffff)) !important;
      border-color: var(--theme-account-card-border, var(--theme-border-default, #e5e7eb)) !important;
      box-shadow: var(--theme-account-card-shadow, none) !important;
    }

    .theme-qty-control {
      background: var(--theme-qty-bg, var(--theme-card-bg, #ffffff)) !important;
      border-color: var(--theme-qty-border, var(--theme-border-default, #e5e7eb)) !important;
      border-width: var(--theme-effective-border-width, 1px) !important;
      padding-left: var(--theme-qty-padding-x, 0.5rem) !important;
      padding-right: var(--theme-qty-padding-x, 0.5rem) !important;
      border-radius: var(--theme-control-radius) !important;
    }

    :where(.shadow-sm) { box-shadow: var(--theme-shadow-sm, none) !important; }
    :where(.shadow-md) { box-shadow: var(--theme-shadow-md, none) !important; }
    :where(.shadow-lg) { box-shadow: var(--theme-shadow-lg, none) !important; }
    :where(.shadow-xl, .shadow-2xl) { box-shadow: var(--theme-shadow-xl, none) !important; }

  `;

  let globalStyles = buildGlobalStyles();
  let fontsHref: string | null = null;

  try {
    const theme = await getThemeSettings();

    // ✅ SANITIZE CSS variables to prevent XSS injection (DCA-003 fix)
    const cssContent = validateCSSVariables(theme.css_variables);

    globalStyles = buildGlobalStyles(cssContent);
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
