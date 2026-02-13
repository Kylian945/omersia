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
    <div className="theme-checkout-summary rounded-2xl bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] p-4">
      <h3 className="text-xs font-semibold text-[var(--theme-heading-color,#111827)] mb-2">
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
              className="flex items-center gap-2 text-xs border-b border-[var(--theme-border-default,#e5e7eb)] pb-2"
            >
              <div className="relative w-10 h-10 bg-[var(--theme-page-bg,#f6f6f7)] rounded-lg overflow-hidden flex items-center justify-center">
                {item.imageUrl ? (
                  <OptimizedImage
                    src={item.imageUrl}
                    alt={item.name}
                    fill
                    sizes="40px"
                    className="object-cover"
                    fallback={<span className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">Image</span>}
                  />
                ) : (
                  <span className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">Image</span>
                )}
              </div>
              <div className="flex-1">
                <div className="text-xs font-medium text-[var(--theme-heading-color,#111827)] line-clamp-2">
                  {item.name}
                </div>
                {item.variantLabel && (
                  <div className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
                    {item.variantLabel}
                  </div>
                )}
                <div className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
                  Qté {item.qty}
                </div>
                {adj?.is_gift && (
                  <div className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
                    Article offert
                  </div>
                )}
              </div>
              <div className="flex flex-col items-end gap-0.5">
                <div className="flex items-baseline gap-1">
                  {item.oldPrice && (
                    <div className="text-xxs text-[var(--theme-muted-color,#6b7280)] line-through">
                      {(item.oldPrice * item.qty).toFixed(2)} €
                    </div>
                  )}
                  <div className="text-xs font-semibold text-[var(--theme-heading-color,#111827)]">
                    {baseLineTotal.toFixed(2)} €
                  </div>
                </div>
                {lineDiscount > 0 && (
                  <div className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
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
          className="text-xs text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-heading-color,#111827)] underline"
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
                className="inline-flex items-center gap-1 rounded-full border border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-page-bg,#f6f6f7)] px-2 py-0.5 text-xxs text-[var(--theme-body-color,#374151)] hover:bg-[var(--theme-input-bg,#ffffff)]"
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
                className="flex-1 rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
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
          <span className="text-[var(--theme-muted-color,#6b7280)]">Sous-total</span>
          <span className="font-medium text-xs">
            {subtotal.toFixed(2)} €
          </span>
        </div>

        <div className="flex justify-between">
          <span className="text-[var(--theme-muted-color,#6b7280)]">Livraison</span>
          {shippingMethod ? (
            <span className="font-medium">
              {shippingMethod}
              {" · "}
              {shippingCost === 0
                ? "Gratuite"
                : `${Number(shippingCost).toFixed(2)} €`}
            </span>
          ) : (
            <span className="text-[var(--theme-muted-color,#6b7280)]">
              À partir de {shippingCost.toFixed(2)} €
            </span>
          )}
        </div>

        {/* 🔥 Réduction livraison */}
        {shippingDiscountTotal?.toFixed && shippingDiscountTotal > 0 && (
          <div className="flex justify-between text-xxs">
            <span className="text-[var(--theme-muted-color,#6b7280)]">Remise livraison</span>
            <span className="text-[var(--theme-muted-color,#6b7280)]">
              - {shippingDiscountTotal.toFixed(2)} €
            </span>
          </div>
        )}

        {/* Remises automatiques */}
        {automaticDiscountTotal?.toFixed && automaticDiscountTotal > 0 && (
          <div className="flex justify-between text-xxs">
            <span className="text-[var(--theme-muted-color,#6b7280)]">Remise automatique</span>
            <span className="text-[var(--theme-muted-color,#6b7280)]">
              - {automaticDiscountTotal.toFixed(2)} €
            </span>
          </div>
        )}

        {productDiscountTotal > 0 && (
          <div className="flex justify-between text-xxs">
            <span className="text-[var(--theme-muted-color,#6b7280)]">Remise produit</span>
            <span className="text-[var(--theme-muted-color,#6b7280)]">
              - {productDiscountTotal.toFixed(2)} €
            </span>
          </div>
        )}

        {orderDiscountTotal > 0 && (
          <div className="flex justify-between text-xxs">
            <span className="text-[var(--theme-muted-color,#6b7280)]">Remise commande</span>
            <span className="text-[var(--theme-muted-color,#6b7280)]">
              - {orderDiscountTotal.toFixed(2)} €
            </span>
          </div>
        )}



        <div className="flex items-center justify-between pt-2 border-t border-[var(--theme-border-default,#e5e7eb)] mt-2">
          <span className="text-[var(--theme-heading-color,#111827)] font-semibold text-xs">
            Total TTC
          </span>
          <span className="text-[var(--theme-heading-color,#111827)] font-semibold text-body-14">
            {total.toFixed(2)} €
          </span>
        </div>
        {/* Taxes incluses */}
        {taxTotal !== undefined && taxTotal > 0 && (
          <div className="flex justify-between text-xxs">
            <span className="text-[var(--theme-muted-color,#6b7280)] italic">
              dont TVA {taxRate ? `(${taxRate.toFixed(1)}%)` : ''}
            </span>
            <span className="text-[var(--theme-muted-color,#6b7280)] italic">
              {taxTotal.toFixed(2)} €
            </span>
          </div>
        )}
      </div>
    </div>
  );
}
