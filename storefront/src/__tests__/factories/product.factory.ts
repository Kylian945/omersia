/**
 * Test Data Factory - Product
 * Follows Laravel factory pattern for consistent test data
 */

export interface ProductFactory {
  id: number
  name: string
  slug: string
  description: string
  price: number
  currency: string
  images: Array<{ url: string; alt: string }>
  inStock: boolean
  category?: {
    id: number
    name: string
    slug: string
  }
}

let productIdCounter = 1

export function createProduct(overrides?: Partial<ProductFactory>): ProductFactory {
  const id = productIdCounter++

  return {
    id,
    name: `Test Product ${id}`,
    slug: `test-product-${id}`,
    description: `This is a test product description for product ${id}`,
    price: 99.99,
    currency: 'EUR',
    images: [
      {
        url: `/images/product-${id}.jpg`,
        alt: `Test Product ${id} Image`,
      },
    ],
    inStock: true,
    category: {
      id: 1,
      name: 'Test Category',
      slug: 'test-category',
    },
    ...overrides,
  }
}

export function createProducts(count: number, overrides?: Partial<ProductFactory>): ProductFactory[] {
  return Array.from({ length: count }, () => createProduct(overrides))
}

// Reset counter for test isolation
export function resetProductFactory() {
  productIdCounter = 1
}
