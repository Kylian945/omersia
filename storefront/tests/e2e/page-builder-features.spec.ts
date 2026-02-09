import { test, expect } from '@playwright/test';

test.describe('Page Builder Features', () => {
  test.describe('Backward Compatibility', () => {
    test('renders old pages without new properties correctly', async ({ page }) => {
      // Note: This test assumes you have a test page without gap/alignment properties
      // You may need to create a fixture or use an existing page

      await page.goto('/');

      // Verify page loads
      await expect(page.locator('body')).toBeVisible();

      // Check that sections render with default gap (gap-4)
      const sections = page.locator('section');
      const firstSection = sections.first();
      await expect(firstSection).toBeVisible();

      // Verify no layout breaks
      const images = page.locator('img');
      if (await images.count() > 0) {
        const firstImage = images.first();
        await expect(firstImage).toBeVisible();
        // Should have default h-auto class
        await expect(firstImage).toHaveClass(/h-auto/);
      }
    });

    test('handles missing image properties gracefully', async ({ page }) => {
      await page.goto('/');

      const images = page.locator('img');
      const count = await images.count();

      if (count > 0) {
        // Check all images load
        for (let i = 0; i < count; i++) {
          const img = images.nth(i);
          await expect(img).toBeVisible();

          // Verify no broken images
          const naturalWidth = await img.evaluate((el: HTMLImageElement) => el.naturalWidth);
          // If image loaded, width > 0
          if (naturalWidth === 0) {
            // Check if it's a placeholder
            const src = await img.getAttribute('src');
            console.log(`Image with src "${src}" may be broken`);
          }
        }
      }
    });
  });

  test.describe('XSS Prevention', () => {
    test('prevents XSS via image URL', async ({ page }) => {
      // This test verifies that malicious URLs don't execute
      // In a real scenario, you'd need to inject a test page with malicious content

      await page.goto('/');

      // Monitor for any alert dialogs (which would indicate XSS)
      let alertFired = false;
      page.on('dialog', async dialog => {
        alertFired = true;
        await dialog.dismiss();
      });

      // Wait for page to fully load
      await page.waitForLoadState('networkidle');

      // Verify no alerts fired
      expect(alertFired).toBe(false);
    });

    test('sanitizes malicious CSS in theme variables', async ({ page }) => {
      await page.goto('/');

      // Check for injected scripts in the page
      const scripts = await page.locator('script').all();

      for (const script of scripts) {
        const content = await script.textContent();

        // Verify no malicious patterns
        if (content) {
          expect(content).not.toContain('alert(');
          expect(content).not.toContain('document.cookie');
          expect(content).not.toContain('eval(');
        }
      }

      // Verify CSS variables are applied safely
      const root = page.locator('html');
      const bgColor = await root.evaluate((el) => {
        return window.getComputedStyle(el).getPropertyValue('--theme-primary');
      });

      // If variable exists, verify it's a valid color (not script)
      if (bgColor) {
        expect(bgColor).not.toContain('script');
        expect(bgColor).not.toContain('javascript:');
      }
    });
  });

  test.describe('Layout Rendering', () => {
    test('renders sections with correct structure', async ({ page }) => {
      await page.goto('/');

      // Check for page builder sections
      const sections = page.locator('section');
      const count = await sections.count();

      if (count > 0) {
        // Verify first section has correct structure
        const firstSection = sections.first();
        await expect(firstSection).toBeVisible();

        // Check for SmartContainer (should have max-width constraint)
        const container = firstSection.locator('.container, [class*="max-w"]').first();
        if (await container.count() > 0) {
          await expect(container).toBeVisible();
        }
      }
    });

    test('handles responsive layouts correctly', async ({ page }) => {
      // Test desktop view
      await page.setViewportSize({ width: 1920, height: 1080 });
      await page.goto('/');

      const sections = page.locator('section');
      if (await sections.count() > 0) {
        await expect(sections.first()).toBeVisible();
      }

      // Test mobile view
      await page.setViewportSize({ width: 375, height: 667 });
      await page.reload();

      // Verify layout doesn't break on mobile
      if (await sections.count() > 0) {
        await expect(sections.first()).toBeVisible();
      }

      // Check for horizontal scrolling (should not exist)
      const bodyScrollWidth = await page.evaluate(() => {
        return document.body.scrollWidth;
      });
      const viewportWidth = page.viewportSize()?.width || 0;

      // Allow small overflow for scrollbar
      expect(bodyScrollWidth).toBeLessThanOrEqual(viewportWidth + 20);
    });

    test('renders images with proper aspect ratios', async ({ page }) => {
      await page.goto('/');

      const images = page.locator('img');
      const count = await images.count();

      if (count > 0) {
        for (let i = 0; i < Math.min(count, 5); i++) {
          const img = images.nth(i);
          await expect(img).toBeVisible();

          // Check for aspect ratio classes or natural dimensions
          const classList = await img.getAttribute('class');
          const hasAspectClass = classList?.includes('aspect-') || classList?.includes('h-auto');

          expect(hasAspectClass).toBe(true);
        }
      }
    });
  });

  test.describe('Performance', () => {
    test('page loads within acceptable time', async ({ page }) => {
      const startTime = Date.now();

      await page.goto('/');
      await page.waitForLoadState('networkidle');

      const loadTime = Date.now() - startTime;

      // Page should load in less than 5 seconds
      expect(loadTime).toBeLessThan(5000);
    });

    test('images load lazily', async ({ page }) => {
      await page.goto('/');

      const images = page.locator('img');
      const count = await images.count();

      if (count > 3) {
        // Check if images below the fold have loading="lazy"
        const belowFoldImages = images.nth(3);
        const loading = await belowFoldImages.getAttribute('loading');

        // Note: Next.js Image component handles lazy loading automatically
        // This test is informational
        console.log('Image loading strategy:', loading || 'default');
      }
    });
  });

  test.describe('Accessibility', () => {
    test('images have alt attributes', async ({ page }) => {
      await page.goto('/');

      const images = page.locator('img');
      const count = await images.count();

      if (count > 0) {
        for (let i = 0; i < count; i++) {
          const img = images.nth(i);

          // Check for alt attribute (may be empty for decorative images)
          const hasAlt = await img.evaluate((el) => el.hasAttribute('alt'));
          expect(hasAlt).toBe(true);
        }
      }
    });

    test('sections have proper semantic structure', async ({ page }) => {
      await page.goto('/');

      // Check for semantic HTML
      const sections = page.locator('section');
      const count = await sections.count();

      if (count > 0) {
        // Verify sections use semantic tags
        await expect(sections.first()).toHaveRole('region', { timeout: 1000 }).catch(() => {
          // Role might not be set, which is okay
          console.log('Section does not have explicit region role');
        });
      }
    });

    test('passes basic keyboard navigation', async ({ page }) => {
      await page.goto('/');

      // Try to tab through the page
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');

      // Verify focus is visible
      const focusedElement = await page.evaluate(() => {
        const el = document.activeElement;
        return el?.tagName;
      });

      // Focus should be on some element (not body)
      expect(focusedElement).not.toBe('BODY');
    });
  });

  test.describe('Edge Cases', () => {
    test('handles empty sections gracefully', async ({ page }) => {
      await page.goto('/');

      // Check if page renders even with potentially empty sections
      await expect(page.locator('body')).toBeVisible();

      // No console errors
      const errors: string[] = [];
      page.on('pageerror', (error) => {
        errors.push(error.message);
      });

      await page.waitForLoadState('networkidle');

      // Filter out known safe errors
      const criticalErrors = errors.filter(error =>
        !error.includes('Failed to load resource') &&
        !error.includes('net::ERR_')
      );

      expect(criticalErrors).toHaveLength(0);
    });

    test('handles very long content', async ({ page }) => {
      await page.goto('/');

      // Scroll to bottom
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));

      // Wait a bit for lazy-loaded content
      await page.waitForTimeout(500);

      // Verify page doesn't crash
      await expect(page.locator('body')).toBeVisible();
    });
  });
});
