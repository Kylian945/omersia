/**
 * CSS Variable Sanitizer - DCA-003 P0-2 Fix
 *
 * Validates CSS custom property declarations to prevent XSS injection attacks
 * via ThemeProvider's dangerouslySetInnerHTML.
 *
 * Attack vectors blocked:
 * - </style> tag escape attempts
 * - <script> tag injection
 * - javascript: protocol
 * - CSS expression() (IE legacy)
 * - @import directives
 * - Malicious data URIs
 */

import type { SpacingValue, SpacingConfig } from './widget-helpers';

/**
 * Validates and sanitizes CSS custom property declarations
 *
 * @param cssVariables - Raw CSS from backend (untrusted)
 * @returns Sanitized CSS variables safe for dangerouslySetInnerHTML
 *
 * @example
 * ```typescript
 * const safe = validateCSSVariables(theme.css_variables);
 * <style dangerouslySetInnerHTML={{ __html: safe }} />
 * ```
 */
export function validateCSSVariables(
  cssVariables: string | undefined | null
): string {
  if (!cssVariables) return '';

  let cleaned = cssVariables;

  // 1. Remove </style> tag escape attempts
  cleaned = cleaned.replace(/<\/style>/gi, '');
  cleaned = cleaned.replace(/<\/\s*style\s*>/gi, ''); // With whitespace

  // 2. Remove <script> tags
  cleaned = cleaned.replace(
    /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
    ''
  );

  // 3. Remove javascript: protocol (and the entire URL)
  // Match javascript: followed by anything up to closing ), ", ', or whitespace
  cleaned = cleaned.replace(/javascript:[^)\s"']*/gi, '');

  // 4. Remove expression() (IE legacy CSS expressions)
  cleaned = cleaned.replace(/expression\s*\(/gi, '');

  // 5. Remove @import (prevents loading external CSS with malicious content)
  cleaned = cleaned.replace(/@import\b/gi, '');

  // 6. Remove data: URIs (except for safe image formats)
  // Allow: data:image/png, data:image/svg+xml
  // Block: data:text/html, data:text/javascript
  // Note: The negative lookahead ensures we only block non-image data URIs
  cleaned = cleaned.replace(
    /data:(?!image\/(png|jpg|jpeg|gif|svg\+xml|webp))[a-z]+\/[a-z+-]+[^)\s"']*/gi,
    ''
  );

  // 6.5. Normalize inline :root declarations (e.g., ":root { --var: value; }")
  // Split them into multiple lines for easier validation
  cleaned = cleaned.replace(/:root\s*\{\s*(--[^}]+)\}/gi, (match, vars) => {
    return `:root {\n  ${vars.trim()}\n}`;
  });

  // 7. Whitelist validation: Only allow valid CSS variable lines
  const lines = cleaned.split('\n');
  const validLines = lines.filter(line => {
    const trimmed = line.trim();

    // Allow empty lines
    if (!trimmed) return true;

    // Allow CSS comments
    if (trimmed.startsWith('/*') || trimmed.endsWith('*/') || trimmed.includes('/*')) {
      return true;
    }

    // Allow :root selector opening/closing
    if (trimmed === ':root {' || trimmed === '}') return true;

    // Allow valid CSS variable declarations
    // Pattern: --variable-name: value;
    // Allows: letters, numbers, hyphens in name
    // Allows: standard CSS values (colors, sizes, data URIs, etc.)
    // Note: We allow semicolons in values (e.g., data:image/png;base64)
    if (/^--[a-z0-9-]+:\s*[^{}<>]*;$/i.test(trimmed)) {
      // Additional check: no dangerous patterns in value
      if (trimmed.includes('</style>') ||
          trimmed.includes('<script>') ||
          trimmed.match(/javascript:/i) ||
          trimmed.match(/expression\s*\(/i)) {
        return false;
      }
      return true;
    }

    // Reject everything else (arbitrary HTML, malicious CSS)
    return false;
  });

  return validLines.join('\n');
}

/**
 * Additional security validation: Check if CSS variables contain suspicious patterns
 *
 * Use this for logging/monitoring security incidents
 *
 * @param cssVariables - Raw CSS to analyze
 * @returns Array of warning messages (empty if safe)
 *
 * @example
 * ```typescript
 * const warnings = validateCSSVariablesSecurity(theme.css_variables);
 * if (warnings.length > 0) {
 *   console.error('CSS injection attempt:', warnings);
 * }
 * ```
 */
export function validateCSSVariablesSecurity(
  cssVariables: string | undefined | null
): string[] {
  if (!cssVariables) return [];

  const warnings: string[] = [];

  if (cssVariables.includes('</style>')) {
    warnings.push('Contains style tag escape attempt');
  }

  if (cssVariables.includes('<script>')) {
    warnings.push('Contains script tag');
  }

  if (cssVariables.match(/javascript:/i)) {
    warnings.push('Contains javascript: protocol');
  }

  if (cssVariables.match(/expression\s*\(/i)) {
    warnings.push('Contains CSS expression() (IE legacy)');
  }

  if (cssVariables.match(/@import/i)) {
    warnings.push('Contains @import directive');
  }

  if (cssVariables.match(/data:text\/(html|javascript)/i)) {
    warnings.push('Contains dangerous data URI');
  }

  return warnings;
}

/**
 * Validates gap value for page builder sections/containers
 *
 * @param value - Gap value from backend (untrusted)
 * @returns Validated gap value or default 'md'
 *
 * @example
 * ```typescript
 * validateGap('lg') // 'lg'
 * validateGap('invalid') // 'md'
 * validateGap(undefined) // 'md'
 * ```
 */
export function validateGap(value: unknown): string {
  const validGaps = ['none', 'xs', 'sm', 'md', 'lg', 'xl'];
  if (typeof value === 'string' && validGaps.includes(value)) {
    return value;
  }
  return 'md'; // Default
}

/**
 * Validates alignment value for page builder sections/containers
 *
 * @param value - Alignment value from backend (untrusted)
 * @returns Validated alignment value or default 'stretch'
 *
 * @example
 * ```typescript
 * validateAlignment('center') // 'center'
 * validateAlignment('invalid') // 'stretch'
 * validateAlignment(undefined) // 'stretch'
 * ```
 */
export function validateAlignment(value: unknown): string {
  const validAlignments = ['start', 'center', 'end', 'stretch', 'baseline'];
  if (typeof value === 'string' && validAlignments.includes(value)) {
    return value;
  }
  return 'stretch'; // Default
}

/**
 * Validates aspect ratio value for image widgets
 *
 * @param value - Aspect ratio value from backend (untrusted)
 * @returns Validated aspect ratio or default 'auto'
 *
 * @example
 * ```typescript
 * validateAspectRatio('16:9') // '16:9'
 * validateAspectRatio('invalid') // 'auto'
 * validateAspectRatio(undefined) // 'auto'
 * ```
 */
export function validateAspectRatio(value: unknown): string {
  const validRatios = ['1:1', '4:3', '16:9', '2:1', '21:9', 'auto'];
  if (typeof value === 'string' && validRatios.includes(value)) {
    return value;
  }
  return 'auto'; // Default
}

/**
 * Validates object-fit value for image widgets
 *
 * @param value - Object-fit value from backend (untrusted)
 * @returns Validated object-fit or default 'cover'
 *
 * @example
 * ```typescript
 * validateObjectFit('contain') // 'contain'
 * validateObjectFit('invalid') // 'cover'
 * validateObjectFit(undefined) // 'cover'
 * ```
 */
export function validateObjectFit(value: unknown): string {
  const validFits = ['contain', 'cover', 'fill', 'scale-down'];
  if (typeof value === 'string' && validFits.includes(value)) {
    return value;
  }
  return 'cover'; // Default
}

/**
 * Validates object-position value for image widgets
 *
 * @param value - Object-position value from backend (untrusted)
 * @returns Validated object-position or default 'center'
 *
 * @example
 * ```typescript
 * validateObjectPosition('top') // 'top'
 * validateObjectPosition('invalid') // 'center'
 * validateObjectPosition(undefined) // 'center'
 * ```
 */
export function validateObjectPosition(value: unknown): string {
  const validPositions = ['top', 'center', 'bottom', 'left', 'right'];
  if (typeof value === 'string' && validPositions.includes(value)) {
    return value;
  }
  return 'center'; // Default
}

/**
 * Validates numeric size (width/height) for image widgets
 *
 * @param value - Size value from backend (untrusted)
 * @param max - Maximum allowed value (default: 2000)
 * @returns Validated number or null if invalid
 *
 * @example
 * ```typescript
 * validateNumericSize(800) // 800
 * validateNumericSize('600') // 600
 * validateNumericSize(-10) // null
 * validateNumericSize(3000) // null
 * validateNumericSize(undefined) // null
 * ```
 */
export function validateNumericSize(value: unknown, max: number = 2000): number | null {
  // Handle null/undefined explicitly (Number(null) = 0, Number(undefined) = NaN)
  if (value === null || value === undefined) {
    return null;
  }
  const num = Number(value);
  if (!Number.isFinite(num) || num < 0 || num > max) {
    return null;
  }
  return Math.round(num);
}

/**
 * Validates spacing value (token or custom pixel number)
 *
 * @param value - Spacing value from backend (untrusted)
 * @returns Validated spacing value or 'none'
 *
 * @example
 * ```typescript
 * validateSpacingValue('md') // 'md'
 * validateSpacingValue('2xl') // '2xl'
 * validateSpacingValue(20) // 20
 * validateSpacingValue('invalid') // 'none'
 * validateSpacingValue(-10) // 'none'
 * validateSpacingValue(5000) // 'none'
 * validateSpacingValue(undefined) // 'none'
 * ```
 */
export function validateSpacingValue(value: unknown): SpacingValue {
  // Validate token values
  const validTokens = ['none', 'xs', 'sm', 'md', 'lg', 'xl', '2xl'] as const;
  if (typeof value === 'string' && (validTokens as readonly string[]).includes(value)) {
    return value as SpacingValue;
  }

  // Validate custom pixel values
  if (typeof value === 'number') {
    // Reject negative numbers and unreasonably large values
    if (value >= 0 && value <= 1000) {
      return Math.round(value);
    }
  }

  // Try to parse string as number
  if (typeof value === 'string') {
    const num = Number(value);
    if (Number.isFinite(num) && num >= 0 && num <= 1000) {
      return Math.round(num);
    }
  }

  return 'none'; // Default
}

/**
 * Validates spacing configuration object for padding/margin
 *
 * @param config - Spacing configuration from backend (untrusted)
 * @returns Validated spacing configuration or empty object
 *
 * @example
 * ```typescript
 * validateSpacingConfig({ all: 'md' }) // { all: 'md' }
 * validateSpacingConfig({ top: 'lg', bottom: 20 }) // { top: 'lg', bottom: 20 }
 * validateSpacingConfig({ top: 'invalid' }) // { top: 'none' }
 * validateSpacingConfig(null) // {}
 * validateSpacingConfig('not an object') // {}
 * ```
 */
export function validateSpacingConfig(config: unknown): SpacingConfig {
  // Ensure config is an object
  if (!config || typeof config !== 'object' || Array.isArray(config)) {
    return {};
  }

  const validated: SpacingConfig = {};
  const configObj = config as Record<string, unknown>;

  // Validate each side individually
  const sides = ['top', 'right', 'bottom', 'left', 'all'] as const;

  for (const side of sides) {
    if (side in configObj) {
      const validatedValue = validateSpacingValue(configObj[side]);
      // Only include non-'none' values to keep the object clean
      if (validatedValue !== 'none' || configObj[side] === 'none') {
        validated[side] = validatedValue;
      }
    }
  }

  return validated;
}

/**
 * Validates CSS percentage value for column widths
 *
 * @param value - Percentage value from backend (untrusted)
 * @returns Validated percentage (0-100) or 100
 *
 * @example
 * ```typescript
 * validateCSSPercentage(50) // 50
 * validateCSSPercentage('75') // 75
 * validateCSSPercentage(-10) // 100
 * validateCSSPercentage(150) // 100
 * validateCSSPercentage(undefined) // 100
 * ```
 */
export function validateCSSPercentage(value: unknown): number {
  if (value === null || value === undefined) {
    return 100;
  }
  const num = Number(value);
  if (!Number.isFinite(num) || num < 0 || num > 100) {
    return 100;
  }
  return Math.round(num);
}
