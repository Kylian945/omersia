/**
 * Types pour les catégories
 */

import type { ListingProduct } from "@/components/product/ListingProducts";

export type CategoryTranslation = {
  locale: string;
  name: string;
  slug: string;
  description?: string;
  meta_title?: string;
  meta_description?: string;
};

export type Category = {
  id: number;
  name: string;
  slug: string;
  description?: string;
  image?: string;
  count: number;
  parent_id?: number;
  parent?: Category;
  position: number;
  translations?: CategoryTranslation[];
  children?: Category[];
  category?: Category;
  products?: ListingProduct[];
};

export type CategoriesResponse = {
  categories: Category[];
};
