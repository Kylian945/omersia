import { cache } from 'react';
import { apiJson, hasApiKey } from './api-http';

export interface ProductSettings {
  product_card_style: 'minimal' | 'bordered' | 'shadow' | 'elevated';
  product_hover_effect: 'none' | 'scale' | 'lift' | 'shadow';
  show_product_badges: 'yes' | 'no';
  show_quick_add: 'yes' | 'no';
  product_image_ratio: 'square' | 'portrait' | 'landscape';
  products_per_row_desktop: number;
  products_per_row_tablet: number;
  products_per_row_mobile: number;
}

export interface ThemeSettings {
  colors: {
    primary: string;
    secondary: string;
    accent: string;
    background_main: string;
    background_card: string;
    text_primary: string;
    text_secondary: string;
  };
  typography: {
    heading_font: string;
    body_font: string;
    heading_weight: string;
    body_size: string;
  };
  layout: {
    border_radius: string;
    card_style: string;
  };
  header: {
    header_style: string;
  };
  buttons: {
    button_style: string;
  };
  cart: {
    cart_type: string;
  };
  products: ProductSettings;
}

export interface ThemeSettingSchema {
  label: string;
  type: 'color' | 'select' | 'number' | 'text';
  default: string | number;
  description?: string;
  options?: string[] | Record<string, string>;
  min?: number;
  max?: number;
}

export interface ThemeSettingsSchema {
  [group: string]: {
    [key: string]: ThemeSettingSchema;
  };
}

export interface ThemeResponse {
  settings: ThemeSettings;
  settings_schema: ThemeSettingsSchema | null;
  css_variables: string;
  component_path: string;
  theme_slug: string;
}

/**
 * Fetch active theme settings from the backend
 * Wrapped with React cache() for per-request deduplication
 */
export const getThemeSettings = cache(async (): Promise<ThemeResponse> => {
  // Ne pas faire d'appel API si la clé n'est pas configurée
  if (!hasApiKey()) {
    // Retourner un thème par défaut minimal
    return {
      settings: {
        colors: {
          primary: '#000000',
          secondary: '#666666',
          accent: '#0066cc',
          background_main: '#ffffff',
          background_card: '#f9fafb',
          text_primary: '#111827',
          text_secondary: '#6b7280',
        },
        typography: {
          heading_font: 'Inter',
          body_font: 'Inter',
          heading_weight: '700',
          body_size: '16px',
        },
        layout: {
          border_radius: '8px',
          card_style: 'shadow',
        },
        header: {
          header_style: 'default',
        },
        buttons: {
          button_style: 'default',
        },
        cart: {
          cart_type: 'drawer',
        },
        products: {
          product_card_style: 'bordered',
          product_hover_effect: 'lift',
          show_product_badges: 'yes',
          show_quick_add: 'yes',
          product_image_ratio: 'square',
          products_per_row_desktop: 4,
          products_per_row_tablet: 3,
          products_per_row_mobile: 2,
        },
      },
      settings_schema: null,
      css_variables: '',
      component_path: 'vision',
      theme_slug: 'vision',
    };
  }

  const response = await apiJson<ThemeResponse>('/theme/settings', {
    cache: 'no-store',
  });


  const { data } = response;

  // Si l'API échoue (clé invalide, erreur serveur, etc.), retourner le thème par défaut
  if (!data) {
    return {
      settings: {
        colors: {
          primary: '#000000',
          secondary: '#666666',
          accent: '#0066cc',
          background_main: '#ffffff',
          background_card: '#f9fafb',
          text_primary: '#111827',
          text_secondary: '#6b7280',
        },
        typography: {
          heading_font: 'Inter',
          body_font: 'Inter',
          heading_weight: '700',
          body_size: '16px',
        },
        layout: {
          border_radius: '8px',
          card_style: 'shadow',
        },
        header: {
          header_style: 'default',
        },
        buttons: {
          button_style: 'default',
        },
        cart: {
          cart_type: 'drawer',
        },
        products: {
          product_card_style: 'bordered',
          product_hover_effect: 'lift',
          show_product_badges: 'yes',
          show_quick_add: 'yes',
          product_image_ratio: 'square',
          products_per_row_desktop: 4,
          products_per_row_tablet: 3,
          products_per_row_mobile: 2,
        },
      },
      settings_schema: null,
      css_variables: '',
      component_path: 'vision',
      theme_slug: 'vision',
    };
  }

  return {
    ...data,
    component_path: data.component_path?.trim() || 'vision',
    theme_slug: data.theme_slug?.trim() || 'vision',
  };
});
