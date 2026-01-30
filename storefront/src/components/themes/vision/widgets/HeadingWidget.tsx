import { JSX } from "react";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

interface HeadingWidgetProps {
  props: {
    text?: string;
    tag?: string;
    align?: string;
    padding?: Record<string, unknown>;
    margin?: Record<string, unknown>;
  };
}

// Default font sizes for each heading level (in px)
const DEFAULT_HEADING_SIZES: Record<string, string> = {
  h1: "48px",
  h2: "36px",
  h3: "28px",
  h4: "22px",
  h5: "18px",
  h6: "16px",
};

// Get alignment class
function getAlignClass(align?: string): string {
  switch (align) {
    case "center":
      return "text-center";
    case "right":
      return "text-right";
    case "left":
    default:
      return "text-left";
  }
}

export function HeadingWidget({ props }: HeadingWidgetProps) {
  const tag = props?.tag || "h2";
  const Tag = tag as keyof JSX.IntrinsicElements;

  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(props?.padding);
  const marginConfig = validateSpacingConfig(props?.margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);
  const alignClass = getAlignClass(props?.align);

  // Get the CSS variable for this heading level's size
  const sizeVar = `var(--theme-${tag}-size, ${DEFAULT_HEADING_SIZES[tag] || "24px"})`;

  return (
    <Tag
      className={`mb-3 font-semibold ${alignClass} ${paddingClasses} ${marginClasses}`.trim()}
      style={{
        color: 'var(--theme-heading-color, #111827)',
        fontFamily: 'var(--theme-heading-font, Inter)',
        fontWeight: 'var(--theme-heading-weight, 600)',
        fontSize: sizeVar,
      }}
    >
      {props?.text || "Titre"}
    </Tag>
  );
}
