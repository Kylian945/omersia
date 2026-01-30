import { sanitizeHTML } from '@/lib/html-sanitizer';
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

interface TextWidgetProps {
  props: {
    html?: string;
    padding?: Record<string, unknown>;
    margin?: Record<string, unknown>;
  };
}

export function TextWidget({ props }: TextWidgetProps) {
  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(props?.padding);
  const marginConfig = validateSpacingConfig(props?.margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return (
    <div
      className={`prose prose-sm [&_.ql-align-center]:text-center [&_.ql-align-right]:text-right [&_.ql-align-justify]:text-justify ${paddingClasses} ${marginClasses}`.trim()}
      style={{
        color: 'var(--theme-body-color, #374151)',
        fontFamily: 'var(--theme-body-font, Inter)',
      }}
      dangerouslySetInnerHTML={{ __html: sanitizeHTML(props?.html) }}
    />
  );
}
