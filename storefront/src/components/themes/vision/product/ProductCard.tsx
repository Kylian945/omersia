"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { OptimizedImage } from "@/components/common/OptimizedImage";
import type { ListingProduct } from "@/components/product/ListingProducts";
import { getMainImage } from "@/lib/image-utils";
import { useThemeSettings } from "@/hooks/useThemeSettings";

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

export function ProductCard({ product, hrefBase = "/products" }: Props) {
  const [mounted, setMounted] = useState(false);
  const themeSettings = useThemeSettings();

  useEffect(() => {
    setMounted(true);
  }, []);

  const t = product.translations?.[0];
  const slug = t?.slug;
  const href = slug ? `${hrefBase}/${slug}` : "#";
  const image = getMainImage(product);

  // Use theme settings only after client-side mount to avoid hydration mismatch
  const productCardStyle = mounted ? themeSettings.productCardStyle : 'bordered';
  const productHoverEffect = mounted ? themeSettings.productHoverEffect : 'lift';
  const borderRadius = mounted ? themeSettings.borderRadius : '12px';
  const productBadgesDisplay = mounted ? themeSettings.productBadgesDisplay : 'block';
  const productImageRatio = mounted ? themeSettings.productImageRatio : '100%';

  const variants: Variant[] = Array.isArray(product.variants)
    ? product.variants
    : [];

  const hasStructuredVariants = variants.length > 0;

  const hasVariants =
    !!product.has_variants ||
    product.type === "variant" ||
    hasStructuredVariants;

  // ---------- Prix & stock ----------
  let priceLabel = "Prix à définir";
  let compareAtLabel: string | null = null;
  let onSale = false;
  let showNonDispoBadge = false;

  if (hasVariants && hasStructuredVariants) {
    const active = variants.filter((v) => v && (v.is_active ?? true));

    const prices = active
      .map((v) =>
        typeof v.price === "number" && v.price > 0 ? v.price : null
      )
      .filter((v): v is number => v !== null);

    const comparePrices = active
      .map((v) =>
        typeof v.compare_at_price === "number" && v.compare_at_price > 0
          ? v.compare_at_price
          : null
      )
      .filter((v): v is number => v !== null);

    const anyInStock = active.some((v) => {
      if (v.manage_stock === false) return true;
      if (typeof v.stock_qty === "number") return v.stock_qty > 0;
      return true;
    });

    if (prices.length) {
      const minPrice = Math.min(...prices);
      priceLabel = `À partir de ${minPrice.toFixed(2).replace(".", ",")} €`;

      if (comparePrices.length) {
        const minCompare = Math.min(...comparePrices);
        if (minCompare > minPrice) {
          compareAtLabel = `${minCompare.toFixed(2).replace(".", ",")} €`;
          onSale = true;
        }
      }
    } else {
      priceLabel = "Voir les variantes";
    }

    showNonDispoBadge = !anyInStock;
  } else {
    // Produit simple
    const price =
      typeof product.price === "number" && product.price > 0
        ? product.price
        : 0;

    const compareAt =
      typeof product.compare_at_price === "number" &&
      product.compare_at_price! > 0
        ? product.compare_at_price
        : null;

    if (price > 0) {
      priceLabel = `${price.toFixed(2).replace(".", ",")} €`;
    } else {
      priceLabel = "Prix à définir";
    }

    if (compareAt && compareAt > price && price > 0) {
      compareAtLabel = `${compareAt.toFixed(2).replace(".", ",")} €`;
      onSale = true;
    }

    showNonDispoBadge =
      typeof product.stock_qty === "number" && product.stock_qty <= 0;
  }

  // Card style classes based on theme settings
  const cardClasses = [
    'group flex flex-col transition-all overflow-hidden',
    productCardStyle === 'minimal' ? 'border-0' :
    productCardStyle === 'bordered' ? 'border border-neutral-100' :
    productCardStyle === 'shadow' ? 'border-0 shadow-md' :
    productCardStyle === 'elevated' ? 'border-0 shadow-lg' : 'border border-neutral-100',

    productHoverEffect === 'lift' ? 'hover:shadow-lg hover:-translate-y-1' :
    productHoverEffect === 'scale' ? 'hover:scale-[1.02]' :
    productHoverEffect === 'shadow' ? 'hover:shadow-xl' : 'hover:border-neutral-300 hover:shadow-sm',
  ].filter(Boolean).join(' ');

  return (
    <Link
      href={href}
      className={cardClasses}
      style={{
        borderRadius,
        backgroundColor: 'var(--theme-card-bg, #ffffff)',
      }}
    >
      <div
        className="relative bg-neutral-50 overflow-hidden"
        style={{ paddingBottom: productImageRatio }}
      >
        {image ? (
          <OptimizedImage
            src={image}
            alt={t?.name || ""}
            fill
            sizes="(max-width: 768px) 50vw, (max-width: 1200px) 33vw, 25vw"
            className="object-cover transition-transform duration-300 group-hover:scale-105"
            fallback={
              <div className="absolute inset-0 flex items-center justify-center text-xxxs text-neutral-400">
                Aucune image
              </div>
            }
          />
        ) : (
          <div className="absolute inset-0 flex items-center justify-center text-xxxs text-neutral-400">
            Aucune image
          </div>
        )}

        {productBadgesDisplay !== 'none' && onSale && (
          <div className="absolute left-1.5 top-1.5 rounded-full bg-black/90 px-2 py-0.5 text-xxxs text-white">
            Promo
          </div>
        )}

        {productBadgesDisplay !== 'none' && showNonDispoBadge && (
          <div className="absolute right-1.5 top-1.5 rounded-full bg-white/90 px-2 py-0.5 text-xxxs text-neutral-800 border border-neutral-200">
            Non dispo
          </div>
        )}

        {productBadgesDisplay !== 'none' && hasVariants && hasStructuredVariants && (
          <div className="absolute left-1.5 bottom-1.5 rounded-full bg-indigo-100/90 px-2 py-0.5 text-xxxs text-indigo-900 border border-indigo-200">
            Plusieurs modèles
          </div>
        )}
      </div>

      <div className="flex flex-col gap-0.5 px-3 py-2">
        <div className="text-body-14 font-medium text-neutral-900 line-clamp-2">
          {t?.name || "Produit sans nom"}
        </div>

        {t?.short_description && (
          <div className="text-xs text-neutral-500 line-clamp-2 min-h-[30px]">
            {t.short_description}
          </div>
        )}

        <div className="mt-0.5 flex items-baseline gap-1">
          <span className="text-body-14 font-semibold text-neutral-900">
            {priceLabel}
          </span>

          {!hasVariants && compareAtLabel && onSale && (
            <span className="text-xs text-neutral-400 line-through">
              {compareAtLabel}
            </span>
          )}
        </div>
      </div>
    </Link>
  );
}
