/**
 * Types pour les produits, variantes et images
 */

export type ProductImage = {
  id: number;
  url?: string | null;
  path?: string | null;
  alt?: string;
  position?: number | null;
  is_main?: boolean;
};

export type ProductTranslation = {
  locale?: string;
  name?: string | null;
  slug?: string;
  description?: string;
  short_description?: string;
  meta_title?: string;
  meta_description?: string;
};

export type VariantValue = {
  id: number;
  value: string;
  option?: {
    id: number;
    name: string;
  };
};

export type Variant = {
  id: number;
  sku?: string | null;
  name?: string | null;
  is_active?: boolean;
  manage_stock?: boolean;
  stock_qty?: number | null;
  price?: number | null;
  compare_at_price?: number | null;
  values?: VariantValue[];
  image_url?: string;
};

export type ProductCategory = {
  id: number;
  slug: string;
  name: string;
  translations?: Array<{
    locale?: string;
    name?: string;
    slug?: string;
  }>;
};

export type ProductWithVariants = {
  id: number;
  name: string;
  slug: string;
  description?: string;
  base_price?: number;
  price?: number;
  compare_at_price?: number | null;
  stock_qty?: number | null;
  sku?: string | null;
  has_variants?: boolean;
  main_image_url?: string | null;
  images?: ProductImage[];
  variants?: Variant[];
  translations?: ProductTranslation[];
  categories?: ProductCategory[];
  related_products?: ListingProduct[];
  relatedProducts?: ListingProduct[];
};

export type ListingProduct = {
  id: number;
  name: string;
  slug: string;
  base_price: number;
  oldPrice?: number;
  image_url?: string;
  translations?: ProductTranslation[];
};

export type NormalizedImage = {
  url: string;
  alt?: string;
};

export type ImageLike = {
  url: string;
  alt?: string;
};

export type ProductWithImages = {
  images?: Array<{ url: string; alt?: string }>;
};

export type SelectedOptions = Record<string, string>;

export type CartItem = {
  id: number;
  name: string;
  price: number;
  oldPrice?: number;
  qty: number;
  imageUrl?: string | null;
  variantId?: number;
  variantLabel?: string;
};

export type PaginationMeta = {
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
};

export type PaginationLinks = {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
};

export type ProductsResponse = {
  products?: ListingProduct[];
  data?: ListingProduct[];
  total?: number;
  current_page?: number;
  last_page?: number;
  per_page?: number;
  from?: number | null;
  to?: number | null;
  links?: PaginationLinks;
};

export type ProductDetailResponse = ProductWithVariants;
