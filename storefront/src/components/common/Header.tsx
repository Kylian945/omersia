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

  // Paralléliser les fetches pour réduire le temps de chargement
  const [menu, shopInfo, themeSettings] = await Promise.all([
    getMenu("main"),
    getShopInfo(),
    getThemeSettings(),
  ]);

  return (
    <HeaderWrapper>
      <HeaderClient menu={menu} shopInfo={shopInfo} cartType={themeSettings.settings.cart?.cart_type || 'drawer'} />
    </HeaderWrapper>
  );
}
