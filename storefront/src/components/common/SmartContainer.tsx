"use client";

import { PropsWithChildren } from "react";
import { useContainerContext } from "@/components/builder/ContainerContext";

export function SmartContainer({ children }: PropsWithChildren) {
  const { isInContainer } = useContainerContext();

  // Si déjà dans un container, ne pas ajouter de wrapper
  if (isInContainer) {
    return <>{children}</>;
  }

  // Sinon, appliquer le container
  return (
    <div className="theme-container mx-auto w-full max-w-6xl px-4 md:px-6 lg:px-8">
      {children}
    </div>
  );
}
