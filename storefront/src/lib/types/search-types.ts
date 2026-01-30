/**
 * Types pour la recherche
 */

import { ProductTranslation } from './product-types';
import { Category } from './category-types';

export type SearchProduct = {
  id: number;
  slug: string;
  name: string;
  price: number;
  images?: Array<{ url: string; alt?: string }>;
  stock_qty: number;
  compare_at_price?: number | null;
  main_image_url?: string | null;
  has_variants?: boolean;
  from_price?: number;
  translations?: ProductTranslation[];
};

export type SearchResponse = {
  query: string;
  total: number;
  products: SearchProduct[];
  facets: {
    categories: Category[];
    price_range: {
      min: number;
      max: number;
    };
  };
};
