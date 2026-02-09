import { getThemeSettings } from "@/lib/api-theme";
import { getThemeComponent } from "@/lib/theme-components";
import type { ListingProduct } from "./ListingProducts";

type Variant = {
  id: number;
  is_active?: boolean;
  manage_stock?: boolean;
  stock_qty?: number | null;
  price?: number | null;
  compare_at_price?: number | null;
};

type Props = {
  product: ListingProduct & {
    variants?: Variant[];
  };
  hrefBase?: string;
};

/**
 * Themed ProductCard - Server Component that loads the appropriate
 * ProductCard implementation based on the active theme
 */
export async function ThemedProductCard(props: Props) {
  // Fetch theme settings to get the component_path
  const theme = await getThemeSettings();

  // Dynamically load the ProductCard component from the active theme
  const ProductCard = await getThemeComponent(
    "product/ProductCard",
    theme.component_path
  );

  // Render the theme-specific ProductCard
  return <ProductCard {...props} />;
}
