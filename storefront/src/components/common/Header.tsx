// app/components/common/Header.tsx
import { unstable_noStore as noStore } from "next/cache";
import { HeaderClient } from "./HeaderClient";
import { HeaderWrapper } from "./HeaderWrapper";
import { getMenu, getShopInfo } from "@/lib/api";
import { getThemeSettings } from "@/lib/api-theme";

export async function Header() {
  // Désactiver complètement le cache pour ce composant
  noStore();

  const menu = await getMenu("main");
  const shopInfo = await getShopInfo();
  const themeSettings = await getThemeSettings();

  return (
    <HeaderWrapper>
      <HeaderClient menu={menu} shopInfo={shopInfo} cartType={themeSettings.settings.cart?.cart_type || 'drawer'} />
    </HeaderWrapper>
  );
}
