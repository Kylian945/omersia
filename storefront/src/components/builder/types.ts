import type { SpacingConfig } from '@/lib/widget-helpers';

export type WidgetBase = {
  id: string;
  type: string;
  props?: Record<string, unknown> & {
    padding?: SpacingConfig;
    margin?: SpacingConfig;
    // Legacy support
    paddingTop?: number;
    paddingBottom?: number;
  };
  visibility?: {
    desktop?: boolean;
    tablet?: boolean;
    mobile?: boolean;
  };
};

export type Column = {
  id: string;
  width?: number; // en % (deprecated, use desktopWidth)
  /** Desktop width in percentage (0-100), validated before render */
  desktopWidth?: number;
  /** Mobile width in percentage (0-100), validated before render */
  mobileWidth?: number;
  padding?: SpacingConfig;
  margin?: SpacingConfig;
  visibility?: {
    desktop?: boolean;
    tablet?: boolean;
    mobile?: boolean;
  };
  widgets: WidgetBase[];
  columns?: Column[]; // Support pour colonnes imbriquées
};

export type Section = {
  id: string;
  settings?: {
    background?: string;
    paddingTop?: number; // Legacy support
    paddingBottom?: number; // Legacy support
    padding?: SpacingConfig; // New spacing system
    margin?: SpacingConfig; // New spacing system
    fullWidth?: boolean;
    gap?: 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    alignment?: 'start' | 'center' | 'end' | 'stretch' | 'baseline';
  };
  visibility?: {
    desktop?: boolean;
    tablet?: boolean;
    mobile?: boolean;
  };
  columns: Column[];
};

export type Layout = {
  sections: Section[];
};
