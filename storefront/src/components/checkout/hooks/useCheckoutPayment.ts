"use client";

import { useCheckoutContext } from "../CheckoutContext";

/**
 * Hook simplifié qui réexporte les fonctionnalités de paiement du contexte
 * Les fonctions handleApplyPromo et handleRemovePromo sont définies dans CheckoutProvider
 */
export function useCheckoutPayment() {
  const {
    promoCode,
    setPromoCode,
    promoError,
    setPromoError,
    appliedPromos,
    automaticDiscountTotal,
    lineAdjustmentsByCode,
    handleApplyPromo,
    handleRemovePromo,
  } = useCheckoutContext();

  return {
    promoCode,
    setPromoCode,
    promoError,
    setPromoError,
    appliedPromos,
    automaticDiscountTotal,
    lineAdjustmentsByCode,
    handleApplyPromo,
    handleRemovePromo,
  };
}
