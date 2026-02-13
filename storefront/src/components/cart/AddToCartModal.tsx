"use client";

import { X, Check, ShoppingBag } from "lucide-react";
import { OptimizedImage } from "@/components/common/OptimizedImage";
import { useEffect } from "react";
import { CartItem } from "@/lib/types/product-types";
import { ModuleHooks } from "../modules/ModuleHooks";
import { useCart } from "./CartContext";

interface AddToCartModalProps {
  isOpen: boolean;
  onClose: () => void;
  item: CartItem | null;
  onViewCart: () => void;
}

export function AddToCartModal({
  isOpen,
  onClose,
  item,
  onViewCart,
}: AddToCartModalProps) {
  const { subtotal } = useCart();

  // Fermer avec Escape
  useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };

    if (isOpen) {
      document.addEventListener("keydown", handleEscape);
      document.body.style.overflow = "hidden";
    }

    return () => {
      document.removeEventListener("keydown", handleEscape);
      document.body.style.overflow = "unset";
    };
  }, [isOpen, onClose]);

  if (!isOpen || !item) return null;

  return (
    <>
      {/* Overlay */}
      <div
        className="fixed inset-0 bg-black/50 z-50 transition-opacity"
        onClick={onClose}
        aria-hidden="true"
      />

      {/* Modal */}
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
          className="bg-[var(--theme-card-bg,#ffffff)] rounded-2xl shadow-2xl max-w-md w-full relative animate-in fade-in zoom-in duration-200"
          onClick={(e) => e.stopPropagation()}
        >
          {/* Bouton fermer */}
          <button
            onClick={onClose}
            className="absolute right-4 top-4 p-2 rounded-full hover:bg-[var(--theme-input-bg,#ffffff)] transition-colors"
            aria-label="Fermer"
          >
            <X className="w-5 h-5" />
          </button>

          {/* Contenu */}
          <div className="p-6">
            {/* Icône de succès */}
            <div className="flex items-center justify-center mb-4">
              <div className="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                <Check className="w-6 h-6 text-green-600" />
              </div>
            </div>

            {/* Titre */}
            <h2 className="text-xl font-semibold text-center text-[var(--theme-heading-color,#111827)] mb-6">
              Produit ajouté au panier
            </h2>

            {/* Hook: après le produit ajouté */}
            <ModuleHooks hookName="cart.modal.after_product" context={{ cartSubtotal: subtotal }} />

            {/* Informations produit */}
            <div className="flex gap-4 mb-6 p-4 bg-[var(--theme-page-bg,#f6f6f7)] rounded-xl">
              {/* Image */}
              {item.imageUrl && (
                <div className="relative w-20 h-20 flex-shrink-0 bg-[var(--theme-card-bg,#ffffff)] rounded-lg overflow-hidden">
                  <OptimizedImage
                    src={item.imageUrl}
                    alt={item.name}
                    fill
                    sizes="80px"
                    className="object-cover"
                    fallback={<span className="text-xs text-[var(--theme-muted-color,#6b7280)]">Image</span>}
                  />
                </div>
              )}

              {/* Détails */}
              <div className="flex-1 min-w-0">
                <h3 className="font-medium text-sm text-[var(--theme-heading-color,#111827)] line-clamp-2 mb-1">
                  {item.name}
                </h3>

                {item.variantLabel && (
                  <p className="text-xs text-[var(--theme-muted-color,#6b7280)] mb-2">
                    {item.variantLabel}
                  </p>
                )}

                <div className="flex items-center justify-between">
                  <div className="flex items-baseline gap-2">
                    <span className="font-semibold text-base">
                      {item.price.toFixed(2)} €
                    </span>
                    {item.oldPrice && (
                      <span className="text-xs text-[var(--theme-muted-color,#6b7280)] line-through">
                        {item.oldPrice.toFixed(2)} €
                      </span>
                    )}
                  </div>

                  <span className="text-sm text-[var(--theme-muted-color,#6b7280)]">
                    Qté: {item.qty}
                  </span>
                </div>
              </div>
            </div>

            {/* Actions */}
            <div className="flex flex-col gap-3">
              <button
                onClick={onViewCart}
                className="w-full py-3 px-4 bg-[var(--theme-primary,#111827)] text-[var(--theme-button-primary-text,#ffffff)] rounded-xl font-medium hover:opacity-90 transition-colors flex items-center justify-center gap-2"
              >
                <ShoppingBag className="w-5 h-5" />
                Voir le panier
              </button>

              <button
                onClick={onClose}
                className="w-full py-3 px-4 border border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-input-bg,#ffffff)] text-[var(--theme-heading-color,#111827)] rounded-xl font-medium hover:border-[var(--theme-border-hover,#111827)] transition-colors"
              >
                Continuer mes achats
              </button>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
