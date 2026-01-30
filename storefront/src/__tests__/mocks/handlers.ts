import { http, HttpResponse } from 'msw'
import { createProduct } from '../factories'

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8001/api/v1'

export const handlers = [
  // Products API
  http.get(`${API_URL}/products`, () => {
    return HttpResponse.json({
      data: [
        createProduct({ id: 1, name: 'Mock Product 1' }),
        createProduct({ id: 2, name: 'Mock Product 2' }),
      ],
    })
  }),

  http.get(`${API_URL}/products/:slug`, ({ params }) => {
    const { slug } = params
    return HttpResponse.json({
      data: createProduct({ slug: slug as string }),
    })
  }),

  // Add other API endpoints as needed
]
