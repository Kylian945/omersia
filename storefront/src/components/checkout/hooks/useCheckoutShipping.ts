"use client";

import { useEffect } from "react";
import { useCheckoutContext } from "../CheckoutContext";
import { useCart } from "@/components/cart/CartContext";

export function useCheckoutShipping() {
  const {
    shippingMethods,
    setShippingMethods,
    shippingMethodId,
    setShippingMethodId,
    shippingLoading,
    setShippingLoading,
    shippingError,
    setShippingError,
    selectedShippingMethod,
    shippingCostBase,
    address,
  } = useCheckoutContext();

  const { subtotal } = useCart();

  // Charger les méthodes de livraison
  useEffect(() => {
    let cancelled = false;

    async function loadShippingMethods() {
      setShippingLoading(true);
      setShippingError(null);

      try {
        const params = new URLSearchParams();
        params.append("cart_total", subtotal.toString());

        if (address.zip) {
          params.append("postal_code", address.zip);
        }
        if (address.country) {
          params.append("country_code", address.country);
        }

        const res = await fetch(`/api/shipping-methods?${params.toString()}`, {
          method: "GET",
          credentials: "include",
          headers: {
            Accept: "application/json",
          },
        });

        if (!res.ok) {
          if (cancelled) return;
          setShippingError("Impossible de charger les modes de livraison");
          setShippingLoading(false);
          return;
        }

        const data = await res.json();
        if (cancelled) return;

        setShippingMethods(data.shipping_methods || []);

        // Auto-sélectionner le premier si rien n'est sélectionné
        if (
          !shippingMethodId &&
          data.shipping_methods &&
          data.shipping_methods.length > 0
        ) {
          setShippingMethodId(data.shipping_methods[0].id);
        }

        setShippingLoading(false);
      } catch (err: unknown) {
        if (cancelled) return;
        setShippingError("Erreur réseau");
        setShippingLoading(false);
      }
    }

    loadShippingMethods();

    return () => {
      cancelled = true;
    };
  }, [subtotal, address.zip, address.country]);

  return {
    shippingMethods,
    shippingMethodId,
    setShippingMethodId,
    shippingLoading,
    shippingError,
    selectedShippingMethod,
    shippingCostBase,
  };
}
