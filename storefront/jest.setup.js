require('@testing-library/jest-dom')

// Note: MSW server setup is opt-in per test file to avoid ESM issues
// To use MSW in a test file, import and setup the server manually:
// import { server } from '@/__tests__/mocks/server'
// beforeAll(() => server.listen())
// afterEach(() => server.resetHandlers())
// afterAll(() => server.close())

// Polyfill HTMLFormElement.prototype.requestSubmit (jsdom defines it but throws "not implemented")
if (typeof HTMLFormElement !== 'undefined') {
  HTMLFormElement.prototype.requestSubmit = function (submitter) {
    if (submitter) {
      if (submitter.getAttribute('type') !== 'submit') {
        throw new TypeError('The specified element is not a submit button')
      }
      if (submitter.form !== this) {
        throw new DOMException('The specified element is not owned by this form element', 'NotFoundError')
      }
    }

    const event = new Event('submit', { bubbles: true, cancelable: true })
    Object.defineProperty(event, 'submitter', { value: submitter || null })

    this.dispatchEvent(event)
  }
}

// Mock Next.js router
jest.mock('next/navigation', () => ({
  useRouter() {
    return {
      push: jest.fn(),
      replace: jest.fn(),
      prefetch: jest.fn(),
      back: jest.fn(),
    }
  },
  useSearchParams() {
    return new URLSearchParams()
  },
  usePathname() {
    return '/'
  },
}))

// Mock environment variables
process.env.NEXT_PUBLIC_API_URL = 'http://localhost:8001/api/v1'
process.env.FRONT_API_KEY = 'test-api-key'
