import { Suspense } from 'react';
import { PageBuilder } from './PageBuilder';
import { getThemeWidgets } from '@/lib/theme-widgets';
import { Layout } from './types';

/**
 * Loading skeleton for PageBuilder - enables streaming SSR
 */
export function PageBuilderSkeleton() {
  return (
    <div className="animate-pulse">
      {/* Hero section skeleton */}
      <div className="h-[400px] bg-neutral-100" />
      {/* Content sections skeleton */}
      <div className="max-w-7xl mx-auto px-4 py-8 space-y-8">
        <div className="h-8 bg-neutral-100 rounded w-1/3" />
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-64 bg-neutral-100 rounded-lg" />
          ))}
        </div>
      </div>
    </div>
  );
}

/**
 * Internal async component that loads widgets
 */
async function PageBuilderLoader({
  layout
}: {
  layout: Layout | null | undefined;
}) {
  // Load theme widgets dynamically based on active theme
  const widgets = await getThemeWidgets();
  return <PageBuilder layout={layout} widgets={widgets} />;
}

/**
 * Wrapper component that loads theme widgets and passes them to PageBuilder
 * Uses Suspense boundary for streaming SSR support
 */
export function PageBuilderWithTheme({
  layout
}: {
  layout: Layout | null | undefined;
}) {
  return (
    <Suspense fallback={<PageBuilderSkeleton />}>
      <PageBuilderLoader layout={layout} />
    </Suspense>
  );
}
