import React from 'react';
import { PageBuilder } from './PageBuilder';
import { getThemeWidgets } from '@/lib/theme-widgets';
import { Layout } from './types';

/**
 * Wrapper component that loads theme widgets and passes them to PageBuilder
 * This component is async and should be used in Server Components
 */
export async function PageBuilderWithTheme({
  layout
}: {
  layout: Layout | null | undefined;
}) {
  // Load theme widgets dynamically based on active theme
  const widgets = await getThemeWidgets();

  return <PageBuilder layout={layout} widgets={widgets} />;
}
