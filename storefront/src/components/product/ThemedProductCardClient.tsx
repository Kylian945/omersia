"use client";

import dynamic from "next/dynamic";
import { ComponentType, useMemo } from "react";
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

/**
 * Client-side Themed ProductCard - Loads the appropriate ProductCard
 * based on the theme path provided as a prop
 */
export function ThemedProductCardClient({ themePath = "vision", ...props }: Props) {
  // Dynamically load the ProductCard component based on theme
  const ProductCard: ComponentType<Omit<Props, "themePath">> = useMemo(() => {
    return dynamic(
      () =>
        import(`@/components/themes/${themePath}/product/ProductCard`)
          .then((mod) => mod.ProductCard)
          .catch(() =>
            // Fallback to vision theme if the theme component doesn't exist
            import(`@/components/themes/vision/product/ProductCard`).then(
              (mod) => mod.ProductCard
            )
          ),
      {
        loading: () => <div className="aspect-4/5 bg-neutral-100 animate-pulse rounded-2xl" />,
        ssr: false,
      }
    );
  }, [themePath]);

  return <ProductCard {...props} />;
}
