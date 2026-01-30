import React from "react";
import { SmartContainer } from "@/components/common/SmartContainer";
import { Column, WidgetBase } from "@/components/builder/types";
import { getGapClass, getAlignmentClass, getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateGap, validateAlignment, validateSpacingConfig } from "@/lib/css-variable-sanitizer";

interface ContainerWidgetProps {
  props: {
    background?: string;
    paddingTop?: number; // Legacy support
    paddingBottom?: number; // Legacy support
    padding?: Record<string, unknown>; // New spacing system
    margin?: Record<string, unknown>; // New spacing system
    columns?: Column[];
    gap?: 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    alignment?: 'start' | 'center' | 'end' | 'stretch' | 'baseline';
  };
  renderWidget: (widget: WidgetBase) => React.JSX.Element | null;
}

export function ContainerWidget({ props, renderWidget }: ContainerWidgetProps) {
  const bg = props?.background || "#ffffff";
  const columns: Column[] = props?.columns || [];

  // Validate and get gap/alignment classes
  const safeGap = validateGap(props?.gap);
  const safeAlignment = validateAlignment(props?.alignment);
  const gapClass = getGapClass(safeGap);
  const alignmentClass = getAlignmentClass(safeAlignment);

  // Validate and get spacing classes (with backward compatibility)
  const paddingConfig = validateSpacingConfig(props?.padding);
  const marginConfig = validateSpacingConfig(props?.margin);

  // Backward compatibility: if new padding system not used, fall back to legacy paddingTop/paddingBottom
  const hasLegacyPadding = !props?.padding && (props?.paddingTop !== undefined || props?.paddingBottom !== undefined);
  let legacyPaddingStyle: Record<string, string> = {};

  if (hasLegacyPadding) {
    const paddingTop = props.paddingTop ?? 40;
    const paddingBottom = props.paddingBottom ?? 40;
    legacyPaddingStyle = {
      paddingTop: `${paddingTop}px`,
      paddingBottom: `${paddingBottom}px`,
    };
  }

  const paddingClasses = hasLegacyPadding ? '' : getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  // Calculate grid template columns based on column widths
  // Use fractional units (fr) instead of percentages to avoid overflow with gap
  const gridTemplateColumns = columns.map(col => {
    const width = col.width || 100;
    // Convert percentages to fr units (50% = 0.5fr, 100% = 1fr)
    return `${width / 100}fr`;
  }).join(' ');

  return (
    <SmartContainer>
      <div
        className={`${paddingClasses} ${marginClasses}`.trim()}
        style={{
          backgroundColor: bg,
          borderRadius: 'var(--theme-border-radius, 12px)',
          ...legacyPaddingStyle,
        }}
      >
        <div
          className={`grid ${gapClass} ${alignmentClass}`}
          style={{
            gridTemplateColumns: gridTemplateColumns,
          }}
        >
          {columns.map((col) => {
            return (
              <div
                key={col.id}
                className="space-y-3"
                style={{
                  minWidth: 0,
                  boxSizing: 'border-box',
                }}
              >
                {col.widgets?.map((w) => (
                  <div key={w.id}>{renderWidget(w)}</div>
                ))}
              </div>
            );
          })}
        </div>
      </div>
    </SmartContainer>
  );
}
