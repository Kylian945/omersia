const nextJest = require('next/jest')

const createJestConfig = nextJest({
  // Provide the path to your Next.js app to load next.config.js and .env files in your test environment
  dir: './',
})

// Jest config
// Note: We use testPathPattern in npm scripts instead of projects for better Next.js compatibility
const customJestConfig = {
  coverageProvider: 'v8',
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['<rootDir>/jest.setup.js'],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
  },

  // Transform MSW modules
  transformIgnorePatterns: [
    'node_modules/(?!(msw)/)',
  ],

  // Test file patterns
  testMatch: [
    '**/__tests__/**/*.test.[jt]s?(x)',
    '**/?(*.)+(spec|test).[jt]s?(x)',
  ],

  // Exclude Playwright E2E tests
  testPathIgnorePatterns: [
    '/node_modules/',
    '/.next/',
    '/tests/e2e/',
    '/e2e/',
  ],

  // Coverage thresholds
  // TODO: Increase coverage thresholds as more tests are added
  coverageThreshold: {
    global: {
      branches: 35,   // Current: 36%
      functions: 9,    // Current: 9.54%
      lines: 2.5,     // Current: 2.96%
      statements: 2.5, // Current: 2.96%
    },
  },

  // Coverage collection
  collectCoverageFrom: [
    'src/**/*.{js,jsx,ts,tsx}',
    '!src/**/*.d.ts',
    '!src/**/*.stories.{js,jsx,ts,tsx}',
    '!src/**/__tests__/**',
  ],

  coveragePathIgnorePatterns: [
    '/node_modules/',
    '/.next/',
    '/coverage/',
  ],
}

// createJestConfig is exported this way to ensure that next/jest can load the Next.js config which is async
module.exports = createJestConfig(customJestConfig)
