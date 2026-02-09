import { apiJson } from "./api-http";
import { Page, PageResponse } from "./types/page-types";

export async function getPageBySlug(slug: string, locale = "fr"): Promise<Page | null> {
  const { res, data } = await apiJson<PageResponse>(`/pages/${slug}?locale=${locale}`, {
    cache: "no-store",
  });

  if (!res.ok || !data) return null;

  const layout = data.layout || data.blocks || data.content_json || { sections: [] };

  return {
    id: data.id,
    slug: data.slug,
    title: data.title,
    meta_title: data.meta_title,
    meta_description: data.meta_description,
    layout,
  };
}
