/**
 * Example Integration Test - ProductCard
 * Demonstrates usage of test utilities infrastructure
 */

import { render, screen, waitFor } from '@/__tests__/setup/test-utils'
import { createProduct, resetProductFactory } from '@/__tests__/factories'

// This is an example test showing how to use the new test utilities
// In a real scenario, you would import the actual ProductCard component

// Mock ProductCard for demonstration purposes
const ProductCard = ({ product }: { product: any }) => (
  <div data-testid="product-card">
    <h2>{product.name}</h2>
    <p>{product.price} {product.currency}</p>
    <img src={product.images[0]?.url} alt={product.images[0]?.alt} />
    {product.inStock ? (
      <button>Add to Cart</button>
    ) : (
      <button disabled>Out of Stock</button>
    )}
  </div>
)

describe('ProductCard Integration (Example)', () => {
  beforeEach(() => {
    // Reset factory state between tests for isolation
    resetProductFactory()
  })

  it('should display product data using factory', () => {
    const mockProduct = createProduct({
      name: 'Amazing Test Product',
      price: 149.99,
      currency: 'EUR',
    })

    render(<ProductCard product={mockProduct} />)

    expect(screen.getByText('Amazing Test Product')).toBeInTheDocument()
    expect(screen.getByText(/149.99/)).toBeInTheDocument()
    expect(screen.getByText(/EUR/)).toBeInTheDocument()
  })

  it('should display add to cart button when in stock', () => {
    const product = createProduct({ inStock: true })

    render(<ProductCard product={product} />)

    const button = screen.getByRole('button', { name: /add to cart/i })
    expect(button).toBeEnabled()
  })

  it('should disable button when out of stock', () => {
    const product = createProduct({ inStock: false })

    render(<ProductCard product={product} />)

    const button = screen.getByRole('button', { name: /out of stock/i })
    expect(button).toBeDisabled()
  })

  it('should display product image with correct attributes', () => {
    const product = createProduct({
      name: 'Test Product',
      images: [
        { url: '/images/test-product.jpg', alt: 'Test Product Image' },
      ],
    })

    render(<ProductCard product={product} />)

    const image = screen.getByRole('img')
    expect(image).toHaveAttribute('src', '/images/test-product.jpg')
    expect(image).toHaveAttribute('alt', 'Test Product Image')
  })

  it('should render using custom test-utils render', () => {
    // The render function from test-utils can wrap components with providers
    const product = createProduct()

    render(<ProductCard product={product} />)

    // Verify the component is rendered correctly
    expect(screen.getByTestId('product-card')).toBeInTheDocument()
  })
})

describe('Product Factory', () => {
  beforeEach(() => {
    resetProductFactory()
  })

  it('should create products with auto-incrementing IDs', () => {
    const product1 = createProduct()
    const product2 = createProduct()
    const product3 = createProduct()

    expect(product1.id).toBe(1)
    expect(product2.id).toBe(2)
    expect(product3.id).toBe(3)
  })

  it('should allow custom overrides', () => {
    const product = createProduct({
      name: 'Custom Product',
      price: 999.99,
      inStock: false,
    })

    expect(product.name).toBe('Custom Product')
    expect(product.price).toBe(999.99)
    expect(product.inStock).toBe(false)
    // Other fields should have default values
    expect(product.slug).toContain('test-product')
    expect(product.currency).toBe('EUR')
  })

  it('should reset ID counter when resetProductFactory is called', () => {
    createProduct() // ID: 1
    createProduct() // ID: 2

    resetProductFactory()

    const product = createProduct()
    expect(product.id).toBe(1) // Reset to 1
  })
})
