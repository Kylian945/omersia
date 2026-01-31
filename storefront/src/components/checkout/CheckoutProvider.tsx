"use client";

import { ReactNode, useMemo, useEffect } from "react";
import type { Address } from "@/lib/api";
import { AuthUser } from "@/lib/types/user-types";
import { CheckoutContext } from "./CheckoutContext";
import { useCheckoutState } from "./hooks/useCheckoutState";
import { useCheckoutAddresses } from "./hooks/useCheckoutAddresses";
import { useCart } from "@/components/cart/CartContext";
import { getErrorMessage } from "@/lib/utils/error-utils";
import { logger } from "@/lib/logger";

type CheckoutProviderProps = {
  initialUser: AuthUser | null;
  initialAddresses: Address[];
  children: ReactNode;
};

export function CheckoutProvider({
  initialUser,
  initialAddresses,
  children,
}: CheckoutProviderProps) {
  const state = useCheckoutState(initialUser, initialAddresses);
  const { items } = useCart();

  // Créer la fonction handleSaveAddress avec accès à l'état
  const handleSaveAddress = async (label: string): Promise<{ success: boolean; error?: string }> => {
    if (!state.effectiveUser) {
      return { success: false, error: "Vous devez être connecté" };
    }

    // Validation
    if (!label.trim()) {
      return { success: false, error: "Le nom de l'adresse est requis" };
    }
    if (!state.address.line1.trim()) {
      return { success: false, error: "L'adresse est requise" };
    }
    if (!state.address.zip.trim()) {
      return { success: false, error: "Le code postal est requis" };
    }
    if (!state.address.city.trim()) {
      return { success: false, error: "La ville est requise" };
    }

    try {
      const res = await fetch("/api/account/addresses", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          label: label,
          line1: state.address.line1,
          line2: state.address.line2,
          postcode: state.address.zip,
          city: state.address.city,
          country: state.address.country,
          is_default_shipping: state.addresses.length === 0,
          is_default_billing: false,
        }),
      });

      if (!res.ok) {
        const errorText = await res.text().catch(() => "Erreur inconnue");
        return { success: false, error: errorText || "Erreur lors de l'ajout de l'adresse" };
      }

      const newAddress = await res.json();
      state.setAddresses((prev) => [...prev, newAddress]);
      state.setSelectedAddressId(newAddress.id);

      return { success: true };
    } catch (err: unknown) {
      return { success: false, error: getErrorMessage(err) || "Erreur réseau" };
    }
  };

  // Créer la fonction handleSaveFirstAddress pour sauvegarder la première adresse
  const handleSaveFirstAddress = async () => {
    if (!state.effectiveUser) {
      state.showErrorModal("Vous devez être connecté");
      return;
    }

    // Validation
    if (!state.address.line1.trim()) {
      state.showErrorModal("L'adresse est requise");
      return;
    }
    if (!state.address.zip.trim()) {
      state.showErrorModal("Le code postal est requis");
      return;
    }
    if (!state.address.city.trim()) {
      state.showErrorModal("La ville est requise");
      return;
    }

    state.setIsSavingFirstAddress(true);

    try {
      const res = await fetch("/api/account/addresses", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          label: "Adresse principale",
          line1: state.address.line1,
          line2: state.address.line2,
          postcode: state.address.zip,
          city: state.address.city,
          country: state.address.country,
          is_default_shipping: true,
          is_default_billing: false,
        }),
      });

      if (!res.ok) {
        const errorText = await res.text().catch(() => "Erreur inconnue");
        state.showErrorModal(errorText || "Erreur lors de l'ajout de l'adresse");
        return;
      }

      const newAddress = await res.json();
      state.setAddresses((prev) => [...prev, newAddress]);
      state.setSelectedAddressId(newAddress.id);

      // Mettre à jour l'adresse avec les données du serveur
      state.updateAddress({
        line1: newAddress.line1,
        line2: newAddress.line2 ?? "",
        zip: newAddress.postcode,
        city: newAddress.city,
        country: newAddress.country || "FR",
      });
    } catch (err: unknown) {
      state.showErrorModal(getErrorMessage(err) || "Erreur réseau");
    } finally {
      state.setIsSavingFirstAddress(false);
    }
  };

  // Créer la fonction handleAddNewAddress avec accès à l'état
  const handleAddNewAddress = async () => {
    if (!state.effectiveUser) {
      state.setAddressModalError("Vous devez être connecté pour ajouter une adresse");
      return;
    }

    // Validation
    if (!state.newAddressForm.label.trim()) {
      state.setAddressModalError("Le label est requis");
      return;
    }
    if (!state.newAddressForm.line1.trim()) {
      state.setAddressModalError("L'adresse est requise");
      return;
    }
    if (!state.newAddressForm.postcode.trim()) {
      state.setAddressModalError("Le code postal est requis");
      return;
    }
    if (!state.newAddressForm.city.trim()) {
      state.setAddressModalError("La ville est requise");
      return;
    }

    state.setAddressModalLoading(true);
    state.setAddressModalError(null);

    try {
      const res = await fetch("/api/account/addresses", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(state.newAddressForm),
      });

      if (!res.ok) {
        const errorText = await res.text().catch(() => "Erreur inconnue");
        state.setAddressModalError(errorText || "Erreur lors de l'ajout de l'adresse");
        return;
      }

      const newAddress = await res.json();
      state.setAddresses((prev) => [...prev, newAddress]);

      if (state.addresses.length === 0) {
        state.setSelectedAddressId(newAddress.id);
      }

      state.setIsAddressModalOpen(false);
    } catch (err: unknown) {
      state.setAddressModalError(getErrorMessage(err) || "Erreur réseau");
    } finally {
      state.setAddressModalLoading(false);
    }
  };

  // Créer la fonction handleApplyPromo
  const handleApplyPromo = async () => {
    if (!state.promoCode.trim()) {
      state.setPromoError("Veuillez entrer un code promo");
      return;
    }

    // Vérifier si le panier contient des articles
    if (items.length === 0) {
      state.setPromoError("Le panier est vide");
      return;
    }

    // Vérifier si déjà appliqué
    if (state.appliedPromos.some((p) => p.code === state.promoCode)) {
      state.setPromoError("Ce code est déjà appliqué");
      return;
    }

    state.setPromoError("");

    try {
      const payload = {
        code: state.promoCode,
        items: items.map((item) => ({
          id: item.id,
          name: item.name,
          price: item.price,
          qty: item.qty,
          variant_id: item.variantId || null,
        })),
        existing_types: state.appliedPromos.map((p) => p.type),
      };

      const res = await fetch("/api/cart/apply-discount", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(payload),
      });

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({}));
        state.setPromoError(
          errorData.message || "Code promo invalide ou non applicable"
        );
        return;
      }

      const data = await res.json();

      // Ajouter le code appliqué
      state.setAppliedPromos((prev) => [
        ...prev,
        {
          code: state.promoCode,
          label: data.label || state.promoCode,
          type: data.type || "order",
          discountAmount: data.discount_amount || 0,
          freeShipping: data.free_shipping || false,
          origin: "manual" as const,
          shippingDiscountAmount: data.shipping_discount_amount || 0,
        },
      ]);

      // Si des ajustements de ligne
      if (data.line_adjustments) {
        state.setLineAdjustmentsByCode((prev) => ({
          ...prev,
          [state.promoCode]: data.line_adjustments,
        }));
      }

      // Clear le champ
      state.setPromoCode("");
    } catch (err: unknown) {
      state.setPromoError("Erreur lors de l'application du code promo");
    }
  };

  // Créer la fonction handleRemovePromo
  const handleRemovePromo = async (code: string) => {
    try {
      // Retirer de la liste locale
      state.setAppliedPromos((prev) => prev.filter((p) => p.code !== code));

      // Retirer les ajustements de ligne
      state.setLineAdjustmentsByCode((prev) => {
        const next = { ...prev };
        delete next[code];
        return next;
      });
    } catch (err: unknown) {
      state.showErrorModal("Erreur lors de la suppression du code promo");
    }
  };

  // Créer la fonction loadAutomaticDiscounts
  const loadAutomaticDiscounts = async () => {
    // Ne charger que si le panier contient des articles
    if (items.length === 0) {
      return;
    }

    try {
      const res = await fetch("/api/cart/apply-automatic-discounts", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          items: items.map((item) => ({
            id: item.id,
            qty: item.qty,
            price: item.price,
            variant_id: item.variantId || null,
          })),
        }),
      });

      if (!res.ok) {
        // Erreur silencieuse pour les réductions automatiques
        logger.error("Échec du chargement des réductions automatiques");
        return;
      }

      const data = await res.json();

      // Retirer les réductions automatiques existantes
      state.setAppliedPromos((prev) =>
        prev.filter((p) => p.origin !== "automatic")
      );

      // Ajouter les nouvelles réductions automatiques
      if (data.promotions && data.promotions.length > 0) {
        interface BackendPromotion {
          code: string;
          label?: string;
          type?: string;
        }
        const automaticPromos = data.promotions.map((promo: BackendPromotion) => ({
          code: promo.code,
          label: promo.label || promo.code,
          type: promo.type || "order",
          discountAmount: 0, // Sera calculé via line_adjustments ou totaux
          freeShipping: data.free_shipping || false,
          origin: "automatic" as const,
          shippingDiscountAmount: 0,
        }));

        state.setAppliedPromos((prev) => [...prev, ...automaticPromos]);
      }

      // Mettre à jour les ajustements de ligne pour les réductions automatiques
      if (data.line_adjustments_by_code) {
        state.setLineAdjustmentsByCode((prev) => ({
          ...prev,
          ...data.line_adjustments_by_code,
        }));
      }

      // Mettre à jour le total des réductions automatiques
      const totalAutoDiscount =
        (data.order_discount_total || 0) +
        (data.product_discount_total || 0) +
        (data.shipping_discount_total || 0);

      state.setAutomaticDiscountTotal(totalAutoDiscount);
    } catch (err: unknown) {
      // Erreur silencieuse - les réductions automatiques ne doivent pas bloquer le checkout
      logger.error("Erreur lors du chargement des réductions automatiques:", err);
    }
  };

  // Charger les réductions automatiques au montage et quand le panier change
  useEffect(() => {
    loadAutomaticDiscounts();
  }, [items]);

  const contextValue = useMemo(
    () => ({
      ...state,
      handleSaveAddress,
      handleSaveFirstAddress,
      handleAddNewAddress,
      handleApplyPromo,
      handleRemovePromo,
    }),
    [state]
  );

  return (
    <CheckoutContext.Provider value={contextValue}>
      {children}
    </CheckoutContext.Provider>
  );
}
