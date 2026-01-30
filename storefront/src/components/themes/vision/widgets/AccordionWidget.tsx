import { sanitizeHTML } from '@/lib/html-sanitizer';
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

interface AccordionItem {
  title: string;
  content: string;
}

interface AccordionWidgetProps {
  props: {
    items?: AccordionItem[];
    padding?: Record<string, unknown>;
    margin?: Record<string, unknown>;
  };
}

export function AccordionWidget({ props }: AccordionWidgetProps) {
  const items: AccordionItem[] = props?.items || [];
  if (!items.length) return null;

  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(props?.padding);
  const marginConfig = validateSpacingConfig(props?.margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return (
    <div
      className={`border divide-y text-sm ${paddingClasses} ${marginClasses}`.trim()}
      style={{
        borderColor: 'var(--theme-border-default, #e5e7eb)',
        borderRadius: 'var(--theme-border-radius, 12px)',
        backgroundColor: 'var(--theme-card-bg, #ffffff)',
      }}
    >
      {items.map((item, i) => (
        <details
          key={i}
          className="group"
          open={i === 0}
        >
          <summary
            className="cursor-pointer list-none px-3 py-2 flex items-center justify-between"
            style={{ color: 'var(--theme-heading-color, #111827)' }}
          >
            <span>{item.title}</span>
            <span
              className="text-xs group-open:rotate-90 transition-transform"
              style={{ color: 'var(--theme-muted-color, #6b7280)' }}
            >
              ›
            </span>
          </summary>
          <div
            className="px-3 pb-3 text-xs leading-relaxed"
            style={{ color: 'var(--theme-body-color, #374151)' }}
            dangerouslySetInnerHTML={{ __html: sanitizeHTML(item.content) }}
          />
        </details>
      ))}
    </div>
  );
}
