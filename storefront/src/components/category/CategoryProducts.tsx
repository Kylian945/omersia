"use client";

import { useMemo, useState } from "react";
import { FilterPanel } from "./FilterPanel";
import { ListingProductsClient } from "../product/ListingProductsClient";
import { ThemedProductCardClient } from "../product/ThemedProductCardClient";
import type { ListingProduct } from "../product/ListingProducts";
import { Button } from "@/components/common/Button";

type Props = {
  products: ListingProduct[];
  themePath?: string;
};

function getEffectivePrice(p: ListingProduct): number {
  // Pour un produit à variantes, l'API met déjà price = prix min
  return typeof p.price === "number" ? p.price : 0;
}

export function CategoryProducts({ products, themePath = "vision" }: Props) {
  const priceBounds = useMemo(() => {
    const prices = products
      .map((p) => getEffectivePrice(p))
      .filter((v) => typeof v === "number" && v > 0);

    if (!prices.length) {
      return { min: 0, max: 500 };
    }

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
  const [sort, setSort] = useState<"featured" | "price-asc" | "price-desc" | "name-asc" | "newest">(
    "featured"
  );

  const filtered = useMemo(() => {
    const base = products.filter((p) => {
      const t = p.translations?.[0];
      const name = (t?.name || "").toLowerCase();
      const sku = (p.sku || "").toLowerCase();
      const q = search.toLowerCase().trim();

      if (q && !name.includes(q) && !sku.includes(q)) return false;

      const price = getEffectivePrice(p);
      if (priceMin && price < priceMin) return false;
      if (priceMax && price > priceMax) return false;

      if (inStockOnly) {
        // Pour un produit à variantes : on ne le filtre pas sur stock_qty,
        // on considère qu'il a potentiellement du stock sur une variante.
        if (!p.has_variants) {
          if ((p.stock_qty ?? 0) <= 0) return false;
        }
      }

      return true;
    });

    return base.sort((a, b) => {
      if (sort === "featured") return 0;

      const ta = a.translations?.[0];
      const tb = b.translations?.[0];

      if (sort === "name-asc") {
        return (ta?.name || "").localeCompare(tb?.name || "");
      }

      const pa = getEffectivePrice(a);
      const pb = getEffectivePrice(b);

      if (sort === "price-asc") return pa - pb;
      if (sort === "price-desc") return pb - pa;

      return 0;
    });
  }, [products, search, priceMin, priceMax, inStockOnly, sort]);

  const total = filtered.length;

  return (
    <>
      <div className="mt-4">
        {/* Top bar */}
        <div className="mb-3 flex items-center justify-between gap-3 text-xxxs">
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={() => setFiltersOpen(true)}
              className="lg:hidden inline-flex items-center gap-1 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs text-neutral-800 hover:border-black hover:text-black hover:bg-neutral-50 transition"
            >
              <span className="text-xs">☰</span>
              <span>Afficher les filtres</span>
            </button>
            <div className="text-neutral-800 text-xs">
              {total} produit{total > 1 ? "s" : ""} trouvé{total > 1 ? "s" : ""}
            </div>
          </div>

          <div className="flex items-center gap-3 ml-auto">

            <div className="flex items-center gap-1.5">
              <span className="text-neutral-500">Trier par</span>
              <select
                value={sort}
                onChange={(e) => setSort(e.target.value as 'featured' | 'price-asc' | 'price-desc' | 'newest')}
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
            <ListingProductsClient isEmpty={!filtered || filtered.length === 0}>
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

            <div
              className="flex-1 bg-black/30"
              onClick={() => setFiltersOpen(false)}
            />
          </div>
        )}
      </div>
    </>
  );
}
