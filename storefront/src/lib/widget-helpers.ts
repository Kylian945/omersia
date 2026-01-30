/**
 * Widget Helpers - Utility functions for page builder widgets
 *
 * Provides helper functions to convert widget props (gap, alignment, aspectRatio, etc.)
 * into Tailwind CSS classes in a type-safe manner.
 *
 * All helpers return safe default classes if invalid values are provided.
 */

/**
 * Converts gap value to Tailwind CSS gap class
 *
 * @param gap - Gap size ('none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl')
 * @returns Tailwind gap class (e.g., 'gap-4')
 *
 * @example
 * ```typescript
 * getGapClass('md') // 'gap-4'
 * getGapClass('xl') // 'gap-8'
 * getGapClass(undefined) // 'gap-4' (default)
 * ```
 */
export function getGapClass(gap?: string): string {
  const gapMap: Record<string, string> = {
    'none': 'gap-0',
    'xs': 'gap-1',
    'sm': 'gap-2',
    'md': 'gap-4',
    'lg': 'gap-6',
    'xl': 'gap-8',
  };
  return gapMap[gap || 'md'] || 'gap-4';
}

/**
 * Converts alignment value to Tailwind CSS items class
 *
 * @param alignment - Alignment value ('start' | 'center' | 'end' | 'stretch' | 'baseline')
 * @returns Tailwind items class (e.g., 'items-center')
 *
 * @example
 * ```typescript
 * getAlignmentClass('center') // 'items-center'
 * getAlignmentClass('stretch') // 'items-stretch'
 * getAlignmentClass(undefined) // 'items-stretch' (default)
 * ```
 */
export function getAlignmentClass(alignment?: string): string {
  const alignmentMap: Record<string, string> = {
    'start': 'items-start',
    'center': 'items-center',
    'end': 'items-end',
    'stretch': 'items-stretch',
    'baseline': 'items-baseline',
  };
  return alignmentMap[alignment || 'stretch'] || 'items-stretch';
}

/**
 * Converts aspect ratio value to Tailwind CSS aspect class
 *
 * @param aspectRatio - Aspect ratio ('1:1' | '4:3' | '16:9' | '2:1' | '21:9' | 'auto')
 * @returns Tailwind aspect class (e.g., 'aspect-video')
 *
 * @example
 * ```typescript
 * getAspectRatioClass('16:9') // 'aspect-video'
 * getAspectRatioClass('1:1') // 'aspect-square'
 * getAspectRatioClass('auto') // 'h-auto'
 * getAspectRatioClass(undefined) // 'h-auto' (default)
 * ```
 */
export function getAspectRatioClass(aspectRatio?: string): string {
  const aspectMap: Record<string, string> = {
    '1:1': 'aspect-square',
    '4:3': 'aspect-[4/3]',
    '16:9': 'aspect-video',
    '2:1': 'aspect-[2/1]',
    '21:9': 'aspect-[21/9]',
    'auto': 'h-auto',
  };
  return aspectMap[aspectRatio || 'auto'] || 'h-auto';
}

/**
 * Converts object-fit value to Tailwind CSS object class
 *
 * @param objectFit - Object fit value ('contain' | 'cover' | 'fill' | 'scale-down')
 * @returns Tailwind object-fit class (e.g., 'object-cover')
 *
 * @example
 * ```typescript
 * getObjectFitClass('cover') // 'object-cover'
 * getObjectFitClass('contain') // 'object-contain'
 * getObjectFitClass(undefined) // 'object-cover' (default)
 * ```
 */
export function getObjectFitClass(objectFit?: string): string {
  const fitMap: Record<string, string> = {
    'contain': 'object-contain',
    'cover': 'object-cover',
    'fill': 'object-fill',
    'scale-down': 'object-scale-down',
  };
  return fitMap[objectFit || 'cover'] || 'object-cover';
}

/**
 * Converts object-position value to Tailwind CSS object class
 *
 * @param objectPosition - Object position ('top' | 'center' | 'bottom' | 'left' | 'right')
 * @returns Tailwind object-position class (e.g., 'object-center')
 *
 * @example
 * ```typescript
 * getObjectPositionClass('center') // 'object-center'
 * getObjectPositionClass('top') // 'object-top'
 * getObjectPositionClass(undefined) // 'object-center' (default)
 * ```
 */
export function getObjectPositionClass(objectPosition?: string): string {
  const positionMap: Record<string, string> = {
    'top': 'object-top',
    'center': 'object-center',
    'bottom': 'object-bottom',
    'left': 'object-left',
    'right': 'object-right',
  };
  return positionMap[objectPosition || 'center'] || 'object-center';
}

/**
 * Spacing configuration type for padding/margin
 */
export type SpacingValue = 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | number;

export type SpacingConfig = {
  top?: SpacingValue;
  right?: SpacingValue;
  bottom?: SpacingValue;
  left?: SpacingValue;
  all?: SpacingValue; // Shorthand for linking all sides
};

/**
 * Note: All spacing classes are explicitly written in getPaddingClasses and getMarginClasses
 * to ensure Tailwind v4 can detect them during build-time scanning.
 * No dynamic class generation is used.
 */

/**
 * Converts padding configuration to Tailwind CSS classes
 * All classes are explicitly written to ensure Tailwind v4 detection
 */
export function getPaddingClasses(padding?: SpacingConfig): string {
  if (!padding) return '';

  const classes: string[] = [];

  // If 'all' is set, use it for all sides
  if (padding.all !== undefined) {
    const value = padding.all;
    if (value === 'none') classes.push('p-0');
    else if (value === 'xs') classes.push('p-1');
    else if (value === 'sm') classes.push('p-2');
    else if (value === 'md') classes.push('p-4');
    else if (value === 'lg') classes.push('p-6');
    else if (value === 'xl') classes.push('p-8');
    else if (value === '2xl') classes.push('p-12');
    return classes.join(' ');
  }

  // Individual sides - all classes explicitly written
  if (padding.top !== undefined) {
    const value = padding.top;
    if (value === 'none') classes.push('pt-0');
    else if (value === 'xs') classes.push('pt-1');
    else if (value === 'sm') classes.push('pt-2');
    else if (value === 'md') classes.push('pt-4');
    else if (value === 'lg') classes.push('pt-6');
    else if (value === 'xl') classes.push('pt-8');
    else if (value === '2xl') classes.push('pt-12');
  }

  if (padding.right !== undefined) {
    const value = padding.right;
    if (value === 'none') classes.push('pr-0');
    else if (value === 'xs') classes.push('pr-1');
    else if (value === 'sm') classes.push('pr-2');
    else if (value === 'md') classes.push('pr-4');
    else if (value === 'lg') classes.push('pr-6');
    else if (value === 'xl') classes.push('pr-8');
    else if (value === '2xl') classes.push('pr-12');
  }

  if (padding.bottom !== undefined) {
    const value = padding.bottom;
    if (value === 'none') classes.push('pb-0');
    else if (value === 'xs') classes.push('pb-1');
    else if (value === 'sm') classes.push('pb-2');
    else if (value === 'md') classes.push('pb-4');
    else if (value === 'lg') classes.push('pb-6');
    else if (value === 'xl') classes.push('pb-8');
    else if (value === '2xl') classes.push('pb-12');
  }

  if (padding.left !== undefined) {
    const value = padding.left;
    if (value === 'none') classes.push('pl-0');
    else if (value === 'xs') classes.push('pl-1');
    else if (value === 'sm') classes.push('pl-2');
    else if (value === 'md') classes.push('pl-4');
    else if (value === 'lg') classes.push('pl-6');
    else if (value === 'xl') classes.push('pl-8');
    else if (value === '2xl') classes.push('pl-12');
  }

  return classes.join(' ');
}

/**
 * Converts margin configuration to Tailwind CSS classes
 * All classes are explicitly written to ensure Tailwind v4 detection
 */
export function getMarginClasses(margin?: SpacingConfig): string {
  if (!margin) return '';

  const classes: string[] = [];

  // If 'all' is set, use it for all sides
  if (margin.all !== undefined) {
    const value = margin.all;
    if (value === 'none') classes.push('m-0');
    else if (value === 'xs') classes.push('m-1');
    else if (value === 'sm') classes.push('m-2');
    else if (value === 'md') classes.push('m-4');
    else if (value === 'lg') classes.push('m-6');
    else if (value === 'xl') classes.push('m-8');
    else if (value === '2xl') classes.push('m-12');
    return classes.join(' ');
  }

  // Individual sides - all classes explicitly written
  if (margin.top !== undefined) {
    const value = margin.top;
    if (value === 'none') classes.push('mt-0');
    else if (value === 'xs') classes.push('mt-1');
    else if (value === 'sm') classes.push('mt-2');
    else if (value === 'md') classes.push('mt-4');
    else if (value === 'lg') classes.push('mt-6');
    else if (value === 'xl') classes.push('mt-8');
    else if (value === '2xl') classes.push('mt-12');
  }

  if (margin.right !== undefined) {
    const value = margin.right;
    if (value === 'none') classes.push('mr-0');
    else if (value === 'xs') classes.push('mr-1');
    else if (value === 'sm') classes.push('mr-2');
    else if (value === 'md') classes.push('mr-4');
    else if (value === 'lg') classes.push('mr-6');
    else if (value === 'xl') classes.push('mr-8');
    else if (value === '2xl') classes.push('mr-12');
  }

  if (margin.bottom !== undefined) {
    const value = margin.bottom;
    if (value === 'none') classes.push('mb-0');
    else if (value === 'xs') classes.push('mb-1');
    else if (value === 'sm') classes.push('mb-2');
    else if (value === 'md') classes.push('mb-4');
    else if (value === 'lg') classes.push('mb-6');
    else if (value === 'xl') classes.push('mb-8');
    else if (value === '2xl') classes.push('mb-12');
  }

  if (margin.left !== undefined) {
    const value = margin.left;
    if (value === 'none') classes.push('ml-0');
    else if (value === 'xs') classes.push('ml-1');
    else if (value === 'sm') classes.push('ml-2');
    else if (value === 'md') classes.push('ml-4');
    else if (value === 'lg') classes.push('ml-6');
    else if (value === 'xl') classes.push('ml-8');
    else if (value === '2xl') classes.push('ml-12');
  }

  return classes.join(' ');
}
