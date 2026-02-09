"use client";

import dynamic from "next/dynamic";
import { ComponentType, memo } from "react";
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
  themePath?: string;
};

// Loading placeholder component
const LoadingPlaceholder = () => (
  <div className="aspect-4/5 bg-neutral-100 animate-pulse rounded-2xl" />
);

type ProductCardComponent = ComponentType<Props>;

const VisionProductCard = dynamic<Props>(
  async () =>
    import("@/components/themes/vision/product/ProductCard").then(
      (mod) => mod.ProductCard as ProductCardComponent
    ),
  {
    loading: LoadingPlaceholder,
    ssr: false,
  }
);

const productCardByTheme: Record<string, ProductCardComponent> = {
  vision: VisionProductCard,
};

/**
 * Client-side Themed ProductCard - Loads the appropriate ProductCard
 * based on the theme path provided as a prop.
 * Uses component caching to prevent recreation on each render.
 */
export const ThemedProductCardClient = memo(function ThemedProductCardClient({
  themePath = "vision",
  ...props
}: Props) {
  const ProductCard = productCardByTheme[themePath] ?? VisionProductCard;
  return <ProductCard {...props} />;
});
