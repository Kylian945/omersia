"use client";

import type { ReactNode } from "react";

type ListingProductsClientProps = {
  children?: ReactNode;
  emptyMessage?: string;
  isEmpty?: boolean;
};

export function ListingProductsClient({
  children,
  emptyMessage = "Aucun produit ne correspond à vos filtres.",
  isEmpty = false,
}: ListingProductsClientProps) {
  if (isEmpty) {
    return (
      <div className="mt-4 text-xs text-neutral-500">
        {emptyMessage}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
      {children}
    </div>
  );
}
