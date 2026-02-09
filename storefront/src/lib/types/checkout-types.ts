/**
 * Types pour le processus de checkout et paiement
 */

import { OrderItemInput } from "./order-types";

export type PaymentMethod = {
  id: number;
  name: string;
  code: string;
};

export type PaymentMethodCode = 'stripe' | 'cod' | string;

export type ShippingMethod = {
  id: number;
  code: string;
  name: string;
  description?: string | null;
  price: number;
  original_price?: number;
  delivery_time: string | null;
  is_free?: boolean;
  has_advanced_pricing?: boolean;
};

export type CheckoutOrderPayload = {
  cart_id?: number;
  currency: string;
  shipping_method_id: number;
  customer_email: string;
  customer_firstname?: string | null;
  customer_lastname?: string | null;
  shipping_address: {
    line1: string;
    line2?: string | null;
    postcode: string;
    city: string;
    country: string;
    phone?: string | null;
  };
  billing_address?: {
    line1: string;
    line2?: string | null;
    postcode: string;
    city: string;
    country: string;
    phone?: string | null;
  };
  items: OrderItemInput[];
  discount_total?: number;
  shipping_total?: number;
  tax_total?: number;
  total?: number;
};

// Types utilisés dans CheckoutClient - version locale
export type CheckoutIdentityState = {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
};

export type CheckoutAddressFormState = {
  line1: string;
  line2: string;
  zip: string;
  city: string;
  country: string;
};

export type CheckoutNewAddressForm = {
  label: string;
  line1: string;
  line2: string;
  postcode: string;
  city: string;
  country: string;
  is_default_billing: boolean;
  is_default_shipping: boolean;
};

export type CheckoutAppliedPromo = {
  code: string;
  label: string;
  type: "order" | "product" | "shipping" | "buy_x_get_y" | string;
  discountAmount: number;
  freeShipping: boolean;
  origin: "manual" | "automatic";
  shippingDiscountAmount?: number;
};

export type CheckoutLineAdjustment = {
  id: number;
  variant_id: number | null;
  discount_amount: number;
  is_gift?: boolean;
};

export type CheckoutLineAdjustmentMap = Record<string, CheckoutLineAdjustment>;