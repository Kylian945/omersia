/**
 * Types pour les widgets du page builder
 */

// Accordion Widget
export type AccordionItem = {
  title: string;
  content: string;
};

export type AccordionWidgetProps = {
  items: AccordionItem[];
  defaultOpenIndex?: number;
};

// Button Widget
export type ButtonWidgetProps = {
  text: string;
  url?: string;
  variant?: 'primary' | 'secondary';
  size?: 'sm' | 'md' | 'lg';
};

// Container Widget
export type ContainerWidgetProps = {
  children?: React.ReactNode;
  maxWidth?: string;
  padding?: string;
  backgroundColor?: string;
};

// Image Widget
export type ImageWidgetProps = {
  src: string;
  alt?: string;
  width?: number;
  height?: number;
  objectFit?: 'cover' | 'contain' | 'fill' | 'none';
};

// Product Slider Widget
export type ProductSliderWidgetProps = {
  products?: number[];
  category?: number;
  limit?: number;
  title?: string;
};

// Spacer Widget
export type SpacerWidgetProps = {
  height?: string;
};

// Tabs Widget
export type TabItem = {
  label: string;
  content: string;
};

export type TabsWidgetProps = {
  items: TabItem[];
  defaultActiveIndex?: number;
};

// Text Widget
export type TextWidgetProps = {
  content: string;
  align?: 'left' | 'center' | 'right';
  size?: 'sm' | 'md' | 'lg';
};

// Video Widget
export type VideoWidgetProps = {
  url: string;
  autoplay?: boolean;
  loop?: boolean;
  muted?: boolean;
};

// Heading Widget
export type HeadingWidgetProps = {
  text: string;
  level?: 1 | 2 | 3 | 4 | 5 | 6;
  align?: 'left' | 'center' | 'right';
};

// Hero Banner Widget
export type HeroBannerProps = {
  title: string;
  subtitle?: string;
  backgroundImage?: string;
  ctaText?: string;
  ctaUrl?: string;
  height?: string;
};

// Features Bar Widget
export type Feature = {
  icon?: string;
  title: string;
  description?: string;
};

export type FeaturesBarProps = {
  features: Feature[];
};

// Promo Banner Widget
export type PromoBannerProps = {
  text: string;
  backgroundColor?: string;
  textColor?: string;
  link?: string;
};

// Testimonials Widget
export type Testimonial = {
  name: string;
  role?: string;
  content: string;
  avatar?: string;
  rating?: number;
};

export type TestimonialsProps = {
  testimonials: Testimonial[];
  title?: string;
};

// Newsletter Widget
export type NewsletterProps = {
  title?: string;
  description?: string;
  placeholder?: string;
  buttonText?: string;
};

// Categories Grid Widget
export type CategoriesGridWidgetProps = {
  categories?: number[];
  columns?: number;
  showCount?: boolean;
};
