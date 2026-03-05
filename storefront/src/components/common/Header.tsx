// app/components/common/Header.tsx
import { unstable_noStore as noStore } from "next/cache";
import { HeaderClient } from "./HeaderClient";
import { HeaderWrapper } from "./HeaderWrapper";
import { getMenu } from "@/lib/api-menu";
import { getShopInfo } from "@/lib/api-shop";
import { getThemeSettings } from "@/lib/api-theme";

export async function Header() {
  // Désactiver complètement le cache pour ce composant
  noStore();

  const [primaryMenu, shopInfo, themeSettings] = await Promise.all([
    getMenu("header"),
    getShopInfo(),
    getThemeSettings(),
  ]);

  // Compat: certains environnements utilisent encore le slug "main" pour le header.
  const menu = primaryMenu ?? await getMenu("main");

  return (
    <HeaderWrapper>
      <HeaderClient menu={menu} shopInfo={shopInfo} cartType={themeSettings.settings.cart?.cart_type || 'drawer'} />
    </HeaderWrapper>
  );
}
