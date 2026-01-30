import { ProductsResponse } from "./types/product-types";

/**
 * Client-side function to fetch products via Next.js API route
 * This avoids exposing the API key in the browser
 */
export async function getProductsClient(
  locale = "fr",
  page = 1
): Promise<ProductsResponse | null> {
  try {
    const response = await fetch(
      `/api/products?locale=${locale}&page=${page}`,
      {
        cache: "no-store",
      }
    );

    if (!response.ok) {
      console.error("Failed to fetch products:", response.statusText);
      return null;
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error("Error fetching products:", error);
    return null;
  }
}
