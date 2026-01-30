'use client';

import { useMemo } from 'react';

export interface UseThemeSettingsReturn {
  // Colors
  primaryColor: string;
  secondaryColor: string;
  cardBg: string;
  pageBg: string;
  headingColor: string;
  bodyColor: string;
  mutedColor: string;
  borderDefault: string;

  // Typography
  headingFont: string;
  bodyFont: string;
  headingWeight: string;
  bodySize: string;

  // Layout
  borderRadius: string;
  cardStyle: string;

  // Header
  headerStyle: string;
  headerSticky: string;

  // Buttons
  buttonStyle: string;
  buttonRadius: string;
  buttonPrimaryText: string;
  buttonSecondaryText: string;

  // Products
  productCardStyle: 'minimal' | 'bordered' | 'shadow' | 'elevated';
  productHoverEffect: 'none' | 'scale' | 'lift' | 'shadow';
  productBadgesDisplay: 'block' | 'none';
  productQuickAddDisplay: 'block' | 'none';
  productImageRatio: string;
  productsPerRowDesktop: number;
  productsPerRowTablet: number;
  productsPerRowMobile: number;

  // Cart
  cartType: 'drawer' | 'page';
}

/**
 * Hook to access theme settings from CSS variables (client-side)
 *
 * @returns Theme settings object
 *
 * @example
 * ```tsx
 * 'use client';
 *
 * function ProductCard() {
 *   const { productCardStyle, productHoverEffect, borderRadius } = useThemeSettings();
 *
 *   return (
 *     <div className={productCardStyle === 'minimal' ? 'border-0' : 'border'}>
 *       {/* ... *\/}
 *     </div>
 *   );
 * }
 * ```
 */
export function useThemeSettings(): UseThemeSettingsReturn {
  return useMemo(() => {
    if (typeof window === 'undefined') {
      // SSR fallback: return defaults
      return {
        primaryColor: '#111827',
        secondaryColor: '#6366f1',
        cardBg: '#ffffff',
        pageBg: '#f6f6f7',
        headingColor: '#111827',
        bodyColor: '#374151',
        mutedColor: '#6b7280',
        borderDefault: '#e5e7eb',
        headingFont: 'Inter',
        bodyFont: 'Inter',
        headingWeight: '600',
        bodySize: '16px',
        borderRadius: '12px',
        cardStyle: 'bordered',
        headerStyle: 'classic',
        headerSticky: 'yes',
        buttonStyle: 'rounded',
        buttonRadius: '8px',
        buttonPrimaryText: '#ffffff',
        buttonSecondaryText: '#111827',
        productCardStyle: 'bordered',
        productHoverEffect: 'lift',
        productBadgesDisplay: 'block',
        productQuickAddDisplay: 'block',
        productImageRatio: '100%',
        productsPerRowDesktop: 4,
        productsPerRowTablet: 3,
        productsPerRowMobile: 2,
        cartType: 'drawer',
      };
    }

    const style = getComputedStyle(document.documentElement);
    const getVar = (name: string, fallback: string = '') =>
      style.getPropertyValue(name).trim() || fallback;

    return {
      // Colors
      primaryColor: getVar('--theme-primary', '#111827'),
      secondaryColor: getVar('--theme-secondary', '#6366f1'),
      cardBg: getVar('--theme-card-bg', '#ffffff'),
      pageBg: getVar('--theme-page-bg', '#f6f6f7'),
      headingColor: getVar('--theme-heading-color', '#111827'),
      bodyColor: getVar('--theme-body-color', '#374151'),
      mutedColor: getVar('--theme-muted-color', '#6b7280'),
      borderDefault: getVar('--theme-border-default', '#e5e7eb'),

      // Typography
      headingFont: getVar('--theme-heading-font', 'Inter'),
      bodyFont: getVar('--theme-body-font', 'Inter'),
      headingWeight: getVar('--theme-heading-weight', '600'),
      bodySize: getVar('--theme-body-size', '16px'),

      // Layout
      borderRadius: getVar('--theme-border-radius', '12px'),
      cardStyle: getVar('--theme-card-style', 'bordered'),

      // Header
      headerStyle: getVar('--theme-header-style', 'classic'),
      headerSticky: getVar('--theme-header-sticky', 'yes'),

      // Buttons
      buttonStyle: getVar('--theme-button-style', 'rounded'),
      buttonRadius: getVar('--theme-button-radius', '8px'),
      buttonPrimaryText: getVar('--theme-button-primary-text', '#ffffff'),
      buttonSecondaryText: getVar('--theme-button-secondary-text', '#111827'),

      // Products
      productCardStyle: getVar('--theme-product-card-style', 'bordered') as 'minimal' | 'bordered' | 'shadow' | 'elevated',
      productHoverEffect: getVar('--theme-product-hover-effect', 'lift') as 'none' | 'scale' | 'lift' | 'shadow',
      productBadgesDisplay: getVar('--theme-product-badges-display', 'block') as 'block' | 'none',
      productQuickAddDisplay: getVar('--theme-product-quick-add-display', 'block') as 'block' | 'none',
      productImageRatio: getVar('--theme-product-image-ratio', '100%'),
      productsPerRowDesktop: parseInt(getVar('--theme-products-per-row-desktop', '4')),
      productsPerRowTablet: parseInt(getVar('--theme-products-per-row-tablet', '3')),
      productsPerRowMobile: parseInt(getVar('--theme-products-per-row-mobile', '2')),

      // Cart
      cartType: (getVar('--theme-cart-type', 'drawer') || 'drawer') as 'drawer' | 'page',
    };
  }, []);
}
