"use client";

import { useState } from "react";
import type { CSSProperties } from "react";
import { sanitizeHTML } from "@/lib/html-sanitizer";
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

const VISION_UI: {
  navClass: string;
  navStyle?: CSSProperties;
  activeClass: string;
  inactiveClass: string;
  contentClass: string;
} = {
  navClass: "flex border-b overflow-x-auto",
  navStyle: { borderColor: "var(--theme-border-default, #e5e7eb)" },
  activeClass: "border-b-2",
  inactiveClass:
    "border-b-2 border-transparent text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-body-color,#374151)]",
  contentClass: "pt-4 text-sm text-[var(--theme-body-color,#374151)] leading-relaxed",
};

export function TabsWidget({ props }: TabsWidgetProps) {
  const items: TabItem[] = props?.items || [];
  const [activeTab, setActiveTab] = useState(0);
  const ui = VISION_UI;

  const paddingConfig = validateSpacingConfig(props?.padding);
  const marginConfig = validateSpacingConfig(props?.margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  if (!items.length) return null;

  return (
    <div className={`w-full ${paddingClasses} ${marginClasses}`.trim()}>
      <div className={ui.navClass} style={ui.navStyle}>
        {items.map((item, i) => {
          const isActive = activeTab === i;
          return (
            <button
              key={i}
              onClick={() => setActiveTab(i)}
              className={`inline-flex items-center justify-center px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors ${
                isActive ? ui.activeClass : ui.inactiveClass
              }`.trim()}
              style={
                isActive
                  ? {
                      borderColor: "var(--theme-primary, #111827)",
                      color: "var(--theme-primary, #111827)",
                    }
                  : undefined
              }
            >
              {item.title}
            </button>
          );
        })}
      </div>

      <div className={ui.contentClass}>
        {items[activeTab] && (
          <div
            dangerouslySetInnerHTML={{ __html: sanitizeHTML(items[activeTab].content) }}
          />
        )}
      </div>
    </div>
  );
}
