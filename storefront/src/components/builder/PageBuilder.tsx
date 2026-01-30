import React from "react";
import { Container } from "@/components/common/Container";
import { WidgetBase, Column, Layout } from "./types";
import { ContainerProvider } from "./ContainerContext";
import { validateCSSPercentage } from "@/lib/css-sanitizer";
import { getGapClass, getAlignmentClass, getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateGap, validateAlignment, validateSpacingConfig } from "@/lib/css-variable-sanitizer";

// Type for theme widgets module - dynamic object containing widgets
export type ThemeWidgets = Record<string, React.ComponentType<Record<string, unknown>>>;

// Mapping between widget types (from backend) and component names
// This is the only place to update when adding new widget types
const WIDGET_TYPE_TO_COMPONENT: Record<string, string> = {
  // Basic widgets
  heading: "HeadingWidget",
  text: "TextWidget",
  image: "ImageWidget",
  video: "VideoWidget",
  button: "ButtonWidget",
  accordion: "AccordionWidget",
  tabs: "TabsWidget",

  // Layout widgets
  spacer: "SpacerWidget",
  container: "ContainerWidget",

  // E-commerce widgets
  hero_banner: "HeroBanner",
  features_bar: "FeaturesBar",
  categories_grid: "CategoriesGridWidget",
  promo_banner: "PromoBanner",
  testimonials: "Testimonials",
  newsletter: "Newsletter",
  product_slider: "ProductSliderWidget",
};

// Helper function to generate visibility classes based on visibility settings
function getVisibilityClasses(visibility?: { desktop?: boolean; tablet?: boolean; mobile?: boolean }): string {
  const visibilitySettings = visibility || { desktop: true, tablet: true, mobile: true };
  const classes: string[] = [];

  // Default: visible everywhere
  // Mobile: < 1024px (< lg)
  // Tablet: 1024px - 1279px (lg)
  // Desktop: >= 1280px (xl, 2xl)

  // Normalize undefined to true (visible by default)
  const mobile = visibilitySettings.mobile !== false;
  const tablet = visibilitySettings.tablet !== false;
  const desktop = visibilitySettings.desktop !== false;

  // If all are false, hide completely
  if (!desktop && !tablet && !mobile) {
    return "hidden";
  }

  if (mobile && tablet && desktop) {
    return "";
  }

  classes.push("hidden");

  if (mobile) {
    classes.push("max-md:block");
  }

  if (tablet && !desktop) {
    classes.push("md:block xl:hidden");
  } else if (tablet && desktop) {
    classes.push("lg:block");
  }

  if (desktop && !tablet) {
    classes.push("xl:block");
  }

  return classes.join(" ");
}

function RenderWidget({
  widget,
  widgets
}: {
  widget: WidgetBase;
  widgets: ThemeWidgets;
}): React.JSX.Element | null {
  const props = widget.props || {};
  const visibilityClasses = getVisibilityClasses(widget.visibility);

  // Get component name from mapping
  const componentName = WIDGET_TYPE_TO_COMPONENT[widget.type];

  // If no mapping exists for this widget type, return null
  if (!componentName) {
    console.warn(`Widget type "${widget.type}" is not mapped to a component`);
    return null;
  }

  // Get the component from the theme widgets
  const WidgetComponent = widgets[componentName];

  // If theme doesn't have this widget, return null
  if (!WidgetComponent) {
    console.warn(`Component "${componentName}" not found in current theme for widget type "${widget.type}"`);
    return null;
  }

  // Special handling for container widget (needs renderWidget prop)
  const isContainer = widget.type === "container";
  const widgetContent = isContainer ? (
    <WidgetComponent
      props={props}
      renderWidget={(w: WidgetBase) => <RenderWidget widget={w} widgets={widgets} />}
    />
  ) : (
    // Check if widget uses "props" wrapper or spreads props directly
    // Most basic widgets use props={props}, e-commerce widgets use {...props}
    widget.type.includes('_') || ['hero_banner', 'features_bar', 'categories_grid', 'promo_banner', 'testimonials', 'newsletter', 'product_slider'].includes(widget.type)
      ? <WidgetComponent {...props} />
      : <WidgetComponent props={props} />
  );

  // Wrap widget with visibility classes
  return (
    <div className={visibilityClasses}>
      {widgetContent}
    </div>
  );
}

function RenderColumn({
  column,
  widgets
}: {
  column: Column;
  widgets: ThemeWidgets;
}) {
  const visibilityClasses = getVisibilityClasses(column.visibility);

  // Validate and get spacing classes
  // Note: We only apply padding to columns, not margin
  // Margins would add to the gap and affect grid layout
  const paddingConfig = validateSpacingConfig(column.padding);
  const paddingClasses = getPaddingClasses(paddingConfig);

  return (
    <div
      key={column.id}
      className={`space-y-3 ${visibilityClasses} ${paddingClasses}`.trim()}
      style={{
        minWidth: 0, // Prevents overflow in grid
        maxWidth: '100%', // Ensure column doesn't exceed its grid cell
        boxSizing: 'border-box',
        overflowWrap: 'break-word', // Break long words
        wordBreak: 'break-word', // Break long words
      }}
    >

      {/* Colonnes imbriquées */}
      {column.columns && column.columns.length > 0 && (
        <div className="grid gap-4" style={{ gridTemplateColumns: `repeat(${column.columns.length}, 1fr)` }}>
          {column.columns.map((nestedCol) => (
            <RenderColumn key={nestedCol.id} column={nestedCol} widgets={widgets} />
          ))}
        </div>
      )}

      {/* Widgets */}
      {column.widgets?.map((w) => (
        <RenderWidget key={w.id} widget={w} widgets={widgets} />
      ))}
    </div>
  );
}

export function PageBuilder({
  layout,
  widgets
}: {
  layout: Layout | null | undefined;
  widgets: ThemeWidgets;
}) {
  if (!layout || !Array.isArray(layout.sections) || layout.sections.length === 0) {
    return null;
  }

  return (
    <div className="mt-4">
      {layout.sections.map((section) => {
        const s = section.settings || {};
        const bg = s.background || "#ffffff";
        const visibilityClasses = getVisibilityClasses(section.visibility);

        // Validate and get gap/alignment classes
        const safeGap = validateGap(s.gap);
        const safeAlignment = validateAlignment(s.alignment);
        const gapClass = getGapClass(safeGap);
        const alignmentClass = getAlignmentClass(safeAlignment);

        // Validate and get spacing classes (with backward compatibility)
        const paddingConfig = validateSpacingConfig(s.padding);
        const marginConfig = validateSpacingConfig(s.margin);

        // Backward compatibility: if new padding system not used, fall back to legacy paddingTop/paddingBottom
        const hasLegacyPadding = !s.padding && (s.paddingTop !== undefined || s.paddingBottom !== undefined);
        let legacyPaddingStyle: Record<string, string> = {};

        if (hasLegacyPadding) {
          const paddingTop = s.paddingTop ?? 32;
          const paddingBottom = s.paddingBottom ?? 32;
          legacyPaddingStyle = {
            paddingTop: `${paddingTop}px`,
            paddingBottom: `${paddingBottom}px`,
          };
        }

        const paddingClasses = hasLegacyPadding ? '' : getPaddingClasses(paddingConfig);
        const marginClasses = getMarginClasses(marginConfig);

        // Calculate grid template columns based on column widths
        // Use fractional units (fr) instead of percentages to avoid overflow with gap
        const gridTemplateColumns = section.columns?.map((col) => {
          // Sanitize width values - convert to number and fallback to 100 if invalid
          const rawDesktopWidth = col.desktopWidth || col.width || 100;
          const rawMobileWidth = col.mobileWidth || 100;

          // Only accept valid numbers - reject strings that aren't purely numeric
          let desktopWidth = 100;
          let mobileWidth = 100;

          if (typeof rawDesktopWidth === 'number' && !isNaN(rawDesktopWidth) && rawDesktopWidth > 0) {
            desktopWidth = rawDesktopWidth;
          } else if (typeof rawDesktopWidth === 'string') {
            // Only accept if the string is purely numeric (no injection attempts)
            const parsed = parseFloat(rawDesktopWidth);
            const trimmed = String(rawDesktopWidth).trim();
            if (!isNaN(parsed) && String(parsed) === trimmed) {
              desktopWidth = parsed;
            }
          }

          if (typeof rawMobileWidth === 'number' && !isNaN(rawMobileWidth) && rawMobileWidth > 0) {
            mobileWidth = rawMobileWidth;
          } else if (typeof rawMobileWidth === 'string') {
            // Only accept if the string is purely numeric (no injection attempts)
            const parsed = parseFloat(rawMobileWidth);
            const trimmed = String(rawMobileWidth).trim();
            if (!isNaN(parsed) && String(parsed) === trimmed) {
              mobileWidth = parsed;
            }
          }

          return {
            desktopWidth: Math.min(Math.max(desktopWidth, 1), 100), // Clamp between 1-100
            mobileWidth: Math.min(Math.max(mobileWidth, 1), 100)
          };
        }) || [];

        // For desktop: if all columns are 100%, use single column grid (1fr) to stack them
        const allDesktopColumnsAre100 = gridTemplateColumns.every(c => c.desktopWidth === 100);
        const desktopTemplate = allDesktopColumnsAre100
          ? '1fr' // Single column - items stack vertically
          : gridTemplateColumns.map(c => `${c.desktopWidth / 100}fr`).join(' ');

        // For mobile: if all columns are 100%, use single column grid (1fr) to stack them
        const allMobileColumnsAre100 = gridTemplateColumns.every(c => c.mobileWidth === 100);
        const mobileTemplate = allMobileColumnsAre100
          ? '1fr' // Single column - items stack vertically
          : gridTemplateColumns.map(c => `${c.mobileWidth / 100}fr`).join(' ');

        const inner = (
          <div
            className={`grid ${gapClass} ${alignmentClass}`}
            data-section-grid={section.id}
            style={{
              gridTemplateColumns: mobileTemplate,
            }}
          >
            <style dangerouslySetInnerHTML={{
              __html: `
                @media (min-width: 768px) {
                  [data-section-grid="${section.id}"] {
                    grid-template-columns: ${desktopTemplate} !important;
                  }
                }
              `
            }} />
            {section.columns?.map((col) => (
              <RenderColumn key={col.id} column={col} widgets={widgets} />
            ))}
          </div>
        );

        return (
          <section
            key={section.id}
            className={`${visibilityClasses} ${paddingClasses} ${marginClasses}`.trim()}
            style={{
              backgroundColor: bg,
              ...legacyPaddingStyle,
            }}
          >
            {s.fullWidth ? (
              inner
            ) : (
              <ContainerProvider>
                <Container>{inner}</Container>
              </ContainerProvider>
            )}
          </section>
        );
      })}
    </div>
  );
}
