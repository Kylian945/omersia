/**
 * Sanitizes HTML content to prevent XSS attacks
 * Works in both server-side (SSR) and client-side contexts
 * @param html - Raw HTML string from backend
 * @returns Sanitized HTML with only allowed tags/attributes
 */
export function sanitizeHTML(html: string | undefined | null): string {
  if (!html) return '';

  // Server-side: dynamic import to avoid SSR issues
  if (typeof window === 'undefined') {
    // During SSR, we use a basic server-side sanitization
    return basicSanitize(html);
  }

  // Client-side: use DOMPurify
  if (typeof window !== 'undefined' && window.DOMPurify) {
    return window.DOMPurify.sanitize(html, {
      ALLOWED_TAGS: [
        'b', 'i', 'em', 'strong', 'a', 'p', 'br', 'hr',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'code', 'pre', 'span', 'div',
      ],
      ALLOWED_ATTR: ['href', 'target', 'rel', 'class'],
      ALLOW_DATA_ATTR: false,
    });
  }

  // Fallback
  return basicSanitize(html);
}

/**
 * Sanitizes rich HTML input and returns a plain-text string.
 * Useful for metadata, snippets, and card previews.
 */
export function sanitizePlainText(html: string | undefined | null): string {
  const sanitized = sanitizeHTML(html);

  if (!sanitized) {
    return '';
  }

  return sanitized
    .replace(/<br\s*\/?>/gi, ' ')
    .replace(/<\/(p|div|li|h[1-6])>/gi, ' ')
    .replace(/<[^>]+>/g, '')
    .replace(/&nbsp;/gi, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

/**
 * Basic server-side HTML sanitization
 * Escapes dangerous characters and removes script tags
 */
function basicSanitize(html: string): string {
  // Remove script tags and their content
  let cleaned = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');

  // Remove event handlers (onclick, onerror, etc.)
  cleaned = cleaned.replace(/\s*on\w+\s*=\s*["'][^"']*["']/gi, '');
  cleaned = cleaned.replace(/\s*on\w+\s*=\s*[^\s>]*/gi, '');

  // Remove javascript: protocol
  cleaned = cleaned.replace(/javascript:/gi, '');

  // Remove data: protocol (except for safe data:image)
  cleaned = cleaned.replace(/data:(?!image\/)(.*?)[;,]/gi, '');

  // Remove style tags with potentially malicious content
  cleaned = cleaned.replace(/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/gi, '');

  return cleaned;
}

// Load DOMPurify on client-side
if (typeof window !== 'undefined') {
  import('isomorphic-dompurify').then((module) => {
    (window as Window & { DOMPurify?: typeof module.default }).DOMPurify = module.default;
  }).catch(() => {
    // Silently fail if DOMPurify can't be loaded
  });
}

declare global {
  interface Window {
    DOMPurify?: {
      sanitize: (html: string, config?: Record<string, unknown>) => string;
    };
  }
}
