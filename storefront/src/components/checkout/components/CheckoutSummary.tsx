import type { CartItem } from "@/components/cart/CartContext";
import { XIcon } from "lucide-react";
import { Button } from "@/components/common/Button";
import { ModuleHooks } from "@/components/modules/ModuleHooks";
import { OptimizedImage } from "@/components/common/OptimizedImage";

type LineAdjustment = {
  id: number;
  variant_id: number | null;
  discount_amount: number;
  is_gift?: boolean;
};

type AppliedPromoBadge = {
  code: string;
  label: string;
};

type CheckoutSummaryProps = {
  items: CartItem[];
  subtotal: number;
  shippingMethod: string | null;
  shippingCost: number;
  total: number;
  promoCode: string;
  promoError: string;
  showPromo: boolean;
  onPromoCodeChange: (value: string) => void;
  onTogglePromo: () => void;
  onApplyPromo: () => void;

  // codes manuels affichés en badges
  appliedPromos: AppliedPromoBadge[];
  onRemovePromoCode: (code: string) => void;

  lineAdjustments: Record<string, LineAdjustment>;

  // remises manuelles
  orderDiscountTotal: number;
  productDiscountTotal: number;

  // remises auto (produit + commande)
  automaticDiscountTotal?: number;

  // 🔥 nouveau : réduction sur les frais de livraison
  shippingDiscountTotal?: number;

  // Taxes
  taxTotal?: number;
  taxRate?: number;
};

export function CheckoutSummary({
  items,
  subtotal,
  shippingMethod,
  shippingCost,
  total,
  promoCode,
  promoError,
  showPromo,
  onPromoCodeChange,
  onTogglePromo,
  onApplyPromo,
  appliedPromos,
  onRemovePromoCode,
  lineAdjustments,
  orderDiscountTotal,
  productDiscountTotal,
  automaticDiscountTotal,
  shippingDiscountTotal,
  taxTotal,
  taxRate,
}: CheckoutSummaryProps) {
  return (
    <div className="rounded-2xl bg-white border border-neutral-200 p-4">
      <h3 className="text-xs font-semibold text-neutral-900 mb-2">
        Récapitulatif
      </h3>

      {/* Lignes panier */}
      <div className="space-y-2 max-h-64 overflow-y-auto pr-1">
        {items.map((item, idx) => {
          const key = `${item.id}-${item.variantId ?? "no-variant"}`;
          const adj = lineAdjustments[key];

          const baseLineTotal = item.price * item.qty;
          const lineDiscount = adj?.discount_amount ?? 0;

          return (
            <div
              key={idx}
              className="flex items-center gap-2 text-xs border-b border-gray-100 pb-2"
            >
              <div className="relative w-10 h-10 bg-neutral-50 rounded-lg overflow-hidden flex items-center justify-center">
                {item.imageUrl ? (
                  <OptimizedImage
                    src={item.imageUrl}
                    alt={item.name}
                    fill
                    sizes="40px"
                    className="object-cover"
                    fallback={<span className="text-xxxs text-neutral-400">Image</span>}
                  />
                ) : (
                  <span className="text-xxxs text-neutral-400">Image</span>
                )}
              </div>
              <div className="flex-1">
                <div className="text-xs font-medium text-neutral-900 line-clamp-2">
                  {item.name}
                </div>
                {item.variantLabel && (
                  <div className="text-xxxs text-neutral-500">
                    {item.variantLabel}
                  </div>
                )}
                <div className="text-xxxs text-neutral-500">
                  Qté {item.qty}
                </div>
                {adj?.is_gift && (
                  <div className="text-xxxs text-gray-400">
                    Article offert
                  </div>
                )}
              </div>
              <div className="flex flex-col items-end gap-0.5">
                <div className="flex items-baseline gap-1">
                  {item.oldPrice && (
                    <div className="text-xxs text-neutral-500 line-through">
                      {(item.oldPrice * item.qty).toFixed(2)} €
                    </div>
                  )}
                  <div className="text-xs font-semibold text-neutral-900">
                    {baseLineTotal.toFixed(2)} €
                  </div>
                </div>
                {lineDiscount > 0 && (
                  <div className="text-xxxs text-gray-400">
                    - {Number(lineDiscount).toFixed(2)} €
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Codes promo + badges */}
      <div className="mt-2 space-y-1">
        <button
          type="button"
          onClick={onTogglePromo}
          className="text-xs text-neutral-500 hover:text-neutral-900 underline"
        >
          {showPromo
            ? "Masquer le champ code promo"
            : appliedPromos.length
              ? "Ajouter un autre code promo"
              : "Ajouter un code promo"}
        </button>

        {appliedPromos.length > 0 && (
          <div className="flex flex-wrap gap-1 mt-1">
            {appliedPromos.map((promo) => (
              <button
                key={promo.code}
                type="button"
                onClick={() => onRemovePromoCode(promo.code)}
                className="inline-flex items-center gap-1 rounded-full border border-neutral-300 bg-neutral-50 px-2 py-0.5 text-xxs text-neutral-700 hover:bg-neutral-100"
              >
                <span className="font-medium">{promo.code}</span>
                <span className="text-xxs leading-none">
                  <XIcon className="w-3 h-3" />
                </span>
              </button>
            ))}
          </div>
        )}

        {showPromo && (
          <div>
            <div className="mt-1 flex gap-1">
              <input
                type="text"
                value={promoCode}
                onChange={(e) => onPromoCodeChange(e.target.value)}
                placeholder="Code promo"
                className="flex-1 rounded-lg border border-neutral-200 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-black/70"
              />
              <Button
                type="button"
                onClick={onApplyPromo}
                variant="primary"
                size="sm"
              >
                Appliquer
              </Button>
            </div>
            {promoError && (
              <p className="text-xs text-red-500 mt-2 p-2 bg-red-50 border border-red-100 rounded-lg">{promoError}</p>
            )}
          </div>
        )}


      </div>

      {/* Hook: checkout.summary.extras - Permet d'ajouter des contenus personnalisés dans le récapitulatif */}
      <ModuleHooks
        hookName="checkout.summary.extras"
        context={{
          items,
          subtotal,
          shippingCost,
          total,
        }}
      />

      {/* Totaux */}
      <div className="mt-2 space-y-1 text-xs">
        <div className="flex justify-between">
          <span className="text-neutral-600">Sous-total</span>
          <span className="font-medium text-xs">
            {subtotal.toFixed(2)} €
          </span>
        </div>

        <div className="flex justify-between">
          <span className="text-neutral-600">Livraison</span>
          {shippingMethod ? (
            <span className="font-medium">
              {shippingMethod}
              {" · "}
              {shippingCost === 0
                ? "Gratuite"
                : `${Number(shippingCost).toFixed(2)} €`}
            </span>
          ) : (
            <span className="text-neutral-600">
              À partir de {shippingCost.toFixed(2)} €
            </span>
          )}
        </div>

        {/* 🔥 Réduction livraison */}
        {shippingDiscountTotal?.toFixed && shippingDiscountTotal > 0 && (
          <div className="flex justify-between text-xxs">
            <span className="text-gray-400">Remise livraison</span>
            <span className="text-gray-400">
              - {shippingDiscountTotal.toFixed(2)} €
            </span>
          </div>
        )}

        {/* Remises automatiques */}
        {automaticDiscountTotal?.toFixed && automaticDiscountTotal > 0 && (
          <div className="flex justify-between text-xxs">
            <span className="text-gray-400">Remise automatique</span>
            <span className="text-gray-400">
              - {automaticDiscountTotal.toFixed(2)} €
            </span>
          </div>
        )}

        {productDiscountTotal > 0 && (
          <div className="flex justify-between text-xxs">
            <span className="text-gray-400">Remise produit</span>
            <span className="text-gray-400">
              - {productDiscountTotal.toFixed(2)} €
            </span>
          </div>
        )}

        {orderDiscountTotal > 0 && (
          <div className="flex justify-between text-xxs">
            <span className="text-gray-400">Remise commande</span>
            <span className="text-gray-400">
              - {orderDiscountTotal.toFixed(2)} €
            </span>
          </div>
        )}



        <div className="flex items-center justify-between pt-2 border-t border-neutral-200 mt-2">
          <span className="text-neutral-900 font-semibold text-xs">
            Total TTC
          </span>
          <span className="text-neutral-900 font-semibold text-body-14">
            {total.toFixed(2)} €
          </span>
        </div>
        {/* Taxes incluses */}
        {taxTotal !== undefined && taxTotal > 0 && (
          <div className="flex justify-between text-xxs">
            <span className="text-gray-500 italic">
              dont TVA {taxRate ? `(${taxRate.toFixed(1)}%)` : ''}
            </span>
            <span className="text-gray-500 italic">
              {taxTotal.toFixed(2)} €
            </span>
          </div>
        )}
      </div>
    </div>
  );
}
