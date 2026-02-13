import { PropsWithChildren } from "react";

export function Container({ children }: PropsWithChildren) {
  return (
    <div className="theme-container mx-auto w-full max-w-6xl px-4 md:px-6 lg:px-8">
      {children}
    </div>
  );
}
