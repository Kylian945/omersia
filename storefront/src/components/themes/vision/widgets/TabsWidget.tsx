'use client';

import { useState } from 'react';
import { sanitizeHTML } from '@/lib/html-sanitizer';
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

interface TabItem {
  title: string;
  content: string;
}

interface TabsWidgetProps {
  props: {
    items?: TabItem[];
    padding?: Record<string, unknown>;
    margin?: Record<string, unknown>;
  };
}

export function TabsWidget({ props }: TabsWidgetProps) {
  const items: TabItem[] = props?.items || [];
  const [activeTab, setActiveTab] = useState(0);

  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(props?.padding);
  const marginConfig = validateSpacingConfig(props?.margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  if (!items.length) return null;

  return (
    <div className={`w-full ${paddingClasses} ${marginClasses}`.trim()}>
      {/* Tabs navigation */}
      <div className="flex border-b overflow-x-auto"
        style={{ borderColor: "var(--theme-border-default, #e5e7eb)" }}
      >
        {items.map((item, i) => (
          <button
            key={i}
            onClick={() => setActiveTab(i)}
            className={`inline-flex items-center justify-center px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors border-b-2 ${
              activeTab === i
                ? ''
                : 'border-transparent text-neutral-500 hover:text-neutral-700'
            }`}
            style={
              activeTab === i
                ? {
                    borderColor: "var(--theme-primary, #000)",
                    color: "var(--theme-primary, #000)",
                  }
                : undefined
            }
          >
            {item.title}
          </button>
        ))}
      </div>

      {/* Tab content */}
      <div className="pt-4">
        {items[activeTab] && (
          <div
            className="text-sm text-neutral-600 leading-relaxed"
            dangerouslySetInnerHTML={{ __html: sanitizeHTML(items[activeTab].content) }}
          />
        )}
      </div>
    </div>
  );
}
