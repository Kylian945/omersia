import type { MetadataRoute } from "next";
import { getProducts } from "@/lib/api-products";
import { getCategories } from "@/lib/api-categories";

/**
 * DCA-005: Sitemap dynamique pour SEO
 * Génère automatiquement les URLs des produits et catégories
 * @see https://nextjs.org/docs/app/api-reference/file-conventions/metadata/sitemap
 */
export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = process.env.NEXT_PUBLIC_FRONTEND_URL || "http://localhost:8000";

  // Pages statiques
  const staticPages: MetadataRoute.Sitemap = [
    {
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: "daily",
      priority: 1,
    },
    {
      url: `${baseUrl}/products`,
      lastModified: new Date(),
      changeFrequency: "daily",
      priority: 0.9,
    },
  ];

  // Récupérer les produits et catégories
  const [productsResponse, categories] = await Promise.all([
    getProducts({ locale: "fr", page: 1, limit: 1000 }),
    getCategories("fr"),
  ]);

  // Pages produits
  const productPages: MetadataRoute.Sitemap = (productsResponse?.products || []).map((product) => ({
    url: `${baseUrl}/products/${product.slug}`,
    lastModified: new Date(),
    changeFrequency: "weekly" as const,
    priority: 0.8,
  }));

  // Pages catégories
  const categoryPages: MetadataRoute.Sitemap = categories.map((category) => ({
    url: `${baseUrl}/categories/${category.slug}`,
    lastModified: new Date(),
    changeFrequency: "weekly" as const,
    priority: 0.7,
  }));

  return [...staticPages, ...productPages, ...categoryPages];
}
