import { unstable_noStore as noStore } from "next/cache";
import { apiJson } from "./api-http";
import { MenuResponse } from "./types/menu-types";

export async function getMenu(slug: string, locale = "fr"): Promise<MenuResponse> {
  noStore();
  const safeSlug = encodeURIComponent(slug);
  const { res, data } = await apiJson<MenuResponse>(
    `/menus/${safeSlug}?locale=${locale}`,
    {
      cache: "no-store",
    }
  );

  if (!res.ok) return null;
  return data;
}
