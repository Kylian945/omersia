export type Address = {
  id: number;
  user_id: number;
  label: string;
  first_name: string | null;
  last_name: string | null;
  company: string | null;
  line1: string;
  line2: string | null;
  postcode: string;
  city: string;
  state: string | null;
  country: string;
  phone: string | null;
  is_default_billing: boolean;
  is_default_shipping: boolean;
  created_at: string;
  updated_at: string;
};

export type AddressInput = {
  label: string;
  first_name?: string;
  last_name?: string;
  company?: string;
  line1: string;
  line2?: string;
  postcode: string;
  city: string;
  country?: string;
  phone?: string;
  is_default_billing?: boolean;
  is_default_shipping?: boolean;
};