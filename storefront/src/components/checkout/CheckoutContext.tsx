"use client";

import { createContext, useContext, ReactNode } from "react";
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

export type CheckoutContextType = {
  // Navigation
  currentStep: number;
  setCurrentStep: (step: number) => void;
  nextStep: () => void;
  prevStep: () => void;

  // User
  effectiveUser: AuthUser | null;

  // Identity
  identity: CheckoutIdentityState;
  setIdentity: (identity: CheckoutIdentityState) => void;
  updateIdentity: (patch: Partial<CheckoutIdentityState>) => void;

  // Addresses
  addresses: Address[];
  setAddresses: React.Dispatch<React.SetStateAction<Address[]>>;
  selectedAddressId: number | "new" | null;
  setSelectedAddressId: (id: number | "new" | null) => void;
  address: CheckoutAddressFormState;
  setAddress: (address: CheckoutAddressFormState) => void;
  updateAddress: (patch: Partial<CheckoutAddressFormState>) => void;
  newAddressLabel: string;
  setNewAddressLabel: (label: string) => void;
  handleSaveAddress: (label: string) => Promise<{ success: boolean; error?: string }>;
  isSavingFirstAddress: boolean;
  setIsSavingFirstAddress: (saving: boolean) => void;
  handleSaveFirstAddress: () => Promise<void>;

  // Billing Address
  useSameAddressForBilling: boolean;
  setUseSameAddressForBilling: (value: boolean) => void;
  billingAddress: CheckoutAddressFormState;
  setBillingAddress: (address: CheckoutAddressFormState) => void;
  updateBillingAddress: (patch: Partial<CheckoutAddressFormState>) => void;
  selectedBillingAddressId: number | "new" | null;
  setSelectedBillingAddressId: (id: number | "new" | null) => void;

  // Address Modal
  isAddressModalOpen: boolean;
  setIsAddressModalOpen: (open: boolean) => void;
  addressModalLoading: boolean;
  setAddressModalLoading: (loading: boolean) => void;
  addressModalError: string | null;
  setAddressModalError: (error: string | null) => void;
  newAddressForm: CheckoutNewAddressForm;
  setNewAddressForm: (form: CheckoutNewAddressForm) => void;
  updateNewAddressForm: (patch: Partial<CheckoutNewAddressForm>) => void;
  handleAddNewAddress: () => Promise<void>;

  // Shipping
  shippingMethods: ShippingMethod[];
  setShippingMethods: (methods: ShippingMethod[]) => void;
  shippingMethodId: number | null;
  setShippingMethodId: (id: number | null) => void;
  shippingLoading: boolean;
  setShippingLoading: (loading: boolean) => void;
  shippingError: string | null;
  setShippingError: (error: string | null) => void;
  selectedShippingMethod: ShippingMethod | null;
  shippingCostBase: number;

  // Payment
  paymentMethod: "card" | "paypal" | "applepay" | null;
  setPaymentMethod: (method: "card" | "paypal" | "applepay" | null) => void;

  // Promo codes
  showPromo: boolean;
  setShowPromo: (show: boolean) => void;
  promoCode: string;
  setPromoCode: (code: string) => void;
  promoError: string;
  setPromoError: (error: string) => void;
  appliedPromos: CheckoutAppliedPromo[];
  setAppliedPromos: React.Dispatch<React.SetStateAction<CheckoutAppliedPromo[]>>;
  automaticDiscountTotal: number;
  setAutomaticDiscountTotal: (total: number) => void;
  lineAdjustmentsByCode: Record<string, CheckoutLineAdjustmentMap>;
  setLineAdjustmentsByCode: React.Dispatch<React.SetStateAction<Record<string, CheckoutLineAdjustmentMap>>>;
  handleApplyPromo: () => Promise<void>;
  handleRemovePromo: (code: string) => Promise<void>;

  // Tax
  taxTotal: number;
  setTaxTotal: (total: number) => void;
  taxRate: number;
  setTaxRate: (rate: number) => void;
  taxLoading: boolean;
  setTaxLoading: (loading: boolean) => void;

  // Order
  orderId: number | null;
  setOrderId: (id: number | null) => void;
  orderNumber: string | null;
  setOrderNumber: (number: string | null) => void;
  submitting: boolean;
  setSubmitting: (submitting: boolean) => void;

  // Error Modal
  errorModalOpen: boolean;
  errorModalMessage: string;
  showErrorModal: (message: string, onClose?: () => void) => void;
  closeErrorModal: () => void;
};

const CheckoutContext = createContext<CheckoutContextType | null>(null);

export function useCheckoutContext() {
  const context = useContext(CheckoutContext);
  if (!context) {
    throw new Error("useCheckoutContext must be used within CheckoutProvider");
  }
  return context;
}

/**
 * Selector hooks for optimized re-renders
 * Use these instead of useCheckoutContext when you only need specific parts
 */
export function useCheckoutNavigation() {
  const { currentStep, setCurrentStep, nextStep, prevStep } = useCheckoutContext();
  return { currentStep, setCurrentStep, nextStep, prevStep };
}

export function useCheckoutIdentity() {
  const { identity, setIdentity, updateIdentity, effectiveUser } = useCheckoutContext();
  return { identity, setIdentity, updateIdentity, effectiveUser };
}

export function useCheckoutShipping() {
  const {
    shippingMethods, setShippingMethods, shippingMethodId, setShippingMethodId,
    shippingLoading, shippingError, selectedShippingMethod, shippingCostBase
  } = useCheckoutContext();
  return {
    shippingMethods, setShippingMethods, shippingMethodId, setShippingMethodId,
    shippingLoading, shippingError, selectedShippingMethod, shippingCostBase
  };
}

export function useCheckoutPayment() {
  const { paymentMethod, setPaymentMethod } = useCheckoutContext();
  return { paymentMethod, setPaymentMethod };
}

export function useCheckoutOrder() {
  const { orderId, orderNumber, submitting, setSubmitting } = useCheckoutContext();
  return { orderId, orderNumber, submitting, setSubmitting };
}

export { CheckoutContext };
