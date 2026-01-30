import { useCheckoutContext } from "../CheckoutContext";
import { useCart } from "@/components/cart/CartContext";
import { CheckoutSummary } from "./CheckoutSummary";
import { useCheckoutPayment } from "../hooks/useCheckoutPayment";
import type { CheckoutLineAdjustment } from "@/lib/types/checkout-types";

export function CheckoutSummaryWrapper() {
  const { items, subtotal } = useCart();
  const {
    selectedShippingMethod,
    shippingCostBase,
    showPromo,
    setShowPromo,
    taxTotal,
    taxRate,
    automaticDiscountTotal,
  } = useCheckoutContext();

  const {
    promoCode,
    setPromoCode,
    promoError,
    appliedPromos,
    handleApplyPromo,
    handleRemovePromo,
    lineAdjustmentsByCode,
  } = useCheckoutPayment();

  // Calculer les remises
  const orderDiscountTotal = appliedPromos
    .filter((p) => p.type === "order")
    .reduce((sum, p) => sum + p.discountAmount, 0);

  const productDiscountTotal = appliedPromos
    .filter((p) => p.type === "product")
    .reduce((sum, p) => sum + p.discountAmount, 0);

  const shippingDiscountTotal = appliedPromos
    .filter((p) => p.freeShipping || p.type === "shipping")
    .reduce((sum, p) => sum + (p.shippingDiscountAmount || 0), 0);

  // Convertir lineAdjustmentsByCode en format attendu
  const flatLineAdjustments: Record<string, CheckoutLineAdjustment> = {};
  Object.entries(lineAdjustmentsByCode).forEach(([code, adjustments]) => {
    Object.assign(flatLineAdjustments, adjustments);
  });

  // Note: subtotal est déjà TTC (taxes comprises), donc on n'ajoute PAS taxTotal
  const total =
    subtotal +
    shippingCostBase -
    orderDiscountTotal -
    productDiscountTotal -
    shippingDiscountTotal -
    automaticDiscountTotal;

  return (
    <CheckoutSummary
      items={items}
      subtotal={subtotal}
      shippingMethod={selectedShippingMethod?.name || null}
      shippingCost={shippingCostBase}
      total={Math.max(0, total)}
      promoCode={promoCode}
      promoError={promoError}
      showPromo={showPromo}
      onPromoCodeChange={setPromoCode}
      onTogglePromo={() => setShowPromo(!showPromo)}
      onApplyPromo={handleApplyPromo}
      appliedPromos={appliedPromos.map((p) => ({
        code: p.code,
        label: p.label,
      }))}
      onRemovePromoCode={handleRemovePromo}
      lineAdjustments={flatLineAdjustments}
      orderDiscountTotal={orderDiscountTotal}
      productDiscountTotal={productDiscountTotal}
      automaticDiscountTotal={automaticDiscountTotal}
      shippingDiscountTotal={shippingDiscountTotal}
      taxTotal={taxTotal}
      taxRate={taxRate}
    />
  );
}
