/**
 * Types pour les menus et la navigation
 */

export type CategoryNode = {
  id: number;
  slug: string | null;
  name: string | null;
  children?: CategoryNode[];
};

export type MenuCategory = CategoryNode;

export type MenuItem = {
  id: number;
  label: string;
  type: "category" | "link" | "text";
  url: string | null;
  category?: MenuCategory | null;
};

export type MenuResponse = {
  slug: string;
  name: string;
  location: string | null;
  items: MenuItem[];
} | null;

export type ShopInfo = {
  name: string;
  display_name: string;
  logo_url: string | null;
};
