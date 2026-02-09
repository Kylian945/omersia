"use client";

import { createContext, useContext, PropsWithChildren } from "react";

interface ContainerContextValue {
  isInContainer: boolean;
}

const ContainerContext = createContext<ContainerContextValue>({
  isInContainer: false,
});

export function useContainerContext() {
  return useContext(ContainerContext);
}

export function ContainerProvider({ children }: PropsWithChildren) {
  return (
    <ContainerContext.Provider value={{ isInContainer: true }}>
      {children}
    </ContainerContext.Provider>
  );
}
