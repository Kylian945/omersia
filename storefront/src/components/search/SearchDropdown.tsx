"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { Search, Loader2 } from "lucide-react";
import { SearchResponse } from "@/lib/types/search-types";
import { logger } from "@/lib/logger";
import { OptimizedImage } from "@/components/common/OptimizedImage";

export function SearchDropdown({
  query,
  onClose,
}: {
  query: string;
  onClose: () => void;
}) {
  const [results, setResults] = useState<SearchResponse | null>(null);
  const [loading, setLoading] = useState(false);

  // États des filtres
  const [selectedCategories, setSelectedCategories] = useState<number[]>([]);
  const [minPrice, setMinPrice] = useState<string>("");
  const [maxPrice, setMaxPrice] = useState<string>("");
  const [inStockOnly, setInStockOnly] = useState(false);

  useEffect(() => {
    if (!query.trim() || query.length < 2) {
      setResults(null);
      return;
    }

    const searchProducts = async () => {
      setLoading(true);
      try {
        // Construire l'URL avec les filtres
        let url = `/api/search?q=${encodeURIComponent(query)}&limit=10&locale=fr`;

        if (selectedCategories.length > 0) {
          url += `&categories=${selectedCategories.join(",")}`;
        }
        if (minPrice) {
          url += `&min_price=${minPrice}`;
        }
        if (maxPrice) {
          url += `&max_price=${maxPrice}`;
        }
        if (inStockOnly) {
          url += `&in_stock_only=true`;
        }

        const response = await fetch(url);

        if (response.ok) {
          const data: SearchResponse = await response.json();
          setResults(data);
        }
      } catch (error) {
        logger.error("Erreur de recherche:", error);
      } finally {
        setLoading(false);
      }
    };

    const debounceTimer = setTimeout(searchProducts, 300);

    return () => clearTimeout(debounceTimer);
  }, [query, selectedCategories, minPrice, maxPrice, inStockOnly]);

  const toggleCategory = (categoryId: number) => {
    setSelectedCategories((prev) =>
      prev.includes(categoryId)
        ? prev.filter((id) => id !== categoryId)
        : [...prev, categoryId]
    );
  };

  const clearFilters = () => {
    setSelectedCategories([]);
    setMinPrice("");
    setMaxPrice("");
    setInStockOnly(false);
  };

  const hasActiveFilters =
    selectedCategories.length > 0 || minPrice || maxPrice || inStockOnly;

  if (!query.trim() || query.length < 2) {
    return null;
  }

  return (
    <>
      <div className="absolute left-0 right-0 top-full mt-2 bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] rounded-xl shadow-lg z-50 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-6 h-6 animate-spin text-[var(--theme-muted-color,#6b7280)]" />
          </div>
        ) : results ? (
          <div className="grid grid-cols-1 md:grid-cols-[250px_minmax(0,1fr)] max-h-[600px]">
            {/* Sidebar filtres */}
            <div className="border-r border-[var(--theme-border-default,#e5e7eb)] overflow-y-auto max-h-[600px] hidden md:block">
              <div className="flex items-center justify-between mb-4 bg-[var(--theme-page-bg,#f6f6f7)] border-b border-[var(--theme-border-default,#e5e7eb)] p-4">
                <div className="flex items-center gap-2 ">

                  <h3 className="text-sm font-semibold text-[var(--theme-heading-color,#111827)]">Filtres</h3>
                </div>
                {hasActiveFilters && (
                  <button
                    onClick={clearFilters}
                    className="text-xs text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-heading-color,#111827)] transition"
                  >
                    Réinitialiser
                  </button>
                )}
              </div>

              {/* Catégories */}
              {results.facets.categories.length > 0 && (
                <div className="mb-6 px-4">
                  <div className="text-xs font-medium text-[var(--theme-body-color,#374151)] mb-2">
                    Catégories
                  </div>
                  <div className="space-y-1">
                    {results.facets.categories.map((category) => (
                      <label
                        key={category.id}
                        className="flex items-center gap-2 py-1 cursor-pointer group"
                      >
                        <input
                          type="checkbox"
                          checked={selectedCategories.includes(category.id)}
                          onChange={() => toggleCategory(category.id)}
                          className="w-4 h-4 rounded border-[var(--theme-border-default,#e5e7eb)] text-[var(--theme-primary,#111827)] focus:ring-[var(--theme-primary,#111827)] focus:ring-offset-0 checked:bg-[var(--theme-primary,#111827)] checked:border-[var(--theme-primary,#111827)]"
                        />
                        <span className="text-xs text-[var(--theme-body-color,#374151)] group-hover:text-[var(--theme-heading-color,#111827)] transition flex-1">
                          {category.name}
                        </span>
                        <span className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
                          ({category.count})
                        </span>
                      </label>
                    ))}
                  </div>
                </div>
              )}

              {/* Prix */}
              {results.facets.price_range.max > 0 && (
                <div className="mb-6 px-4">
                  <div className="text-xs font-medium text-[var(--theme-body-color,#374151)] mb-2">
                    Prix
                  </div>
                  <div className="space-y-2">
                    <div className="flex items-center gap-2">
                      <input
                        type="number"
                        placeholder="Min"
                        value={minPrice}
                        onChange={(e) => setMinPrice(e.target.value)}
                        className="w-full px-2 py-1.5 text-xs border border-[var(--theme-border-default,#e5e7eb)] rounded-lg focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                        min={0}
                        max={results.facets.price_range.max}
                      />
                      <span className="text-xs text-[var(--theme-muted-color,#6b7280)]">-</span>
                      <input
                        type="number"
                        placeholder="Max"
                        value={maxPrice}
                        onChange={(e) => setMaxPrice(e.target.value)}
                        className="w-full px-2 py-1.5 text-xs border border-[var(--theme-border-default,#e5e7eb)] rounded-lg focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                        min={0}
                        max={results.facets.price_range.max}
                      />
                    </div>
                    <div className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
                      De {results.facets.price_range.min}€ à{" "}
                      {results.facets.price_range.max}€
                    </div>
                  </div>
                </div>
              )}

              {/* Stock */}
              <div className="mb-4 px-4">
                <div className="text-xs font-medium text-[var(--theme-body-color,#374151)] mb-2">
                  Disponibilité
                </div>
                <label className="flex items-center gap-2 py-1 cursor-pointer group">
                  <input
                    type="checkbox"
                    checked={inStockOnly}
                    onChange={(e) => setInStockOnly(e.target.checked)}
                    className="w-4 h-4 rounded border-[var(--theme-border-default,#e5e7eb)] text-[var(--theme-primary,#111827)] focus:ring-[var(--theme-primary,#111827)] focus:ring-offset-0 checked:bg-[var(--theme-primary,#111827)] checked:border-[var(--theme-primary,#111827)]"
                  />
                  <span className="text-xs text-[var(--theme-body-color,#374151)] group-hover:text-[var(--theme-heading-color,#111827)] transition">
                    En stock uniquement
                  </span>
                </label>
              </div>
            </div>

            {/* Résultats */}
            <div className="overflow-y-auto max-h-[600px]">
              {results.products.length > 0 ? (
                <div className="p-4">
                  <div className="text-xs font-medium text-[var(--theme-muted-color,#6b7280)] mb-3">
                    {results.total} résultat{results.total > 1 ? "s" : ""} trouvé
                    {results.total > 1 ? "s" : ""}
                  </div>
                  <div className="space-y-2">
                    {results.products.map((product) => {
                      const translation = product.translations?.[0];
                      const productName =
                        translation?.name || product.name || "Sans nom";
                      const productSlug = translation?.slug || product.slug;
                      const displayPrice = product.has_variants
                        ? product.from_price
                        : product.price;
                      const image = product.main_image_url || product.images?.[0]?.url || null;

                      return (
                        <Link
                          key={product.id}
                          href={`/products/${productSlug}`}
                          onClick={onClose}
                          className="flex items-center gap-4 p-3 rounded-lg hover:bg-[var(--theme-page-bg,#f6f6f7)] transition"
                        >
                          {image ? (
                            <OptimizedImage
                              src={image}
                              alt={productName}
                              width={64}
                              height={64}
                              sizes="64px"
                              className="w-16 h-16 object-cover rounded-md shrink-0"
                              fallback={
                                <div className="w-16 h-16 bg-[var(--theme-input-bg,#ffffff)] rounded-md flex items-center justify-center shrink-0">
                                  <Search className="w-6 h-6 text-[var(--theme-muted-color,#6b7280)]" />
                                </div>
                              }
                            />
                          ) : (
                            <div className="w-16 h-16 bg-[var(--theme-input-bg,#ffffff)] rounded-md flex items-center justify-center shrink-0">
                              <Search className="w-6 h-6 text-[var(--theme-muted-color,#6b7280)]" />
                            </div>
                          )}
                          <div className="flex-1 min-w-0 flex justify-between">
                            <div>
                              <div className="font-medium text-sm text-[var(--theme-heading-color,#111827)] truncate">
                                {productName}
                              </div>
                              <div className="flex items-center gap-2 mt-1">
                                <span className="text-sm font-semibold text-[var(--theme-heading-color,#111827)]">
                                  {product.has_variants && "À partir de "}
                                  {displayPrice?.toFixed(2)} €
                                </span>
                                {product.compare_at_price &&
                                  product.compare_at_price > (displayPrice || 0) && (
                                    <span className="text-xs text-[var(--theme-muted-color,#6b7280)] line-through">
                                      {product.compare_at_price.toFixed(2)} €
                                    </span>
                                  )}
                              </div>
                            </div>
                            <div className="text-xs text-[var(--theme-muted-color,#6b7280)] mt-0.5">
                              {product.stock_qty > 0 ? (
                                <div className="flex items-center gap-2">
                                  <div className="h-2 w-2 bg-emerald-500 rounded-full"></div>
                                  <span>En stock</span>
                                </div>
                              ) : (
                                <div className="flex items-center gap-2">
                                  <div className="h-2 w-2 bg-rose-500 rounded-full"></div>
                                  <span>Indisponible</span>
                                </div>
                              )}
                            </div>

                          </div>
                        </Link>
                      );
                    })}
                  </div>
                  {results.total > 10 && (
                    <Link
                      href={`/search?q=${encodeURIComponent(query)}`}
                      onClick={onClose}
                      className="block mt-4 pt-4 border-t border-[var(--theme-border-default,#e5e7eb)] text-center text-sm font-medium text-[var(--theme-body-color,#374151)] hover:text-[var(--theme-heading-color,#111827)] transition"
                    >
                      Voir tous les résultats ({results.total})
                    </Link>
                  )}
                </div>
              ) : (
                <div className="p-8 text-center">
                  <Search className="w-12 h-12 text-[var(--theme-muted-color,#6b7280)] mx-auto mb-3" />
                  <div className="text-sm font-medium text-[var(--theme-heading-color,#111827)] mb-1">
                    Aucun résultat trouvé
                  </div>
                  <div className="text-xs text-[var(--theme-muted-color,#6b7280)] mb-3">
                    Essayez de modifier vos filtres ou vos mots-clés
                  </div>
                  {hasActiveFilters && (
                    <button
                      onClick={clearFilters}
                      className="text-xs text-[var(--theme-heading-color,#111827)] hover:underline"
                    >
                      Réinitialiser les filtres
                    </button>
                  )}
                </div>
              )}
            </div>
          </div>
        ) : null}
      </div>
    </>
  );
}
