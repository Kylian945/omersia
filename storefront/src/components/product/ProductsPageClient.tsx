"use client";

import { useState } from "react";
import { getProductsClient } from "@/lib/api-products-client";
import { ProductsClient } from "./ProductsClient";
import { Pagination } from "@/components/common/Pagination";
import type { ListingProduct } from "./ListingProducts";
import type { ProductsResponse } from "@/lib/types/product-types";

type ProductsPageClientProps = {
  initialData: ProductsResponse;
  themePath: string;
};

export function ProductsPageClient({ initialData, themePath }: ProductsPageClientProps) {
  const [data, setData] = useState<ProductsResponse>(initialData);
  const [isLoading, setIsLoading] = useState(false);

  const currentPage = data.current_page || 1;
  const lastPage = data.last_page || 1;
  const total = data.total || 0;
  const perPage = data.per_page || 20;
  const from = data.from || null;
  const to = data.to || null;

  const handlePageChange = async (page: number) => {
    setIsLoading(true);
    try {
      const newData = await getProductsClient("fr", page);
      if (newData) {
        setData(newData);
      }
    } finally {
      setIsLoading(false);
      // Scroll to top
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  };

  const products = (data.data || []) as ListingProduct[];

  return (
    <>
      {isLoading && (
        <div className="fixed inset-0 bg-white/80 z-50 flex items-center justify-center">
          <div className="text-center">
            <div className="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-black border-r-transparent"></div>
            <p className="mt-2 text-sm text-neutral-600">Chargement...</p>
          </div>
        </div>
      )}

      <ProductsClient products={products} themePath={themePath} />

      {products.length > 0 && (
        <Pagination
          currentPage={currentPage}
          lastPage={lastPage}
          total={total}
          perPage={perPage}
          from={from}
          to={to}
          onPageChange={handlePageChange}
        />
      )}
    </>
  );
}
