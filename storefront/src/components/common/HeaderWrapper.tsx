"use client";

import { useMemo } from "react";
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
  let classes = "z-40";

  // Position (sticky ou non)
  if (isSticky) {
    classes += " sticky top-0 backdrop-blur";
  } else {
    classes += " relative";
  }

  // Style visuel selon header_style
  switch (headerStyle) {
    case "minimal":
      // Minimal: transparent, sans bordure
      classes += isSticky ? " bg-white/40" : " bg-transparent";
      break;
    case "classic":
      // Classic: fond blanc, sans bordure
      classes += isSticky ? " bg-white/80" : " bg-white";
      break;
    case "bordered":
      // Bordered: fond blanc + bordure inférieure
      classes += isSticky
        ? " bg-white/80 border-b border-black/5"
        : " bg-white border-b border-black/5";
      break;
    default:
      classes += " bg-white";
  }

  return (
    <>
      {/* Hook: header.top - Permet d'ajouter une bannière d'informations en haut du site */}
      <ModuleHooks
        hookName="header.top"
        context={{}}
      />
      <header className={classes}>{children}</header>
    </>
  );
}
