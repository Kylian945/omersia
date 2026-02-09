import { Button } from "@/components/common/Button";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

interface ButtonWidgetProps {
  props: {
    label?: string;
    url?: string;
    padding?: Record<string, unknown>;
    margin?: Record<string, unknown>;
  };
}

export function ButtonWidget({ props }: ButtonWidgetProps) {
  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(props?.padding);
  const marginConfig = validateSpacingConfig(props?.margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return (
    <div className={`${paddingClasses} ${marginClasses}`.trim()}>
      <Button
        href={props?.url || "#"}
        variant="primary"
        size="md"
      >
        {props?.label || "En savoir plus"}
      </Button>
    </div>
  );
}
