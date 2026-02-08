"use client";

import { useCallback } from "react";
import { useCheckoutContext } from "../CheckoutContext";
import type { Address } from "@/lib/api";
import { getErrorMessage } from "@/lib/utils/error-utils";

export function useCheckoutAddresses() {
  const {
    addresses,
    setAddresses,
    isAddressModalOpen,
    setIsAddressModalOpen,
    addressModalLoading,
    setAddressModalLoading,
    addressModalError,
    setAddressModalError,
    newAddressForm,
    setNewAddressForm,
    updateNewAddressForm,
    setSelectedAddressId,
    effectiveUser,
  } = useCheckoutContext();

  const openAddressModal = useCallback(() => {
    setNewAddressForm({
      label: "",
      line1: "",
      line2: "",
      postcode: "",
      city: "",
      country: "FR",
      is_default_billing: false,
      is_default_shipping: false,
    });
    setAddressModalError(null);
    setIsAddressModalOpen(true);
  }, [setNewAddressForm, setAddressModalError, setIsAddressModalOpen]);

  const closeAddressModal = useCallback(() => {
    setIsAddressModalOpen(false);
  }, [setIsAddressModalOpen]);

  const handleAddNewAddress = useCallback(async () => {
    if (!effectiveUser) {
      setAddressModalError("Vous devez être connecté pour ajouter une adresse");
      return;
    }

    // Validation basique
    if (!newAddressForm.label.trim()) {
      setAddressModalError("Le label est requis");
      return;
    }
    if (!newAddressForm.line1.trim()) {
      setAddressModalError("L'adresse est requise");
      return;
    }
    if (!newAddressForm.postcode.trim()) {
      setAddressModalError("Le code postal est requis");
      return;
    }
    if (!newAddressForm.city.trim()) {
      setAddressModalError("La ville est requise");
      return;
    }

    setAddressModalLoading(true);
    setAddressModalError(null);

    try {
      const res = await fetch("/api/account/addresses", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(newAddressForm),
      });

      if (!res.ok) {
        const errorText = await res.text().catch(() => "Erreur inconnue");
        setAddressModalError(errorText || "Erreur lors de l'ajout de l'adresse");
        return;
      }

      const newAddress = await res.json() as Address;

      // Ajouter à la liste
      setAddresses((prev: Address[]) => [...prev, newAddress]);

      // Sélectionner la nouvelle adresse si c'est la seule
      if (addresses.length === 0 && newAddress.id) {
        setSelectedAddressId(newAddress.id);
      }

      // Fermer la modal
      setIsAddressModalOpen(false);
    } catch (err: unknown) {
      setAddressModalError(getErrorMessage(err) || "Erreur réseau");
    } finally {
      setAddressModalLoading(false);
    }
  }, [
    effectiveUser,
    newAddressForm,
    addresses.length,
    setAddressModalLoading,
    setAddressModalError,
    setAddresses,
    setSelectedAddressId,
    setIsAddressModalOpen,
  ]);

  return {
    addresses,
    isAddressModalOpen,
    addressModalLoading,
    addressModalError,
    newAddressForm,
    updateNewAddressForm,
    openAddressModal,
    closeAddressModal,
    handleAddNewAddress,
  };
}
