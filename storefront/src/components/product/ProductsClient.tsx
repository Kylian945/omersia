"use client";

import { useMemo, useState, useEffect } from "react";
import { FilterPanel } from "@/components/category/FilterPanel";
import { ListingProductsClient } from "@/components/product/ListingProductsClient";
import { ThemedProductCardClient } from "@/components/product/ThemedProductCardClient";
import type { ListingProduct } from "@/components/product/ListingProducts";
import { Button } from "@/components/common/Button";

type Props = {
  products: ListingProduct[];
  themePath?: string;
};

export function ProductsClient({ products, themePath = "vision" }: Props) {
  // Bornes prix globales
  const priceBounds = useMemo(() => {
    const prices = products
      .map((p) => p.price ?? 0)
      .filter((v) => typeof v === "number" && v > 0);

    if (!prices.length) return { min: 0, max: 500 };

    const min = Math.floor(Math.min(...prices));
    const max = Math.ceil(Math.max(...prices));

    if (min === max) {
      return { min: 0, max: max || 500 };
    }

    return { min, max };
  }, [products]);

  const [filtersOpen, setFiltersOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [priceMin, setPriceMin] = useState<number>(priceBounds.min);
  const [priceMax, setPriceMax] = useState<number>(priceBounds.max);
  const [inStockOnly, setInStockOnly] = useState(false);
  const [sort, setSort] = useState<"featured" | "price-asc" | "price-desc" | "name-asc">(
    "featured"
  );

  // Réinitialiser les bornes de prix quand les produits changent (pagination)
  useEffect(() => {
    setPriceMin(priceBounds.min);
    setPriceMax(priceBounds.max);
  }, [priceBounds.min, priceBounds.max]);

  const filtered = useMemo(() => {
    const base = products.filter((p) => {
      const t = p.translations?.[0];
      const name = (t?.name || "").toLowerCase();
      const sku = (p.sku || "").toLowerCase();
      const q = search.toLowerCase().trim();

      if (q && !name.includes(q) && !sku.includes(q)) return false;

      const price = p.price ?? 0;
      if (price < priceMin) return false;
      if (price > priceMax) return false;

      if (inStockOnly && (p.stock_qty ?? 0) <= 0) return false;

      return true;
    });

    return base.sort((a, b) => {
      if (sort === "featured") return 0;

      const ta = a.translations?.[0];
      const tb = b.translations?.[0];

      if (sort === "name-asc") {
        return (ta?.name || "").localeCompare(tb?.name || "");
      }

      const pa = a.price ?? 0;
      const pb = b.price ?? 0;

      if (sort === "price-asc") return pa - pb;
      if (sort === "price-desc") return pb - pa;

      return 0;
    });
  }, [products, search, priceMin, priceMax, inStockOnly, sort]);

  const total = filtered.length;

  return (
    <>
      {/* Top bar */}
      <div className="text-neutral-800 text-xs block md:hidden mb-3 text-right">
        {total} produit{total > 1 ? "s" : ""} trouvé{total > 1 ? "s" : ""}
      </div>
      <div className="mb-3 flex items-center justify-between gap-3 text-xxxs">
        {/* Bouton filtres mobile */}
        <button
          type="button"
          onClick={() => setFiltersOpen(true)}
          className="lg:hidden inline-flex items-center gap-1 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs text-neutral-800 hover:border-black hover:text-black hover:bg-neutral-50 transition"
        >
          <span className="text-xs">☰</span>
          <span>Afficher les filtres</span>
        </button>

        <div className="text-neutral-800 text-xs hidden md:block">
          {total} produit{total > 1 ? "s" : ""} trouvé{total > 1 ? "s" : ""}
        </div>
        <div className="flex items-center gap-2 ml-auto">

          <div className="flex items-center gap-1.5">
            <span className="text-neutral-500">Trier par</span>
            <select
              value={sort}
              onChange={(e) => setSort(e.target.value as any)}
              className="rounded-lg border border-neutral-200 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-black/70"
            >
              <option value="featured">Pertinence</option>
              <option value="price-asc">Prix croissant</option>
              <option value="price-desc">Prix décroissant</option>
              <option value="name-asc">Nom (A-Z)</option>
            </select>
          </div>
        </div>
      </div>

      {/* Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-[220px_minmax(0,1fr)] gap-6 items-start">
        {/* Sidebar desktop */}
        <aside className="hidden lg:block">
          <div className="text-xs font-medium text-neutral-500 uppercase tracking-[.16em] mb-2">
            Filtres
          </div>
          <FilterPanel
            search={search}
            setSearch={setSearch}
            priceMin={priceMin}
            setPriceMin={setPriceMin}
            priceMax={priceMax}
            setPriceMax={setPriceMax}
            bounds={priceBounds}
            inStockOnly={inStockOnly}
            setInStockOnly={setInStockOnly}
          />
        </aside>

        {/* Listing produits */}
        <section>
          <ListingProductsClient isEmpty={!filtered || filtered.length === 0} emptyMessage="Aucun produit ne correspond à vos filtres.">
            {filtered.map((product) => (
              <ThemedProductCardClient
                key={product.id}
                product={product}
                hrefBase="/products"
                themePath={themePath}
              />
            ))}
          </ListingProductsClient>
        </section>
      </div>

      {/* Drawer mobile */}
      {filtersOpen && (
        <div className="fixed inset-0 z-40 flex lg:hidden">
          <div className="h-full w-5/6 sm:w-3/5 md:w-1/3 bg-white shadow-2xl border-r border-neutral-100 p-3 flex flex-col gap-2">
            <div className="flex items-center justify-between mb-1">
              <div className="text-xs font-semibold text-neutral-800">
                Filtres
              </div>
              <button
                type="button"
                onClick={() => setFiltersOpen(false)}
                className="text-body-14 leading-none text-neutral-500 hover:text-black"
              >
                ✕
              </button>
            </div>

            <FilterPanel
              search={search}
              setSearch={setSearch}
              priceMin={priceMin}
              setPriceMin={setPriceMin}
              priceMax={priceMax}
              setPriceMax={setPriceMax}
              bounds={priceBounds}
              inStockOnly={inStockOnly}
              setInStockOnly={setInStockOnly}
            />

            <Button
              type="button"
              onClick={() => setFiltersOpen(false)}
              variant="primary"
              size="sm"
              className="mt-2 w-full"
            >
              Voir les produits
            </Button>
          </div>

          {/* Backdrop */}
          <div
            className="flex-1 bg-black/30"
            onClick={() => setFiltersOpen(false)}
          />
        </div>
      )}
    </>
  );
}
