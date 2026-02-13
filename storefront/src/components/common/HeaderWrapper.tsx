"use client";

import { CSSProperties, useMemo } from "react";
import { ModuleHooks } from "@/components/modules/ModuleHooks";
import { useHydrated } from "@/hooks/useHydrated";

type HeaderWrapperProps = {
  children: React.ReactNode;
};

export function HeaderWrapper({ children }: HeaderWrapperProps) {
  const isHydrated = useHydrated();

  const headerStyle = useMemo(() => {
    if (!isHydrated) return "classic";

    const style = getComputedStyle(document.documentElement)
      .getPropertyValue("--theme-header-style")
      .trim();

    return style || "classic";
  }, [isHydrated]);

  const isSticky = useMemo(() => {
    if (!isHydrated) return true;

    const sticky = getComputedStyle(document.documentElement)
      .getPropertyValue("--theme-header-sticky")
      .trim();

    return sticky ? sticky === "yes" : true;
  }, [isHydrated]);

  // Base classes
  let classes = "z-40 text-[var(--theme-body-color,#374151)]";

  // Position (sticky ou non)
  if (isSticky) {
    classes += " sticky top-0 backdrop-blur";
  } else {
    classes += " relative";
  }

  // Style visuel selon header_style
  const headerBackground = "var(--theme-header-bg, var(--theme-card-bg, #ffffff))";
  let headerInlineStyle: CSSProperties | undefined;

  switch (headerStyle) {
    case "minimal":
      // Minimal: transparent, sans bordure
      headerInlineStyle = isSticky ? { backgroundColor: headerBackground } : undefined;
      break;
    case "classic":
      // Classic: fond blanc, sans bordure
      headerInlineStyle = { backgroundColor: headerBackground };
      break;
    case "bordered":
      // Bordered: fond blanc + bordure inférieure
      classes += isSticky
        ? " border-b border-[var(--theme-border-default,#e5e7eb)]"
        : " border-b border-[var(--theme-border-default,#e5e7eb)]";
      headerInlineStyle = { backgroundColor: headerBackground };
      break;
    default:
      headerInlineStyle = { backgroundColor: headerBackground };
  }

  return (
    <>
      {/* Hook: header.top - Permet d'ajouter une bannière d'informations en haut du site */}
      <ModuleHooks
        hookName="header.top"
        context={{}}
      />
      <header className={classes} style={headerInlineStyle}>
        {children}
      </header>
    </>
  );
}
