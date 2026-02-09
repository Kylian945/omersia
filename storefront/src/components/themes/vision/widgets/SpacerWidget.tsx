import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

interface SpacerWidgetProps {
  props: {
    size?: number;
    padding?: Record<string, unknown>;
    margin?: Record<string, unknown>;
  };
}

export function SpacerWidget({ props }: SpacerWidgetProps) {
  const size = typeof props?.size === "number" ? props.size : 32;

  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(props?.padding);
  const marginConfig = validateSpacingConfig(props?.margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return <div className={`${paddingClasses} ${marginClasses}`.trim()} style={{ height: `${size}px` }} />;
}
