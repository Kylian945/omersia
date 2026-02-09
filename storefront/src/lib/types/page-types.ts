/**
 * Types pour les pages CMS
 */

import { Layout } from "../../components/builder/types";

export type Page = {
  id: number;
  slug: string;
  title: string;
  meta_title?: string | null;
  meta_description?: string | null;
  layout: Layout;
};

export type PageResponse = {
  id: number;
  slug: string;
  title: string;
  meta_title?: string | null;
  meta_description?: string | null;
  layout?: Layout;
  blocks?: Layout;
  content_json?: Layout;
};
