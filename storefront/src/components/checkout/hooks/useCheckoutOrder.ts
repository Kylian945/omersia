"use client";

import { useCallback } from "react";
import { useRouter } from "next/navigation";
import { useCheckoutContext } from "../CheckoutContext";
import { useCart } from "@/components/cart/CartContext";

export function useCheckoutOrder() {
  const router = useRouter();
  const {
    identity,
    address,
    billingAddress,
    useSameAddressForBilling,
    shippingMethodId,
    taxTotal,
    submitting,
    setSubmitting,
    setOrderId,
    setOrderNumber,
    showErrorModal,
  } = useCheckoutContext();

  const { items, subtotal, cartId, clear: clearCart } = useCart();

  const handleSubmitOrder = useCallback(async () => {
    if (!shippingMethodId) {
      showErrorModal("Veuillez sélectionner un mode de livraison");
      return;
    }

    setSubmitting(true);

    try {
      const orderPayload = {
        cart_id: cartId,
        currency: "EUR",
        shipping_method_id: shippingMethodId,
        customer_email: identity.email,
        customer_firstname: identity.firstName || null,
        customer_lastname: identity.lastName || null,
        shipping_address: {
          line1: address.line1,
          line2: address.line2 || null,
          postcode: address.zip,
          city: address.city,
          country: address.country,
          phone: identity.phone || null,
        },
        billing_address: useSameAddressForBilling
          ? undefined
          : {
              line1: billingAddress.line1,
              line2: billingAddress.line2 || null,
              postcode: billingAddress.zip,
              city: billingAddress.city,
              country: billingAddress.country,
              phone: identity.phone || null,
            },
        items: items.map((item) => ({
          product_id: item.id,
          variant_id: item.variantId,
          quantity: item.qty,
          unit_price: item.price,
        })),
        tax_total: taxTotal,
        total: subtotal + taxTotal,
      };

      const res = await fetch("/api/checkout/order", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(orderPayload),
      });

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({}));
        showErrorModal(
          errorData.message || "Erreur lors de la création de la commande"
        );
        setSubmitting(false);
        return;
      }

      const data = await res.json();

      setOrderId(data.order.id);
      setOrderNumber(data.order.number);

      // Si paiement réussi directement (COD, etc.)
      if (data.order.payment_status === "paid") {
        clearCart();
        router.push(`/checkout/success/${data.order.number}`);
      }

      // Sinon, on reste sur l'étape 4 pour le paiement Stripe
      setSubmitting(false);
    } catch (err: unknown) {
      showErrorModal("Erreur réseau lors de la commande");
      setSubmitting(false);
    }
  }, [
    shippingMethodId,
    identity,
    address,
    billingAddress,
    useSameAddressForBilling,
    items,
    subtotal,
    taxTotal,
    cartId,
    setSubmitting,
    setOrderId,
    setOrderNumber,
    showErrorModal,
    clearCart,
    router,
  ]);

  return {
    submitting,
    handleSubmitOrder,
  };
}
