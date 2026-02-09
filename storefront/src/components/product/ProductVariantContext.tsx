"use client";

import {
  createContext,
  useContext,
  useMemo,
  useState,
  ReactNode,
} from "react";
import { getMainImageUrl } from "@/lib/image-utils";
import {
  ProductWithVariants,
  Variant,
  SelectedOptions,
} from "@/lib/types/product-types";

type ProductVariantContextType = {
  // options
  options: Record<string, string[]>;
  optionNames: string[];
  // sélection
  selected: SelectedOptions;
  toggleOption: (name: string, value: string) => void;
  // qty
  qty: number;
  setQty: (qty: number) => void;
  matchingVariant: Variant | null;
  currentPrice: number | null;
  currentCompareAt: number | null;
  currentInStock: boolean;
  variantLabel: string | null;
  // infos produit
  productId: number;
  productName: string;
  imageUrl: string | null;
};

const ProductVariantContext = createContext<ProductVariantContextType | null>(
  null
);

export function ProductVariantProvider({
  product,
  children,
}: {
  product: ProductWithVariants;
  children: ReactNode;
}) {
  const variants = useMemo(
    () => (product.variants || []).filter((v) => v && (v.is_active ?? true)),
    [product.variants]
  );

  const [selected, setSelected] = useState<SelectedOptions>({});
  const [qty, setQty] = useState(1);

  const { options, optionNames } = useMemo(() => {
    const map = new Map<string, Set<string>>();

    for (const v of variants) {
      (v.values || []).forEach((vv) => {
        const name = vv.option?.name;
        if (!name) return;
        if (!map.has(name)) map.set(name, new Set());
        map.get(name)!.add(vv.value);
      });
    }

    const obj: Record<string, string[]> = {};
    for (const [name, set] of map.entries()) {
      obj[name] = Array.from(set);
    }

    return {
      options: obj,
      optionNames: Object.keys(obj),
    };
  }, [variants]);

  const matchingVariant = useMemo(() => {
    if (!variants.length) return null;

    if (!optionNames.length) {
      return variants[0] || null;
    }

    if (!optionNames.every((name) => selected[name])) {
      return null;
    }

    return (
      variants.find((v) =>
        optionNames.every((name) =>
          (v.values || []).some(
            (vv) =>
              vv.option?.name === name &&
              vv.value === selected[name]
          )
        )
      ) || null
    );
  }, [variants, optionNames, selected]);

  const currentPrice =
    typeof matchingVariant?.price === "number"
      ? matchingVariant.price
      : null;

  const currentCompareAt =
    typeof matchingVariant?.compare_at_price === "number"
      ? matchingVariant.compare_at_price
      : null;

  const currentInStock = (() => {
    if (!matchingVariant) return false;
    if (matchingVariant.manage_stock === false) return true;
    if (typeof matchingVariant.stock_qty === "number") {
      return matchingVariant.stock_qty > 0;
    }
    return false;
  })();

  const variantLabel =
    matchingVariant &&
    (matchingVariant.name ||
      (matchingVariant.values || [])
        .map((v) => v.value)
        .join(" / "));

  const productName = product.translations?.[0]?.name || "Produit";
  const imageUrl = getMainImageUrl(product);

  const toggleOption = (name: string, value: string) => {
    setSelected((prev) => ({
      ...prev,
      [name]: prev[name] === value ? "" : value,
    }));
  };

  const value: ProductVariantContextType = {
    options,
    optionNames,
    selected,
    toggleOption,
    qty,
    setQty,
    matchingVariant,
    currentPrice,
    currentCompareAt,
    currentInStock,
    variantLabel: variantLabel || null,
    productId: product.id,
    productName,
    imageUrl,
  };

  return (
    <ProductVariantContext.Provider value={value}>
      {children}
    </ProductVariantContext.Provider>
  );
}

export function useProductVariant() {
  const ctx = useContext(ProductVariantContext);
  if (!ctx) {
    throw new Error(
      "useProductVariant must be used within a ProductVariantProvider"
    );
  }
  return ctx;
}
