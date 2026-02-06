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

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type ProductCardComponent = ComponentType<any>;

// Cache for dynamically loaded theme components - prevents recreation on each render
const themeComponentCache = new Map<string, ProductCardComponent>();

function getThemedComponent(themePath: string): ProductCardComponent {
  // Return cached component if available
  if (themeComponentCache.has(themePath)) {
    return themeComponentCache.get(themePath)!;
  }

  // Create and cache the dynamic component
  const DynamicComponent = dynamic(
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
      loading: LoadingPlaceholder,
      ssr: false,
    }
  );

  themeComponentCache.set(themePath, DynamicComponent);
  return DynamicComponent;
}

/**
 * Client-side Themed ProductCard - Loads the appropriate ProductCard
 * based on the theme path provided as a prop.
 * Uses component caching to prevent recreation on each render.
 */
export const ThemedProductCardClient = memo(function ThemedProductCardClient({
  themePath = "vision",
  ...props
}: Props) {
  const ProductCard = getThemedComponent(themePath);
  return <ProductCard {...props} />;
});
