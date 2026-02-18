"use client";

import { useContext } from "react";
import { ProductVariantContext } from "./ProductVariantProvider";

export function useProductVariant() {
  const ctx = useContext(ProductVariantContext);
  if (!ctx) {
    throw new Error(
      "useProductVariant must be used within a ProductVariantProvider"
    );
  }
  return ctx;
}

export function useOptionalProductVariant() {
  return useContext(ProductVariantContext);
}
