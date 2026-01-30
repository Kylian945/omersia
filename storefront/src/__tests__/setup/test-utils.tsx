import React, { ReactElement } from 'react'
import { render, RenderOptions } from '@testing-library/react'

/**
 * Test Utilities - Custom Render
 *
 * Provides a custom render function that wraps components with necessary providers.
 * Currently simplified for basic component testing.
 *
 * To add providers in the future:
 * - ThemeProvider (requires server-side setup)
 * - AuthProvider
 * - CartProvider
 * - etc.
 */

interface AllTheProvidersProps {
  children: React.ReactNode
}

function AllTheProviders({ children }: AllTheProvidersProps) {
  // For now, we just return children without additional providers
  // This can be extended as needed for integration tests
  return <>{children}</>
}

function customRender(
  ui: ReactElement,
  options?: Omit<RenderOptions, 'wrapper'>
) {
  return render(ui, { wrapper: AllTheProviders, ...options })
}

// Re-export everything from @testing-library/react
export * from '@testing-library/react'

// Override render method
export { customRender as render }
