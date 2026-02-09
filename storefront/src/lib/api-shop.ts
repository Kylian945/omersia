import { unstable_noStore as noStore } from "next/cache";
import { apiJson } from "./api-http";
import { ShopInfo } from "./types/menu-types";

export async function getShopInfo(): Promise<ShopInfo> {
  noStore();
  const { data } = await apiJson<ShopInfo>("/shop/info", {
    cache: "no-store",
  });

  return data || {
    name: "Omersia",
    display_name: "Omersia",
    logo_url: null,
  };
}
