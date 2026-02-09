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
          className="bg-white rounded-2xl shadow-2xl max-w-md w-full relative animate-in fade-in zoom-in duration-200"
          onClick={(e) => e.stopPropagation()}
        >
          {/* Bouton fermer */}
          <button
            onClick={onClose}
            className="absolute right-4 top-4 p-2 rounded-full hover:bg-gray-100 transition-colors"
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
            <h2 className="text-xl font-semibold text-center mb-6">
              Produit ajouté au panier
            </h2>

            {/* Hook: après le produit ajouté */}
            <ModuleHooks hookName="cart.modal.after_product" context={{ cartSubtotal: subtotal }} />

            {/* Informations produit */}
            <div className="flex gap-4 mb-6 p-4 bg-gray-50 rounded-xl">
              {/* Image */}
              {item.imageUrl && (
                <div className="relative w-20 h-20 flex-shrink-0 bg-white rounded-lg overflow-hidden">
                  <OptimizedImage
                    src={item.imageUrl}
                    alt={item.name}
                    fill
                    sizes="80px"
                    className="object-cover"
                    fallback={<span className="text-xs text-gray-400">Image</span>}
                  />
                </div>
              )}

              {/* Détails */}
              <div className="flex-1 min-w-0">
                <h3 className="font-medium text-sm line-clamp-2 mb-1">
                  {item.name}
                </h3>

                {item.variantLabel && (
                  <p className="text-xs text-gray-500 mb-2">
                    {item.variantLabel}
                  </p>
                )}

                <div className="flex items-center justify-between">
                  <div className="flex items-baseline gap-2">
                    <span className="font-semibold text-base">
                      {item.price.toFixed(2)} €
                    </span>
                    {item.oldPrice && (
                      <span className="text-xs text-gray-400 line-through">
                        {item.oldPrice.toFixed(2)} €
                      </span>
                    )}
                  </div>

                  <span className="text-sm text-gray-600">
                    Qté: {item.qty}
                  </span>
                </div>
              </div>
            </div>

            {/* Actions */}
            <div className="flex flex-col gap-3">
              <button
                onClick={onViewCart}
                className="w-full py-3 px-4 bg-black text-white rounded-xl font-medium hover:bg-gray-800 transition-colors flex items-center justify-center gap-2"
              >
                <ShoppingBag className="w-5 h-5" />
                Voir le panier
              </button>

              <button
                onClick={onClose}
                className="w-full py-3 px-4 bg-gray-100 text-gray-900 rounded-xl font-medium hover:bg-gray-200 transition-colors"
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
