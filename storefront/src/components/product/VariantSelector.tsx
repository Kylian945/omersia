"use client";

import { useProductVariant } from "./ProductVariantContext";
import { AddToCartButton } from "./AddToCartButton";

type VariantSelectorProps = {
  onAdded?: () => void;
};

export function VariantSelector({ onAdded }: VariantSelectorProps = {}) {
  const {
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
    variantLabel,
    productId,
    productName,
    imageUrl,
  } = useProductVariant();

  const canAdd =
    !!matchingVariant &&
    typeof currentPrice === "number" &&
    currentPrice > 0 &&
    currentInStock &&
    qty > 0;

  return (
    <div className="space-y-2">
      {/* Sélecteurs d’options */}
      {optionNames.map((name) => (
        <div key={name} className="space-y-1">
          <div className="text-xs text-neutral-800 font-medium">
            {name} :
          </div>
          <div className="flex flex-wrap gap-1.5">
            {(options[name] || []).map((val) => {
              const isActive = selected[name] === val;
              return (
                <button
                  key={val}
                  type="button"
                  onClick={() => toggleOption(name, val)}
                  className={`px-2 py-1 rounded-full text-xs border transition ${isActive
                      ? "bg-black text-white border-black"
                      : "bg-white text-neutral-800 border-neutral-200 hover:bg-neutral-50"
                    }`}
                >
                  {val}
                </button>
              );
            })}
          </div>
        </div>
      ))}

      {/* Etat sélection */}
      {optionNames.length > 0 && !matchingVariant && (
        <p className="text-xs text-amber-600">
          Sélectionnez toutes les options pour voir la variante disponible.
        </p>
      )}

      {matchingVariant && (
        <div className="space-y-2">
          {/* Résumé variante */}
          <div className="text-xxxs text-neutral-700">
            Variante sélectionnée :{" "}
            <span className="font-medium">
              {variantLabel || `Variante #${matchingVariant.id}`}
            </span>
            {matchingVariant.sku && (
              <span className="ml-1 text-xxxs text-neutral-400">
                (SKU {matchingVariant.sku})
              </span>
            )}
          </div>

          {/* Prix + stock */}
          <div className="flex items-center gap-2 text-body-16">
            {typeof currentPrice === "number" && (
              <span className="font-semibold">
                {currentPrice.toFixed(2)} €
              </span>
            )}
            {typeof currentCompareAt === "number" &&
              currentCompareAt > (currentPrice || 0) && (
                <span className="line-through text-neutral-400 text-xs">
                  {currentCompareAt.toFixed(2)} €
                </span>
              )}
            <span
              className={`ml-2 px-2 py-0.5 rounded-full border text-xs flex items-center gap-2 ${currentInStock
                  ? "bg-emerald-50 text-emerald-700 border-emerald-100"
                  : "bg-red-50 text-red-600 border-red-100"
                }`}
            >
              {currentInStock ? (<div className="h-2 w-2 bg-emerald-500 rounded-full" />) : (<div className="h-2 w-2 bg-rose-500 rounded-full" />)}
              {currentInStock ? "En stock" : "Indisponible"}
            </span>
          </div>

          {/* CTA */}
          <div className="space-y-1 pt-1">
            <div className="flex items-center gap-2">
              <input
                type="number"
                min={1}
                value={qty}
                onChange={(e) =>
                  setQty(Math.max(1, Number(e.target.value) || 1))
                }
                className="w-16 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs text-neutral-800 focus:outline-none focus:ring-1 focus:ring-black/70"
              />
              <AddToCartButton
                productId={productId}
                name={productName}
                price={currentPrice || 0}
                oldPrice={
                  typeof currentCompareAt === "number"
                    ? currentCompareAt
                    : undefined
                }
                imageUrl={imageUrl || undefined}
                variantId={matchingVariant.id}
                variantLabel={variantLabel || undefined}
                quantity={qty}
                disabled={!canAdd}
                onAdded={onAdded}
              />
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
