import { defineConfig, devices } from '@playwright/test';

const isCI = !!process.env.CI;
const chromiumExecutablePath = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH;

const chromeProjectUse = {
  ...devices['Desktop Chrome'],
  ...(chromiumExecutablePath
    ? {
        launchOptions: {
          executablePath: chromiumExecutablePath,
        },
      }
    : {}),
};

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: !isCI,
  forbidOnly: isCI,
  retries: isCI ? 2 : 0,
  workers: isCI ? 1 : undefined,
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['json', { outputFile: 'playwright-report/results.json' }],
  ],
  use: {
    baseURL: 'http://localhost:3000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },

  // Named projects for intention-based testing
  projects: [
    {
      name: 'security',
      testMatch: /.*xss.*\.spec\.ts/,
      use: chromeProjectUse,
    },
    {
      name: 'theme',
      testMatch: /.*theme.*\.spec\.ts/,
      testIgnore: /.*xss.*\.spec\.ts/,
      use: chromeProjectUse,
    },
    {
      name: 'all-browsers',
      testIgnore: [/.*xss.*\.spec\.ts/, /.*theme.*\.spec\.ts/],
      use: chromeProjectUse,
    },
    // Additional browsers for comprehensive CI testing
    ...(isCI ? [
      {
        name: 'firefox',
        testIgnore: [/.*xss.*\.spec\.ts/, /.*theme.*\.spec\.ts/],
        use: { ...devices['Desktop Firefox'] },
      },
      {
        name: 'webkit',
        testIgnore: [/.*xss.*\.spec\.ts/, /.*theme.*\.spec\.ts/],
        use: { ...devices['Desktop Safari'] },
      },
    ] : []),
  ],

  // Auto-start development server
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:3000',
    reuseExistingServer: !isCI,
    timeout: 120 * 1000,
    cwd: '.', // Ensure command runs in correct directory (storefront)
  },
});
