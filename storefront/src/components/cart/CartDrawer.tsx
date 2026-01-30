"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import { X } from "lucide-react";
import { useCart } from "./CartContext";
import Link from "next/link";
import { Button } from "../common/Button";
import { ModuleHooks } from "../modules/ModuleHooks";

export function CartDrawer() {
  const {
    items,
    isOpen,
    closeCart,
    removeItem,
    subtotal,
    totalQty,
    cartType
  } = useCart();

  // état interne pour gérer l'animation avant unmount
  const [visible, setVisible] = useState(isOpen);

  useEffect(() => {
    if (isOpen) {
      setVisible(true);
    } else {
      // attend la fin de l'animation avant de démonter
      const timeout = setTimeout(() => setVisible(false), 250);
      return () => clearTimeout(timeout);
    }
  }, [isOpen]);

  // Ne pas afficher le drawer en mode "page"
  if (cartType === 'page') return null;

  if (!visible && !isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex justify-end">
      {/* Overlay */}
      <div
        className={`absolute top-0 left-0 bg-black/40 backdrop-blur-sm transition-opacity duration-500 h-full w-full ${isOpen ? "opacity-100" : "opacity-0"
          }`}
        onClick={closeCart}
      />

      {/* Drawer animé */}
      <div
        className={`w-full max-w-sm h-full bg-white shadow-2xl border-l border-neutral-200 flex flex-col transform transition-transform duration-300 cart-drawer-animate ${isOpen ? "translate-x-0" : "translate-x-full"
          }`}
      >
        <div className="px-4 py-3 border-b border-neutral-100 flex items-center justify-between">
          <div>
            <div className="text-body-14 font-semibold text-neutral-900">
              Votre panier
            </div>
            <div className="text-xs text-neutral-500">
              {totalQty
                ? `${totalQty} article${totalQty > 1 ? "s" : ""}`
                : "Aucun article pour le moment"}
            </div>
          </div>
          <button
            onClick={closeCart}
            className="text-body-14 text-neutral-500 hover:text-black"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Contenu */}
        <div className="flex-1 overflow-y-auto px-4 py-3 space-y-2">
          {/* Hook: avant les items du panier */}
          <ModuleHooks hookName="cart.drawer.before_items" context={{ cartSubtotal: subtotal }} />

          {items.length === 0 && (
            <div className="text-xs text-neutral-500">
              Ajoutez des produits pour commencer votre commande.
            </div>
          )}

          {items.map((item, index) => (
            <div key={index} className="flex items-center gap-2">
              <div className="flex flex-1 items-center gap-2 py-0.5 border border-neutral-100 px-2 rounded-lg">
                <div className="relative w-14 h-14 rounded-lg bg-neutral-50 overflow-hidden flex items-center justify-center">
                  {item.imageUrl ? (
                    <Image
                      src={item.imageUrl}
                      alt={item.name}
                      fill
                      sizes="56px"
                      className="object-cover"
                    />
                  ) : (
                    <span className="text-xxxs text-neutral-400">Image</span>
                  )}
                </div>

                <div className="flex-1 flex flex-col gap-0.5">
                  <div className="text-xs font-medium text-neutral-900 line-clamp-2">
                    {item.name}
                  </div>
                  {item.variantLabel && (
                    <div className="text-xxs text-neutral-500">
                      {item.variantLabel}
                    </div>
                  )}
                  <span className="text-xs text-neutral-500">
                    Qté. {item.qty}
                  </span>
                </div>
                <div className="text-xs font-semibold text-neutral-900 pr-2 flex gap-2 items-center">
                  {item.oldPrice && (
                    <span className="line-through font-normal text-gray-400 text-xs">{(item.oldPrice * item.qty).toFixed(2)} €</span>
                  )}
                  <span>{(item.price * item.qty).toFixed(2)} €</span>

                </div>
              </div>
              <div className="px-1">
                <button
                  onClick={() => removeItem(index)}
                  className="text-xs text-neutral-400 hover:text-gray-800 h-6 w-6 flex items-center justify-center border border-gray-200 hover:bg-gray-100 rounded-full"
                >
                  <X className="w-3 h-3" />
                </button>
              </div>
            </div>
          ))}
        </div>

        {/* Footer */}
        <div>
          <ModuleHooks hookName="cart.drawer.after_items" context={{ cartSubtotal: subtotal }} />

          <div className="border-t border-neutral-100 px-4 py-3">
            <div className="flex items-center justify-between text-xs">
              <span className="text-neutral-600">Sous-total</span>
              <span className="font-semibold text-neutral-900 text-body-14">
                {subtotal.toFixed(2)} €
              </span>
            </div>
            <p className="text-xs text-neutral-400 mt-2 mb-4">
              Les frais de livraison et codes promo seront appliqués à
              l’étape suivante.
            </p>
            <div onClick={() => closeCart()}>
              <Button
                href="/checkout"
                size="md"
                variant="primary"
                className="w-full"
                disabled={items.length === 0}>
                Valider mon panier
              </Button>
            </div>
          </div>
        </div>
      </div>

    </div>
  );
}
