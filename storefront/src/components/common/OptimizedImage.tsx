"use client";

import Image, { ImageProps } from "next/image";
import { useState } from "react";
import { isNextImageCompatible, normalizeImageUrl } from "@/lib/image-loader";

type OptimizedImageProps = Omit<ImageProps, "onError"> & {
  fallback?: React.ReactNode;
};

/**
 * Wrapper around Next.js Image that:
 * - Normalizes backend image URLs to match remotePatterns
 * - Handles loading errors gracefully
 * - Falls back to unoptimized if optimization fails
 * - Shows a placeholder on final error
 */
export function OptimizedImage({
  src,
  alt,
  fallback,
  ...props
}: OptimizedImageProps) {
  const [hasError, setHasError] = useState(false);
  const [useUnoptimized, setUseUnoptimized] = useState(false);

  const defaultFallback = (
    <div className="flex items-center justify-center bg-neutral-100 text-neutral-400 text-xs h-full w-full">
      Image non disponible
    </div>
  );

  if (hasError) {
    return fallback ? <>{fallback}</> : defaultFallback;
  }

  // Normalize the src URL to match Next.js remotePatterns (localhost:8000)
  const normalizedSrc = typeof src === "string" ? normalizeImageUrl(src) : src;

  if (typeof normalizedSrc === "string" && !isNextImageCompatible(normalizedSrc)) {
    return fallback ? <>{fallback}</> : defaultFallback;
  }

  return (
    <Image
      src={normalizedSrc}
      alt={alt}
      unoptimized={useUnoptimized}
      onError={() => {
        if (!useUnoptimized) {
          // First try with unoptimized
          setUseUnoptimized(true);
        } else {
          // If still fails, show fallback
          setHasError(true);
        }
      }}
      {...props}
    />
  );
}
