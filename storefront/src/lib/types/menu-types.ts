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

export type CmsPageNode = {
  id: number;
  slug: string | null;
  title: string | null;
};

export type MenuItem = {
  id: number;
  label: string;
  type: "category" | "cms_page" | "link" | "text";
  url: string | null;
  category?: MenuCategory | null;
  cms_page?: CmsPageNode | null;
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
