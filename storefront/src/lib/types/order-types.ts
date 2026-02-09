export type OrderAddress = {
  id?: number;
  label?: string;
  line1: string;
  line2?: string | null;
  postcode: string;
  city: string;
  country: string;
  phone?: string | null;
  is_default_billing?: boolean;
  is_default_shipping?: boolean;
};

export type OrderTracking = {
  number?: string | null;
  url?: string | null;
  carrier?: string | null;
  updated_at?: string | null;
};

export type OrderMeta = {
  tracking?: OrderTracking;
};

export type Order = {
  id: number;
  number: string;
  currency: string;
  status: string;
  payment_status: string;
  fulfillment_status: string;
  subtotal: number;
  discount_total: number;
  shipping_total: number;
  tax_total: number;
  total: number;
  customer_email: string | null;
  customer_firstname: string | null;
  customer_lastname: string | null;
  shipping_address: OrderAddress;
  billing_address: OrderAddress;
  placed_at: string;
  items: {
    id: number;
    name: string;
    sku: string | null;
    quantity: number;
    unit_price: number;
    total_price: number;
  }[];
  shipping_method?: {
    id: number;
    code: string;
    name: string;
    price: number;
    delivery_time: string | null;
  } | null;
  meta?: {
    tracking?: {
      number?: string | null;
      url?: string | null;
      carrier?: string | null;
      updated_at?: string | null;
    };
  } | null;
};

export type OrderItemInput = {
  product_id?: number;
  variant_id?: number;
  name: string;
  sku?: string;
  quantity: number;
  unit_price: number;
  total_price: number;
};

export type OrderSummary = {
  id: number;
  number: string;
  status: string;
  total: number;
  placed_at: string;
  items_count: number;
};

export type OrderItemApi = {
  id: number;
  product_id?: number | null;
  variant_id?: number | null;
  name: string;
  sku: string | null;
  quantity: number;
  unit_price: number;
  total_price: number;
  image_url?: string | null;
};

export type OrderApi = {
  id: number;
  number: string;
  currency: string;
  status: string;
  payment_status: string;
  fulfillment_status: string;
  subtotal: number;
  discount_total: number;
  shipping_total: number;
  tax_total: number;
  total: number;
  customer_email: string | null;
  customer_firstname: string | null;
  customer_lastname: string | null;
  shipping_address: OrderAddress;
  billing_address: OrderAddress;
  placed_at: string;
  items: OrderItemApi[];
  shipping_method?: {
    id: number;
    code: string;
    name: string;
    price: number;
    delivery_time: string | null;
  } | null;
  meta?: {
    tracking?: {
      number?: string | null;
      url?: string | null;
      carrier?: string | null;
      updated_at?: string | null;
    };
  } | null;
};