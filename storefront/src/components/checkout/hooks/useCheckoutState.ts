"use client";

import { useState, useCallback, useEffect, useRef } from "react";
import type { Address } from "@/lib/api";
import {
  CheckoutIdentityState,
  CheckoutAddressFormState,
  CheckoutNewAddressForm,
  CheckoutAppliedPromo,
  ShippingMethod,
  CheckoutLineAdjustmentMap,
} from "@/lib/types/checkout-types";
import { AuthUser } from "@/lib/types/user-types";
import { useCart } from "@/components/cart/CartContext";
import { logger } from "@/lib/logger";

export function useCheckoutState(
  initialUser: AuthUser | null,
  initialAddresses: Address[]
) {
  // Navigation
  const [currentStep, setCurrentStep] = useState(1);

  const nextStep = useCallback(() => {
    setCurrentStep((prev) => Math.min(prev + 1, 4));
  }, []);

  const prevStep = useCallback(() => {
    setCurrentStep((prev) => Math.max(prev - 1, 1));
  }, []);

  // User
  const [effectiveUser, setEffectiveUser] = useState<AuthUser | null>(initialUser);
  const effectiveUserRef = useRef<AuthUser | null>(initialUser);

  // Identity
  const [identity, setIdentity] = useState<CheckoutIdentityState>({
    id: String(effectiveUser?.id ?? ""),
    firstName: effectiveUser?.firstname ?? "",
    lastName: effectiveUser?.lastname ?? "",
    email: effectiveUser?.email ?? "",
    phone: "",
  });

  const updateIdentity = useCallback((patch: Partial<CheckoutIdentityState>) => {
    setIdentity((prev) => ({ ...prev, ...patch }));
  }, []);

  const applyAuthenticatedUser = useCallback((user: AuthUser | null) => {
    effectiveUserRef.current = user;
    setEffectiveUser(user);

    if (!user) {
      return;
    }

    setIdentity((prev) => ({
      ...prev,
      id: String(user.id),
      firstName: prev.firstName || user.firstname || "",
      lastName: prev.lastName || user.lastname || "",
      email: user.email || prev.email,
    }));
  }, []);

  const refreshCheckoutAuth = useCallback(async (): Promise<{
    user: AuthUser | null;
    unavailable: boolean;
  }> => {
    try {
      const res = await fetch("/auth/me", {
        method: "GET",
        credentials: "include",
        cache: "no-store",
        headers: {
          Accept: "application/json",
        },
      });

      if (!res.ok) {
        if (res.status === 401 || res.status === 403) {
          applyAuthenticatedUser(null);
          return {
            user: null,
            unavailable: false,
          };
        }

        return {
          user: effectiveUserRef.current,
          unavailable: true,
        };
      }

      const data = (await res.json().catch(() => null)) as
        | { authenticated?: boolean; user?: AuthUser | null; unavailable?: boolean }
        | null;

      if (data?.unavailable) {
        return {
          user: effectiveUserRef.current,
          unavailable: true,
        };
      }

      const nextUser = data?.authenticated ? data.user ?? null : null;
      applyAuthenticatedUser(nextUser);

      return {
        user: nextUser,
        unavailable: false,
      };
    } catch (error: unknown) {
      logger.warn("Unable to refresh checkout auth state", error);
      return {
        user: effectiveUserRef.current,
        unavailable: true,
      };
    }
  }, [applyAuthenticatedUser]);

  // Addresses
  const [addresses, setAddresses] = useState<Address[]>(initialAddresses || []);
  const [selectedAddressId, setSelectedAddressId] = useState<number | "new" | null>(
    initialAddresses.length
      ? (initialAddresses.find((a) => a.is_default_shipping) ?? initialAddresses[0]).id
      : "new"
  );

  // Initialiser l'adresse avec celle par défaut si elle existe
  const defaultAddress = initialAddresses.length
    ? (initialAddresses.find((a) => a.is_default_shipping) ?? initialAddresses[0])
    : null;

  const [address, setAddress] = useState<CheckoutAddressFormState>({
    line1: defaultAddress?.line1 ?? "",
    line2: defaultAddress?.line2 ?? "",
    zip: defaultAddress?.postcode ?? "",
    city: defaultAddress?.city ?? "",
    country: defaultAddress?.country ?? "FR",
  });

  const updateAddress = useCallback((patch: Partial<CheckoutAddressFormState>) => {
    setAddress((prev) => ({ ...prev, ...patch }));
  }, []);

  const [newAddressLabel, setNewAddressLabel] = useState("");
  const [isSavingFirstAddress, setIsSavingFirstAddress] = useState(false);

  // Billing Address
  const [useSameAddressForBilling, setUseSameAddressForBilling] = useState(true);
  const [billingAddress, setBillingAddress] = useState<CheckoutAddressFormState>({
    line1: "",
    line2: "",
    zip: "",
    city: "",
    country: "FR",
  });

  const updateBillingAddress = useCallback((patch: Partial<CheckoutAddressFormState>) => {
    setBillingAddress((prev) => ({ ...prev, ...patch }));
  }, []);

  const [selectedBillingAddressId, setSelectedBillingAddressId] = useState<number | "new" | null>(
    initialAddresses.length
      ? (initialAddresses.find((a) => a.is_default_billing) ?? initialAddresses[0]).id
      : "new"
  );

  // Address Modal
  const [isAddressModalOpen, setIsAddressModalOpen] = useState(false);
  const [addressModalLoading, setAddressModalLoading] = useState(false);
  const [addressModalError, setAddressModalError] = useState<string | null>(null);
  const [newAddressForm, setNewAddressForm] = useState<CheckoutNewAddressForm>({
    label: "",
    line1: "",
    line2: "",
    postcode: "",
    city: "",
    country: "FR",
    is_default_billing: false,
    is_default_shipping: false,
  });

  const updateNewAddressForm = useCallback((patch: Partial<CheckoutNewAddressForm>) => {
    setNewAddressForm((prev) => ({ ...prev, ...patch }));
  }, []);

  // Shipping
  const [shippingMethods, setShippingMethods] = useState<ShippingMethod[]>([]);
  const [shippingMethodId, setShippingMethodId] = useState<number | null>(null);
  const [shippingLoading, setShippingLoading] = useState(true);
  const [shippingError, setShippingError] = useState<string | null>(null);

  const selectedShippingMethod =
    shippingMethods.find((m) => m.id === shippingMethodId) || null;

  const shippingCostBase =
    selectedShippingMethod?.price ??
    (currentStep < 3 && shippingMethods.length > 0
      ? Math.min(...shippingMethods.map((m) => m.price))
      : 0);

  // Load shipping methods
  const { subtotal } = useCart();

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

        const data = await res.json().catch(() => null);

        if (!res.ok || !data || data.ok === false) {
          throw new Error(
            data?.error || data?.message || "Impossible de charger les livraisons."
          );
        }

        if (!cancelled) {
          const methods: ShippingMethod[] = data.data || [];
          setShippingMethods(methods);

          // Pré-sélectionner la méthode la moins chère
          if (methods.length > 0) {
            const currentMethodStillExists = methods.find((m) => m.id === shippingMethodId);
            if (!shippingMethodId || !currentMethodStillExists) {
              const cheapest = methods.sort((a, b) => a.price - b.price)[0];
              setShippingMethodId(cheapest.id);
            }
          }
        }
      } catch (err: unknown) {
        logger.error(err);
        if (!cancelled) {
          setShippingError(
            (err instanceof Error ? err.message : String(err)) || "Erreur lors du chargement des livraisons."
          );
        }
      } finally {
        if (!cancelled) {
          setShippingLoading(false);
        }
      }
    }

    loadShippingMethods();

    return () => {
      cancelled = true;
    };
  }, [subtotal, address.zip, address.country, shippingMethodId]);

  // Payment
  const [paymentMethod, setPaymentMethod] = useState<
    "card" | "paypal" | "applepay" | "test" | null
  >(null);

  // Promo codes
  const [showPromo, setShowPromo] = useState(false);
  const [promoCode, setPromoCode] = useState("");
  const [promoError, setPromoError] = useState("");
  const [appliedPromos, setAppliedPromos] = useState<CheckoutAppliedPromo[]>([]);
  const [automaticDiscountTotal, setAutomaticDiscountTotal] = useState(0);
  const [lineAdjustmentsByCode, setLineAdjustmentsByCode] = useState<
    Record<string, CheckoutLineAdjustmentMap>
  >({});

  // Tax
  const [taxTotal, setTaxTotal] = useState(0);
  const [taxRate, setTaxRate] = useState(0);
  const [taxLoading, setTaxLoading] = useState(false);

  // Order
  const [orderId, setOrderId] = useState<number | null>(null);
  const [orderNumber, setOrderNumber] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  // Error Modal
  const [errorModalOpen, setErrorModalOpen] = useState(false);
  const [errorModalMessage, setErrorModalMessage] = useState("");
  const [errorModalOnClose, setErrorModalOnClose] = useState<(() => void) | undefined>();

  const showErrorModal = useCallback((message: string, onClose?: () => void) => {
    setErrorModalMessage(message);
    setErrorModalOnClose(() => onClose);
    setErrorModalOpen(true);
  }, []);

  const closeErrorModal = useCallback(() => {
    setErrorModalOpen(false);
    if (errorModalOnClose) {
      errorModalOnClose();
      setErrorModalOnClose(undefined);
    }
  }, [errorModalOnClose]);

  // Reset submitting when changing steps
  useEffect(() => {
    setSubmitting(false);
  }, [currentStep]);

  useEffect(() => {
    void refreshCheckoutAuth();

    const handleAuthChanged = () => {
      void refreshCheckoutAuth();
    };

    const handleVisibilityChange = () => {
      if (document.visibilityState === "visible") {
        void refreshCheckoutAuth();
      }
    };

    window.addEventListener("auth:changed", handleAuthChanged);
    document.addEventListener("visibilitychange", handleVisibilityChange);

    return () => {
      window.removeEventListener("auth:changed", handleAuthChanged);
      document.removeEventListener("visibilitychange", handleVisibilityChange);
    };
  }, [refreshCheckoutAuth]);

  // Create or update order when reaching step 4
  const { cartId, items } = useCart();
  const isCreatingOrder = useRef(false);

  // Load tax calculation
  useEffect(() => {
    let cancelled = false;

    async function loadTaxes() {
      // Ne calculer les taxes que si on a une adresse avec pays et code postal
      if (!address.country || !address.zip || items.length === 0) {
        setTaxTotal(0);
        setTaxRate(0);
        return;
      }

      setTaxLoading(true);

      try {
        const res = await fetch("/api/checkout/calculate-tax", {
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
            })),
            country: address.country,
            postcode: address.zip,
            subtotal: subtotal,
          }),
        });

        if (!cancelled) {
          const data = await res.json().catch(() => null);

          if (data) {
            setTaxTotal(data.tax_total || 0);
            setTaxRate(data.tax_rate || 0);
          } else {
            setTaxTotal(0);
            setTaxRate(0);
          }
        }
      } catch (err: unknown) {
        logger.error("Erreur lors du calcul des taxes:", err);
        if (!cancelled) {
          setTaxTotal(0);
          setTaxRate(0);
        }
      } finally {
        if (!cancelled) {
          setTaxLoading(false);
        }
      }
    }

    loadTaxes();

    return () => {
      cancelled = true;
    };
  }, [subtotal, address.zip, address.country, items]);

  useEffect(() => {
    if (
      currentStep === 4 &&
      !!effectiveUser &&
      !isCreatingOrder.current &&
      shippingMethodId &&
      address.line1 &&
      address.zip &&
      address.city
    ) {
      const createOrUpdateOrder = async () => {
        isCreatingOrder.current = true;
        setSubmitting(true);

        try {
          const { user: currentUser, unavailable: authUnavailable } =
            await refreshCheckoutAuth();

          if (authUnavailable) {
            showErrorModal(
              "Le service d'authentification est indisponible. Veuillez réessayer dans quelques instants."
            );
            setSubmitting(false);
            isCreatingOrder.current = false;
            return;
          }

          if (!currentUser) {
            showErrorModal(
              "Votre session a expiré. Veuillez vous reconnecter pour finaliser la commande.",
              () => {
                window.location.href = "/login?redirect=%2Fcheckout";
              }
            );
            setSubmitting(false);
            isCreatingOrder.current = false;
            return;
          }

          // Récupérer l'ID de l'adresse sélectionnée (pas "new")
          const shippingAddressId = typeof selectedAddressId === 'number' ? selectedAddressId : null;

          if (!shippingAddressId) {
            showErrorModal("Veuillez sélectionner une adresse de livraison");
            setSubmitting(false);
            isCreatingOrder.current = false;
            return;
          }

          // Calculer les réductions
          const promoDiscount = appliedPromos
            .filter((p) => p.type === "order" || p.type === "product")
            .reduce((sum, p) => sum + (p.discountAmount || 0), 0);

          const shippingDiscountTotal = appliedPromos
            .filter((p) => p.freeShipping || (p.shippingDiscountAmount ?? 0) > 0)
            .reduce((sum, p) => sum + (p.shippingDiscountAmount ?? 0), 0);

          // Calculer le total avec réductions
          // Note: subtotal est déjà TTC (taxes comprises), donc on n'ajoute PAS taxTotal
          const finalShippingCost = Math.max(0, shippingCostBase - shippingDiscountTotal);
          const total = subtotal - promoDiscount - automaticDiscountTotal + finalShippingCost;

          const orderPayload = {
            cartId: cartId,
            orderId: orderId, // Envoyer l'orderId s'il existe pour mise à jour
            shippingMethodId: shippingMethodId,
            shippingAddressId: shippingAddressId,
            billingAddressId: useSameAddressForBilling ? shippingAddressId : selectedBillingAddressId,
            items: items,
            subtotal: subtotal,
            shippingCostBase: shippingCostBase,
            shippingDiscountTotal: shippingDiscountTotal,
            shippingCost: finalShippingCost,
            promoDiscount: promoDiscount,
            automaticDiscountTotal: automaticDiscountTotal,
            taxTotal: taxTotal,
            total: total,
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

          if (res.status === 401) {
            showErrorModal(
              "Votre session a expiré. Veuillez vous reconnecter pour finaliser la commande.",
              () => {
                window.location.href = "/login?redirect=%2Fcheckout";
              }
            );
            setSubmitting(false);
            isCreatingOrder.current = false;
            return;
          }

          if (!res.ok) {
            const errorData = await res.json().catch(() => ({}));
            showErrorModal(
              errorData.message || "Erreur lors de la création de la commande"
            );
            setSubmitting(false);
            isCreatingOrder.current = false;
            return;
          }

          const data = await res.json();

          setOrderId(data.id);
          setOrderNumber(data.number);
          setSubmitting(false);
          isCreatingOrder.current = false;
        } catch {
          showErrorModal("Erreur réseau lors de la commande");
          setSubmitting(false);
          isCreatingOrder.current = false;
        }
      };

      createOrUpdateOrder();
    }
  }, [
    currentStep,
    effectiveUser,
    shippingMethodId,
    selectedAddressId,
    appliedPromos,
    automaticDiscountTotal,
    subtotal,
    shippingCostBase,
    taxTotal,
    items,
    cartId,
    orderId,
    useSameAddressForBilling,
    selectedBillingAddressId,
    address.line1,
    address.zip,
    address.city,
    refreshCheckoutAuth,
    showErrorModal,
  ]);

  return {
    // Navigation
    currentStep,
    setCurrentStep,
    nextStep,
    prevStep,

    // User
    effectiveUser,

    // Identity
    identity,
    setIdentity,
    updateIdentity,

    // Addresses
    addresses,
    setAddresses,
    selectedAddressId,
    setSelectedAddressId,
    address,
    setAddress,
    updateAddress,
    newAddressLabel,
    setNewAddressLabel,
    isSavingFirstAddress,
    setIsSavingFirstAddress,

    // Billing Address
    useSameAddressForBilling,
    setUseSameAddressForBilling,
    billingAddress,
    setBillingAddress,
    updateBillingAddress,
    selectedBillingAddressId,
    setSelectedBillingAddressId,

    // Address Modal
    isAddressModalOpen,
    setIsAddressModalOpen,
    addressModalLoading,
    setAddressModalLoading,
    addressModalError,
    setAddressModalError,
    newAddressForm,
    setNewAddressForm,
    updateNewAddressForm,

    // Shipping
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

    // Payment
    paymentMethod,
    setPaymentMethod,

    // Promo codes
    showPromo,
    setShowPromo,
    promoCode,
    setPromoCode,
    promoError,
    setPromoError,
    appliedPromos,
    setAppliedPromos,
    automaticDiscountTotal,
    setAutomaticDiscountTotal,
    lineAdjustmentsByCode,
    setLineAdjustmentsByCode,

    // Tax
    taxTotal,
    setTaxTotal,
    taxRate,
    setTaxRate,
    taxLoading,
    setTaxLoading,

    // Order
    orderId,
    setOrderId,
    orderNumber,
    setOrderNumber,
    submitting,
    setSubmitting,

    // Error Modal
    errorModalOpen,
    errorModalMessage,
    showErrorModal,
    closeErrorModal,
  };
}
