"use client";

import { useCallback, useMemo, useState } from "react";
import Link from "next/link";
import { createPortal } from "react-dom";
import { OptimizedImage } from "@/components/common/OptimizedImage";
import { useCart } from "@/components/cart/CartContext";
import { ProductVariantProvider } from "@/components/product/ProductVariantProvider";
import { VariantSelector } from "@/components/product/VariantSelector";
import type { ListingProduct } from "@/components/product/ListingProducts";
import type { ProductWithVariants } from "@/lib/types/product-types";
import { getMainImage } from "@/lib/image-utils";
import { useThemeSettings } from "@/hooks/useThemeSettings";
import { useHydrated } from "@/hooks/useHydrated";
import { sanitizePlainText } from "@/lib/html-sanitizer";

type VariantValue = {
  id: number;
  value: string;
  option?: {
    id: number;
    name: string;
  } | null;
};

type Variant = {
  id: number;
  sku?: string | null;
  name?: string | null;
  is_active?: boolean;
  manage_stock?: boolean;
  stock_qty?: number | null;
  price?: number | null;
  compare_at_price?: number | null;
  values?: VariantValue[];
};

export type ThemedProductCardProps = {
  product: ListingProduct & {
    variants?: Variant[];
  };
  hrefBase?: string;
};

type VisionUi = {
  cardTone: string;
  mediaTone: string;
  contentTone: string;
  promoTone: string;
  unavailableTone: string;
  modelsTone: string;
  titleTone: string;
  descTone: string;
  priceTone: string;
};

const VISION_UI: VisionUi = {
  cardTone: "rounded-2xl shadow-sm",
  mediaTone: "",
  contentTone: "bg-[var(--theme-card-bg,#ffffff)]",
  promoTone: "",
  unavailableTone: "",
  modelsTone: "",
  titleTone: "",
  descTone: "",
  priceTone: "",
};

function formatEuro(value: number): string {
  return `${value.toFixed(2).replace(".", ",")} EUR`;
}

function buildFallbackVariantProduct(
  product: ThemedProductCardProps["product"],
  variants: Variant[],
  slug?: string
): ProductWithVariants {
  const normalizedVariants = variants
    .filter((variant) => variant && (variant.is_active ?? true))
    .map((variant, index) => {
      const label = variant.name?.trim() || `Variante ${index + 1}`;
      const stockQty =
        typeof variant.stock_qty === "number"
          ? variant.stock_qty
          : variant.manage_stock === false
            ? 999
            : 1;

      return {
        ...variant,
        name: label,
        values:
          Array.isArray(variant.values) && variant.values.length > 0
            ? variant.values
            : [
                {
                  id: index + 1,
                  value: label,
                  option: { id: 1, name: "Modele" },
                },
              ],
        stock_qty: stockQty,
      };
    });

  return {
    id: product.id,
    name:
      product.translations?.[0]?.name ||
      product.name ||
      "Produit",
    slug:
      product.translations?.[0]?.slug ||
      slug ||
      String(product.id),
    price:
      typeof product.price === "number"
        ? product.price
        : undefined,
    compare_at_price:
      typeof product.compare_at_price === "number"
        ? product.compare_at_price
        : null,
    has_variants: true,
    images: Array.isArray(product.images) ? product.images : [],
    translations: Array.isArray(product.translations)
      ? product.translations
      : [],
    variants: normalizedVariants as ProductWithVariants["variants"],
  };
}

export function ProductCard({
  product,
  hrefBase = "/products",
}: ThemedProductCardProps) {
  const themeSettings = useThemeSettings();
  const isHydrated = useHydrated();
  const { addItem } = useCart();
  const ui = VISION_UI;
  const [variantProduct, setVariantProduct] = useState<ProductWithVariants | null>(
    null
  );
  const [isVariantModalOpen, setIsVariantModalOpen] = useState(false);
  const [isVariantLoading, setIsVariantLoading] = useState(false);
  const [variantError, setVariantError] = useState<string | null>(null);

  const t = product.translations?.[0];
  const shortDescription = sanitizePlainText(t?.short_description);
  const slug = t?.slug;
  const locale =
    (t as { locale?: string } | undefined)?.locale ||
    "fr";
  const href = slug ? `${hrefBase}/${slug}` : "#";
  const image = getMainImage(product);
  const productName =
    t?.name ||
    product.name ||
    "Produit";
  const simplePrice =
    typeof product.price === "number"
      ? product.price
      : 0;
  const simpleCompareAt =
    typeof product.compare_at_price === "number" &&
    product.compare_at_price > simplePrice
      ? product.compare_at_price
      : undefined;

  const productCardStyle = isHydrated ? themeSettings.productCardStyle : "bordered";
  const productHoverEffect = isHydrated ? themeSettings.productHoverEffect : "lift";
  const borderRadius = isHydrated ? themeSettings.borderRadius : "12px";
  const productBadgesDisplay = isHydrated ? themeSettings.productBadgesDisplay : "block";
  const productQuickAddDisplay = isHydrated ? themeSettings.productQuickAddDisplay : "block";
  const productImageRatio = isHydrated ? themeSettings.productImageRatio : "100%";
  const productBadgeRadius = isHydrated ? themeSettings.productBadgeRadius : "9999px";
  const productTitleLines = isHydrated ? themeSettings.productTitleLines : 2;
  const productPriceSize = isHydrated ? themeSettings.productPriceSize : "0.9375rem";
  const productQuickAddStyle = isHydrated ? themeSettings.productQuickAddStyle : "button";
  const titleClampClass = productTitleLines === 1 ? "line-clamp-1" : "line-clamp-2";

  const variants: Variant[] = useMemo(
    () => (Array.isArray(product.variants) ? product.variants : []),
    [product.variants]
  );
  const hasStructuredVariants = variants.length > 0;

  const hasVariants =
    !!product.has_variants ||
    product.type === "variant" ||
    hasStructuredVariants;

  let priceLabel = "Prix a definir";
  let compareAtLabel: string | null = null;
  let onSale = false;
  let showNonDispoBadge = false;

  if (hasVariants && hasStructuredVariants) {
    const active = variants.filter((v) => v && (v.is_active ?? true));

    const prices = active
      .map((v) => (typeof v.price === "number" && v.price > 0 ? v.price : null))
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
      priceLabel = `A partir de ${formatEuro(minPrice)}`;

      if (comparePrices.length) {
        const minCompare = Math.min(...comparePrices);
        if (minCompare > minPrice) {
          compareAtLabel = formatEuro(minCompare);
          onSale = true;
        }
      }
    } else {
      priceLabel = "Voir les variantes";
    }

    showNonDispoBadge = !anyInStock;
  } else {
    const price = typeof product.price === "number" && product.price > 0 ? product.price : 0;

    const compareAt =
      typeof product.compare_at_price === "number" && product.compare_at_price > 0
        ? product.compare_at_price
        : null;

    if (price > 0) {
      priceLabel = formatEuro(price);
    }

    if (compareAt && compareAt > price && price > 0) {
      compareAtLabel = formatEuro(compareAt);
      onSale = true;
    }

    showNonDispoBadge = typeof product.stock_qty === "number" && product.stock_qty <= 0;
  }

  const cardClasses = [
    "group flex flex-col overflow-hidden transition-all",
    productCardStyle === "minimal"
      ? "border-0"
      : productCardStyle === "bordered"
        ? "border border-[var(--theme-border-default,#e5e7eb)]"
        : productCardStyle === "shadow"
          ? "border-0 shadow-md"
          : productCardStyle === "elevated"
            ? "border-0 shadow-lg"
            : "border border-[var(--theme-border-default,#e5e7eb)]",
    productHoverEffect === "lift"
      ? "hover:-translate-y-1 hover:shadow-lg"
      : productHoverEffect === "scale"
        ? "hover:scale-[1.02]"
        : productHoverEffect === "shadow"
          ? "hover:shadow-xl"
          : "hover:border-[var(--theme-border-hover,#111827)] hover:shadow-sm",
    ui.cardTone,
  ]
    .filter(Boolean)
    .join(" ");

  const quickAddDisabled =
    showNonDispoBadge ||
    (!hasVariants && simplePrice <= 0);

  const loadVariantProduct = useCallback(async (): Promise<ProductWithVariants> => {
    if (!slug) {
      throw new Error("Missing product slug");
    }

    const response = await fetch(
      `/api/products/${encodeURIComponent(slug)}?locale=${encodeURIComponent(locale)}`,
      {
        cache: "no-store",
      }
    );

    if (!response.ok) {
      throw new Error("Failed to fetch product details");
    }

    const data = (await response.json()) as ProductWithVariants;

    if (!data || typeof data !== "object") {
      throw new Error("Invalid product details response");
    }

    return data;
  }, [slug, locale]);

  const closeVariantModal = useCallback(() => {
    setIsVariantModalOpen(false);
    setVariantError(null);
  }, []);

  const handleQuickAdd = useCallback(
    async (event: React.MouseEvent<HTMLButtonElement>) => {
      event.preventDefault();
      event.stopPropagation();

      if (quickAddDisabled) {
        return;
      }

      if (!hasVariants) {
        addItem({
          id: product.id,
          name: productName,
          price: simplePrice,
          oldPrice: simpleCompareAt,
          qty: 1,
          imageUrl: image || undefined,
        });
        return;
      }

      setIsVariantModalOpen(true);
      setVariantError(null);

      if (
        variantProduct?.id === product.id &&
        Array.isArray(variantProduct.variants) &&
        variantProduct.variants.length > 0
      ) {
        return;
      }

      setIsVariantLoading(true);
      try {
        const detailedProduct = await loadVariantProduct();
        const loadedVariants = Array.isArray(detailedProduct.variants)
          ? detailedProduct.variants
          : [];

        if (loadedVariants.length === 0) {
          throw new Error("No variants in details");
        }

        setVariantProduct(detailedProduct);
      } catch {
        const fallbackProduct = buildFallbackVariantProduct(product, variants, slug);
        const fallbackVariants = Array.isArray(fallbackProduct.variants)
          ? fallbackProduct.variants
          : [];

        if (fallbackVariants.length === 0) {
          setVariantProduct(null);
          setVariantError("Impossible de charger les declinaisons pour ce produit.");
          return;
        }

        setVariantProduct(fallbackProduct);
      } finally {
        setIsVariantLoading(false);
      }
    },
    [
      quickAddDisabled,
      hasVariants,
      addItem,
      productName,
      simplePrice,
      simpleCompareAt,
      image,
      variantProduct,
      loadVariantProduct,
      product,
      variants,
      slug,
    ]
  );

  const variantModal = isVariantModalOpen ? (
    <div
      className="fixed inset-0 z-[90] flex items-center justify-center bg-black/45 p-4"
      onClick={closeVariantModal}
    >
      <div
        className="w-full max-w-xl rounded-2xl border border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-card-bg,#ffffff)] p-5 shadow-2xl"
        onClick={(event) => event.stopPropagation()}
      >
        <div className="mb-4 flex items-start justify-between gap-3">
          <div>
            <h3 className="text-sm font-semibold text-[var(--theme-heading-color,#111827)]">
              Choisir une declinaison
            </h3>
            <p className="mt-1 text-xs text-[var(--theme-muted-color,#6b7280)]">
              {productName}
            </p>
          </div>
          <button
            type="button"
            className="inline-flex h-8 w-8 items-center justify-center rounded-full border border-[var(--theme-border-default,#e5e7eb)] text-sm text-[var(--theme-body-color,#374151)] hover:bg-[var(--theme-page-bg,#f6f6f7)]"
            onClick={closeVariantModal}
            aria-label="Fermer la modal"
          >
            ×
          </button>
        </div>

        {isVariantLoading && (
          <div className="py-8 text-center text-sm text-[var(--theme-muted-color,#6b7280)]">
            Chargement des declinaisons...
          </div>
        )}

        {!isVariantLoading && variantError && (
          <div className="rounded-lg border border-[var(--theme-error-color,#ef4444)]/30 bg-[var(--theme-error-bg,#fee2e2)] px-3 py-2 text-xs text-[var(--theme-error-color,#ef4444)]">
            {variantError}
          </div>
        )}

        {!isVariantLoading && variantProduct && (
          <ProductVariantProvider product={variantProduct}>
            <VariantSelector onAdded={closeVariantModal} />
          </ProductVariantProvider>
        )}
      </div>
    </div>
  ) : null;

  return (
    <>
      <Link
        href={href}
        className={cardClasses}
        style={{
          borderRadius,
          backgroundColor: "var(--theme-card-bg, #ffffff)",
        }}
      >
        <div
          className={`relative overflow-hidden ${ui.mediaTone}`.trim()}
          style={{
            paddingBottom: productImageRatio,
            backgroundColor: "var(--theme-page-bg, #f6f6f7)",
          }}
        >
          {image ? (
            <OptimizedImage
              src={image}
              alt={t?.name || ""}
              fill
              sizes="(max-width: 768px) 50vw, (max-width: 1200px) 33vw, 25vw"
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              fallback={
                <div className="absolute inset-0 flex items-center justify-center text-xxxs text-[var(--theme-muted-color,#6b7280)]">
                  Aucune image
                </div>
              }
            />
          ) : (
            <div className="absolute inset-0 flex items-center justify-center text-xxxs text-[var(--theme-muted-color,#6b7280)]">
              Aucune image
            </div>
          )}

          {productBadgesDisplay !== "none" && onSale && (
            <div
              className={`absolute left-1.5 top-1.5 px-2 py-0.5 text-xxxs ${ui.promoTone}`.trim()}
              style={{
                backgroundColor: "var(--theme-promo-bg, #fbbf24)",
                color: "var(--theme-promo-text, #92400e)",
                borderRadius: productBadgeRadius,
              }}
            >
              Promo
            </div>
          )}

          {productBadgesDisplay !== "none" && showNonDispoBadge && (
            <div
              className={`absolute right-1.5 top-1.5 border px-2 py-0.5 text-xxxs ${ui.unavailableTone}`.trim()}
              style={{
                backgroundColor: "var(--theme-error-bg, #fee2e2)",
                color: "var(--theme-error-color, #ef4444)",
                borderColor: "var(--theme-border-default, #e5e7eb)",
                borderRadius: productBadgeRadius,
              }}
            >
              Non dispo
            </div>
          )}

          {productBadgesDisplay !== "none" && hasVariants && hasStructuredVariants && (
            <div
              className={`absolute left-1.5 bottom-1.5 border px-2 py-0.5 text-xxxs ${ui.modelsTone}`.trim()}
              style={{
                backgroundColor: "var(--theme-variant-badge-bg, #ffffff)",
                color: "var(--theme-variant-badge-text, #374151)",
                borderColor: "var(--theme-border-default, #e5e7eb)",
                borderRadius: productBadgeRadius,
              }}
            >
              Plusieurs modeles
            </div>
          )}

          {productQuickAddDisplay !== "none" &&
            productQuickAddStyle === "icon" && (
              <button
                type="button"
                onClick={handleQuickAdd}
                disabled={quickAddDisabled}
                className={`theme-button-shape absolute bottom-1.5 right-1.5 flex h-8 w-8 items-center justify-center border text-sm font-bold transition ${
                  quickAddDisabled
                    ? "cursor-not-allowed opacity-50"
                    : "hover:brightness-95"
                }`}
                style={{
                  backgroundColor: "var(--theme-primary, #111827)",
                  borderColor: "var(--theme-primary, #111827)",
                  color: "var(--theme-button-primary-text, #ffffff)",
                }}
                aria-label={hasVariants ? "Choisir une declinaison" : "Ajout rapide"}
              >
                +
              </button>
            )}
        </div>

        <div className={`flex flex-col gap-0.5 px-3 py-2 ${ui.contentTone}`.trim()}>
          <div
            className={`${titleClampClass} text-body-14 font-medium text-[var(--theme-heading-color,#111827)] ${ui.titleTone}`.trim()}
          >
            {t?.name || "Produit sans nom"}
          </div>

          {shortDescription && (
            <div
              className={`line-clamp-2 min-h-[30px] text-xs text-[var(--theme-muted-color,#6b7280)] ${ui.descTone}`.trim()}
            >
              {shortDescription}
            </div>
          )}

          <div className="mt-0.5 flex items-baseline gap-1">
            <span
              className={`text-body-14 font-semibold text-[var(--theme-heading-color,#111827)] ${ui.priceTone}`.trim()}
              style={{ fontSize: productPriceSize }}
            >
              {priceLabel}
            </span>

            {!hasVariants && compareAtLabel && onSale && (
              <span className="text-xs text-[var(--theme-muted-color,#6b7280)] line-through">
                {compareAtLabel}
              </span>
            )}
          </div>

          {productQuickAddDisplay !== "none" &&
            productQuickAddStyle === "button" && (
              <button
                type="button"
                onClick={handleQuickAdd}
                disabled={quickAddDisabled}
                className={`theme-button-shape mt-2 inline-flex w-full items-center justify-center border px-2 py-1.5 text-xs font-semibold transition ${
                  quickAddDisabled
                    ? "cursor-not-allowed opacity-50"
                    : "hover:brightness-95"
                }`}
                style={{
                  backgroundColor: "var(--theme-primary, #111827)",
                  borderColor: "var(--theme-primary, #111827)",
                  color: "var(--theme-button-primary-text, #ffffff)",
                }}
              >
                {hasVariants ? "Choisir une declinaison" : "Ajout rapide"}
              </button>
            )}
        </div>
      </Link>

      {isHydrated && variantModal
        ? createPortal(variantModal, document.body)
        : null}
    </>
  );
}
