"use client";

import { ComponentType, memo, useEffect, useState } from "react";
import type { ListingProduct } from "./ListingProducts";
import { getThemeComponent } from "@/lib/theme-components";

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
  <div className="aspect-4/5 bg-[var(--theme-page-bg,#f6f6f7)] animate-pulse rounded-2xl" />
);

type ProductCardComponent = ComponentType<Props>;

const componentCache = new Map<string, ProductCardComponent>();

function normalizeThemePath(themePath?: string): string {
  const normalizedTheme = themePath?.trim();

  if (
    !normalizedTheme ||
    normalizedTheme === "default" ||
    normalizedTheme === "null" ||
    normalizedTheme === "undefined"
  ) {
    return "vision";
  }

  return normalizedTheme;
}

async function loadProductCard(themePath?: string): Promise<ProductCardComponent> {
  const normalizedTheme = normalizeThemePath(themePath);

  if (componentCache.has(normalizedTheme)) {
    return componentCache.get(normalizedTheme)!;
  }

  const component = (await getThemeComponent(
    "product/ProductCard",
    normalizedTheme
  )) as ProductCardComponent;

  componentCache.set(normalizedTheme, component);

  return component;
}

/**
 * Client-side Themed ProductCard - Loads the appropriate ProductCard
 * based on the theme path provided as a prop.
 * Uses lazy loading + cache to avoid re-importing components.
 */
export const ThemedProductCardClient = memo(function ThemedProductCardClient({
  themePath = "vision",
  ...props
}: Props) {
  const normalizedTheme = normalizeThemePath(themePath);
  const [ProductCard, setProductCard] = useState<ProductCardComponent | null>(
    () => componentCache.get(normalizedTheme) ?? null
  );

  useEffect(() => {
    let isMounted = true;

    loadProductCard(normalizedTheme)
      .then((component) => {
        if (isMounted) {
          setProductCard(() => component);
        }
      })
      .catch(async () => {
        if (!isMounted) return;

        try {
          const fallback = await loadProductCard("vision");
          if (isMounted) {
            setProductCard(() => fallback);
          }
        } catch {
          if (isMounted) {
            setProductCard(null);
          }
        }
      });

    return () => {
      isMounted = false;
    };
  }, [normalizedTheme]);

  if (!ProductCard) {
    return <LoadingPlaceholder />;
  }

  return <ProductCard {...props} />;
});
