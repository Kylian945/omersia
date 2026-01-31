import { apiJson } from "./api-http";
import { logger } from "./logger";

export type EcommercePageWidget = {
  id: string;
  type: string;
  props: Record<string, unknown>;
};

export type EcommercePageColumn = {
  id: string;
  width: number;
  widgets: EcommercePageWidget[];
  columns?: EcommercePageColumn[]; // Support colonnes imbriquées
};

export type EcommercePageSection = {
  id: string;
  settings: {
    background: string;
    paddingTop: number;
    paddingBottom: number;
    fullWidth?: boolean; // Support pleine largeur
  };
  columns: EcommercePageColumn[];
};

export type EcommercePageContent = {
  sections?: EcommercePageSection[];
  beforeNative?: { sections: EcommercePageSection[] };
  afterNative?: { sections: EcommercePageSection[] };
};

export type EcommercePage = {
  id: number;
  type: string;
  slug: string | null;
  title: string;
  hasNativeContent?: boolean;
  content: EcommercePageContent;
  meta_title?: string;
  meta_description?: string;
};

/**
 * Get an ecommerce page by slug
 */
export async function getEcommercePageBySlug(
  slug: string,
  locale: string = "fr"
): Promise<EcommercePage | null> {
  try {
    const { data } = await apiJson<EcommercePage>(
      `/ecommerce-pages/${slug}?locale=${locale}`
    );
    return data;
  } catch (error) {
    logger.error("Error fetching ecommerce page:", error);
    return null;
  }
}

/**
 * Get an ecommerce page by type and optional slug
 */
export async function getEcommercePageByType(
  type: string,
  slug?: string,
  locale: string = "fr"
): Promise<EcommercePage | null> {
  try {
    const url = slug
      ? `/ecommerce-pages/${type}/${slug}?locale=${locale}`
      : `/ecommerce-pages/${type}?locale=${locale}`;
    const { data } = await apiJson<EcommercePage>(url);
    return data;
  } catch (error) {
    logger.error("Error fetching ecommerce page:", error);
    return null;
  }
}
