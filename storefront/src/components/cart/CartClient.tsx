"use client";

import Link from "next/link";
import { OptimizedImage } from "@/components/common/OptimizedImage";
import { useCart } from "@/components/cart/CartContext";
import { Container } from "@/components/common/Container";
import { Trash2, ArrowLeft, ArrowRight } from "lucide-react";
import { Button } from "@/components/common/Button";
import { ModuleHooks } from "@/components/modules/ModuleHooks";

export default function CartPage() {
  const { items, subtotal, totalQty, updateQty, removeItem, clear } = useCart();

  const handleQtyChange = (index: number, value: string) => {
    const qty = Math.max(1, Number(value) || 1);
    updateQty(index, qty);
  };

  return (
    <main className="flex-1 py-8 bg-neutral-50">
      <Container>
        {/* Breadcrumb */}
        <div className="text-xs text-neutral-500 mb-3">
          <Link href="/" className="hover:text-black transition">
            Accueil
          </Link>
          <span className="mx-1">/</span>
          <span className="text-neutral-800">Panier</span>
        </div>

        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-2 mb-5">
          <div>
            <h1 className="text-2xl font-semibold tracking-tight text-neutral-900">
              Mon panier
            </h1>
            <p className="text-xs text-neutral-500">
              {totalQty > 0
                ? `${totalQty} article${totalQty > 1 ? "s" : ""} sélectionné${totalQty > 1 ? "s" : ""
                }`
                : "Votre panier est vide."}
            </p>
          </div>
        </div>

        {/* Empty state */}
        {items.length === 0 && (
          <div className="mt-4 rounded-2xl bg-white border border-neutral-200 p-6 text-xs text-neutral-600 flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
              <p className="font-medium text-neutral-900 mb-1">
                Votre panier est encore vide.
              </p>
              <p className="text-xs text-neutral-500">
                Ajoutez des produits pour commencer votre commande.
              </p>
            </div>
            <Link
              href="/products"
              className="inline-flex items-center gap-1 rounded-full bg-black px-4 py-1.5 text-xs text-white hover:bg-neutral-900"
            >
              <ArrowRight className="w-3 h-3" />
              Découvrir les produits
            </Link>
          </div>
        )}

        {/* Cart layout */}
        {items.length > 0 && (
          <div className="grid grid-cols-1 lg:grid-cols-[minmax(0,2.2fr)_minmax(260px,320px)] gap-6 items-start">
            {/* Left: items */}
            <section className="flex flex-col gap-3">
              <section className="rounded-2xl bg-white border border-neutral-200 p-4 space-y-3">
                {/* Hook: avant les items du panier */}
                <ModuleHooks hookName="cart.page.before_items" context={{ cartSubtotal: subtotal }} />

                {items.map((item, index) => (
                  <div
                    key={`${item.id}-${index}-${item.variantId || "base"}`}
                    className="flex items-center gap-3 border border-neutral-100 rounded-xl px-3 py-2"
                  >
                    {/* Image */}
                    <div className="relative w-16 h-16 bg-neutral-50 rounded-lg overflow-hidden flex items-center justify-center">
                      {item.imageUrl ? (
                        <OptimizedImage
                          src={item.imageUrl}
                          alt={item.name}
                          fill
                          sizes="64px"
                          className="object-cover"
                          fallback={<span className="text-xxxs text-neutral-400">Image</span>}
                        />
                      ) : (
                        <span className="text-xxxs text-neutral-400">Image</span>
                      )}
                    </div>

                    {/* Infos */}
                    <div className="flex-1 flex flex-col gap-0.5">
                      <div className="text-xs font-medium text-neutral-900 line-clamp-2">
                        {item.name}
                      </div>
                      {item.variantLabel && (
                        <div className="text-xxxs text-neutral-500">
                          {item.variantLabel}
                        </div>
                      )}
                      <div className="flex items-center gap-2 mt-1">
                        {/* Qty */}
                        <div className="flex items-center gap-1">
                          <span className="text-xxxs text-neutral-500">
                            Qté
                          </span>
                          <input
                            type="number"
                            min={1}
                            value={item.qty}
                            onChange={(e) => handleQtyChange(index, e.target.value)}
                            className="w-14 rounded-lg border border-neutral-200 px-2 py-1.5 text-xs text-neutral-800 focus:outline-none focus:ring-1 focus:ring-black/70"
                          />
                        </div>
                      </div>
                    </div>

                    {/* Prices + remove */}
                    <div className="flex flex-col items-end gap-1">
                      {item.oldPrice && (
                        <div className="text-xxxs text-neutral-400 line-through">
                          {(item.oldPrice * item.qty).toFixed(2)} €
                        </div>
                      )}
                      <div className="text-xs font-semibold text-neutral-900">
                        {(item.price * item.qty).toFixed(2)} €
                      </div>

                    </div>
                    <button
                      type="button"
                      onClick={() => removeItem(index)}
                      className="ml-3 mt-1 inline-flex items-center justify-center w-6 h-6 rounded-full border border-neutral-200 text-neutral-400 hover:text-red-500 hover:border-red-300"
                      aria-label="Retirer l'article"
                    >
                      <Trash2 className="w-3 h-3" />
                    </button>
                  </div>
                ))}



                {/* Hook: cart.items.extras - Permet d'ajouter du contenu supplémentaire dans la liste des items */}
                <ModuleHooks
                  hookName="cart.items.extras"
                  context={{
                    cartItems: items,
                    cartTotal: subtotal,
                  }}
                />

                {/* Back to shopping */}
                <div className="flex items-center gap-1 pt-1">
                  <ArrowLeft className="w-3 h-3 text-neutral-400" />
                  <Link
                    href="/products"
                    className="text-xs text-neutral-500 hover:text-neutral-900"
                  >
                    Continuer mes achats
                  </Link>
                </div>
              </section>

              {/* Hook: apres les items du panier */}
              <ModuleHooks hookName="cart.page.after_items" context={{ cartSubtotal: subtotal }} />

            </section>

            {/* Right: summary */}
            <aside className="space-y-3">
              {/* Hook: cart.sidebar.recommendations - Permet d'ajouter des recommandations dans la sidebar */}
              <ModuleHooks
                hookName="cart.sidebar.recommendations"
                context={{
                  cartItems: items,
                  cartTotal: subtotal,
                }}
              />

              <div className="rounded-2xl bg-white border border-neutral-200 p-4 space-y-2">
                <h2 className="text-xs font-semibold text-neutral-900">
                  Récapitulatif
                </h2>

                <div className="flex justify-between text-xs">
                  <span className="text-neutral-600">
                    Sous-total ({totalQty} article
                    {totalQty > 1 ? "s" : ""})
                  </span>
                  <span className="font-medium">
                    {subtotal.toFixed(2)} €
                  </span>
                </div>

                <div className="flex justify-between text-xs">
                  <span className="text-neutral-600">
                    Livraison
                  </span>
                  <span className="text-neutral-600">
                    Calculée à l&apos;étape suivante
                  </span>
                </div>

                <div className="pt-2 mt-1 border-t border-neutral-200 flex justify-between items-baseline">
                  <span className="text-xs text-neutral-900 font-semibold">
                    Total estimé
                  </span>
                  <span className="text-body-14 text-neutral-900 font-semibold">
                    {subtotal.toFixed(2)} €
                  </span>
                </div>

                <Button
                  href="/checkout"
                  variant="primary"
                  size="md"
                  className="mt-2 w-full"
                >
                  Valider mon panier
                </Button>

                <p className="text-xs text-neutral-400 mt-1">
                  Les frais de livraison et codes promo seront appliqués au
                  checkout.
                </p>
              </div>

              {/* Hook: cart.footer.actions - Permet d'ajouter des actions supplémentaires en bas du panier */}
              <ModuleHooks
                hookName="cart.footer.actions"
                context={{
                  cartItems: items,
                  cartTotal: subtotal,
                }}
              />
            </aside>
          </div>
        )}
      </Container>
    </main>
  );
}
