"use client";

import { useEffect, useRef, useState } from "react";
import { Container } from "../common/Container";
import { ProductWithVariants } from "@/lib/types/product-types";
import { SimpleAddToCart } from "./SimpleAddToCart";
import { StickyVariantSelector } from "./StickyVariantSelector";

type StickyAddToCartBarProps = {
  product: ProductWithVariants;
  inStock: boolean;
};

export function StickyAddToCartBar({
  product,
  inStock,
}: StickyAddToCartBarProps) {
  const [visible, setVisible] = useState(false);
  const barRef = useRef<HTMLDivElement | null>(null);

  const t = product.translations?.[0]!;

  const hasVariants =
    !!product.has_variants &&
    Array.isArray(product.variants) &&
    product.variants.length > 0;

  // Détection visibilité via l'ancre #product-cta
  useEffect(() => {
    const target = document.getElementById("product-cta");
    if (!target) return;

    const observer = new IntersectionObserver(
      (entries) => {
        const entry = entries[0];
        setVisible(!entry.isIntersecting);
      },
      {
        root: null,
        threshold: 0.2,
      }
    );

    observer.observe(target);
    return () => observer.disconnect();
  }, []);

  // Gestion dynamique du padding-bottom du <main> en fonction de la hauteur réelle de la barre
  useEffect(() => {
    const main = document.querySelector("main") as HTMLElement | null;
    if (!main) return;

    if (!visible) {
      // Si la barre n'est plus visible, on reset
      main.style.paddingBottom = "";
      return;
    }

    function applyPadding() {
      if (!barRef.current) return;
      const height = barRef.current.offsetHeight || 0;
      // On ajoute un petit offset pour respirer un peu
      if (main) {
        main.style.paddingBottom = `${height}px`;
      }
    }

    applyPadding();

    // Recalcule sur resize (mobile/desktop, rotation, etc.)
    window.addEventListener("resize", applyPadding);

    return () => {
      window.removeEventListener("resize", applyPadding);
      // Clean au démontage / changement de page
      main.style.paddingBottom = "";
    };
  }, [visible]);

  if (!visible) return null;

  return (
    <div
      ref={barRef}
      className="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur border-t border-neutral-200"
    >
      <Container>
        {hasVariants ? (
          <div className="w-full py-3 flex flex-wrap items-start gap-3">
            <div className="w-full flex justify-between min-w-0">
              <div className="flex flex-col">
                <div className="text-xs text-neutral-500 truncate">
                  Vous regardez
                </div>
                <div className="text-xs font-medium text-neutral-900 truncate">
                  {t.name}
                </div>
              </div>
              <div className="self-start rounded-full bg-indigo-100/90 px-2 py-0.5 text-xxxs text-indigo-900 border border-indigo-200">
                Plusieurs modèles
              </div>
            </div>

            <hr className="border-gray-100 w-full h-px" />

            <div className="w-full flex items-center gap-2">
              <StickyVariantSelector />
            </div>
          </div>
        ) : (
          <div className="w-full py-3 flex flex-wrap items-start gap-3">
            <div className="w-full md:flex-1 min-w-0">
              <div className="text-xs text-neutral-500 truncate">
                Vous regardez
              </div>
              <div className="text-xs font-medium text-neutral-900 truncate">
                {t.name}
              </div>
              <div className="text-xs text-neutral-500">
                {inStock ? (
                  <div className="flex items-center gap-2">
                    <div className="h-2 w-2 bg-emerald-500 rounded-full"></div>
                    <span>En stock — prêt à être commandé</span>
                  </div>
                ) : (
                  <div className="flex items-center gap-2">
                    <div className="h-2 w-2 bg-rose-500 rounded-full"></div>
                    <span>Actuellement indisponible</span>
                  </div>
                )}
              </div>
            </div>

            <div className="w-full md:w-auto flex items-center gap-2">
              <SimpleAddToCart
                productId={product.id}
                name={t?.name || "Produit"}
                price={
                  typeof product.price === "number" ? product.price : 0
                }
                oldPrice={
                  typeof product.compare_at_price === "number"
                    ? product.compare_at_price
                    : undefined
                }
                disabled={!inStock}
              />
            </div>
          </div>
        )}
      </Container>
    </div>
  );
}
