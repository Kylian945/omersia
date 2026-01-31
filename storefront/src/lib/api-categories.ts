import { apiJson } from "./api-http";
import { Category, CategoriesResponse } from "./types/category-types";
import { logger } from "./logger";

/**
 * Récupère toutes les catégories actives
 */
export async function getCategories(
  locale = "fr",
  parentOnly = false
): Promise<Category[]> {
  const params = new URLSearchParams({
    locale,
  });

  if (parentOnly) {
    params.append("parent_only", "true");
  }

  const { res, data } = await apiJson<CategoriesResponse>(
    `/categories?${params.toString()}`,
    {
      cache: "no-store", // Cache 5 minutes
    }
  );

  if (!res.ok) {
    logger.warn("Categories fetch failed:", res.status, res.url);
    return [];
  }

  return data?.categories || [];
}

export async function getCategoryBySlug(slug: string, locale = "fr"): Promise<Category | null> {
  const safeSlug = encodeURIComponent(slug);
  const { res, data } = await apiJson<Category>(
    `/categories/${safeSlug}?locale=${encodeURIComponent(locale)}`,
    {
      cache: "no-store",
    }
  );

  if (!res.ok) {
    logger.warn("Category fetch failed:", res.status, res.url);
    return null;
  }

  return data;
}
