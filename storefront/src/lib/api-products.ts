import { cache } from "react";
import { apiJson } from "./api-http";
import { ProductsResponse, ProductDetailResponse } from "./types/product-types";

type GetProductsOptions = {
  locale?: string;
  page?: number;
  category?: string;
  limit?: number;
};

export async function getProducts(
  localeOrOptions: string | GetProductsOptions = "fr",
  page = 1
): Promise<ProductsResponse | null> {
  // Support both old signature (locale, page) and new signature (options object)
  let locale = "fr";
  let category: string | undefined;
  let limit: number | undefined;

  if (typeof localeOrOptions === "string") {
    locale = localeOrOptions;
  } else {
    locale = localeOrOptions.locale ?? "fr";
    page = localeOrOptions.page ?? 1;
    category = localeOrOptions.category;
    limit = localeOrOptions.limit;
  }

  const params = new URLSearchParams({
    locale,
    page: String(page),
  });

  if (category) {
    params.set("category", category);
  }

  if (limit) {
    params.set("limit", String(limit));
  }

  const { res, data } = await apiJson<ProductsResponse>(`/products?${params.toString()}`, {
    cache: "no-store",
  });

  if (!res.ok) return null;
  return data;
}

/**
 * Get a product by slug
 * Wrapped with React cache() for per-request deduplication
 */
export const getProductBySlug = cache(async (slug: string, locale = "fr"): Promise<ProductDetailResponse | null> => {
  const safeSlug = encodeURIComponent(slug);
  const { res, data } = await apiJson<ProductDetailResponse>(
    `/products/${safeSlug}?locale=${locale}`,
    {
      cache: "no-store",
    }
  );

  if (!res.ok) return null;
  return data;
});
