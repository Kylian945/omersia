/**
 * Custom image loader for Next.js
 * Handles images from the Laravel backend with proper optimization
 *
 * Uses a proxy API route to handle Docker network routing, ensuring
 * consistent URLs between server and client (no hydration mismatch).
 */

type ImageLoaderProps = {
  src: string;
  width: number;
  quality?: number;
};

// Use image proxy in Docker development (set via NEXT_PUBLIC_USE_IMAGE_PROXY)
// This must be a NEXT_PUBLIC_ var so it's consistent between server and client
const USE_IMAGE_PROXY = process.env.NEXT_PUBLIC_USE_IMAGE_PROXY === "true";

/**
 * Extracts the path from a backend image URL
 * Returns the path portion (e.g., /storage/products/1/image.png)
 */
function extractImagePath(url: string): string | null {
  if (!url) return null;

  // Handle full URLs - extract path after the host
  const patterns = [
    /^https?:\/\/localhost(?::\d+)?(.+)$/,
    /^https?:\/\/127\.0\.0\.1(?::\d+)?(.+)$/,
    /^https?:\/\/backend(?::\d+)?(.+)$/,
    /^https?:\/\/nginx(?::\d+)?(.+)$/,
    /^https?:\/\/host\.docker\.internal(?::\d+)?(.+)$/,
  ];

  for (const pattern of patterns) {
    const match = url.match(pattern);
    if (match) {
      return match[1];
    }
  }

  // Already a relative path
  if (url.startsWith("/")) {
    return url;
  }

  return null;
}

/**
 * Normalizes an image URL for Next.js Image optimization
 *
 * In Docker development, converts backend URLs to use the image proxy:
 * - Input: http://localhost:8000/storage/products/1/image.png
 * - Output: /api/image-proxy?path=/storage/products/1/image.png
 *
 * The proxy route handles fetching from the internal Docker network.
 */
export function normalizeImageUrl(src: string): string {
  if (!src) return "";

  // Already a proxy URL - don't transform again
  if (src.startsWith("/api/image-proxy")) {
    return src;
  }

  // If proxy is enabled and this is a backend image, use the proxy
  if (USE_IMAGE_PROXY) {
    const path = extractImagePath(src);
    // Only transform storage paths, not API paths
    if (path && path.startsWith("/storage")) {
      return `/api/image-proxy?path=${encodeURIComponent(path)}`;
    }
  }

  // For non-Docker environments or non-backend images, return as-is
  // or normalize to localhost:8000
  if (src.startsWith("http://") || src.startsWith("https://")) {
    // Normalize various backend hostnames to localhost:8000
    return src
      .replace(/^http:\/\/backend:8001/, "http://localhost:8000")
      .replace(/^http:\/\/backend:8000/, "http://localhost:8000")
      .replace(/^http:\/\/backend\//, "http://localhost:8000/")
      .replace(/^http:\/\/nginx(?::\d+)?\//, "http://localhost:8000/")
      .replace(/^http:\/\/127\.0\.0\.1:8000/, "http://localhost:8000")
      .replace(/^http:\/\/127\.0\.0\.1\//, "http://localhost:8000/");
  }

  // Relative path - prepend backend URL
  const backendUrl = process.env.NEXT_PUBLIC_BACKEND_URL || "http://localhost:8000";
  const cleanSrc = src.startsWith("/") ? src : `/${src}`;

  if (USE_IMAGE_PROXY) {
    return `/api/image-proxy?path=${encodeURIComponent(cleanSrc)}`;
  }

  return `${backendUrl}${cleanSrc}`;
}

/**
 * Custom loader for Next.js Image component
 * Returns the normalized URL for the image
 */
export function backendImageLoader({ src }: ImageLoaderProps): string {
  return normalizeImageUrl(src);
}

/**
 * Check if a URL is from the backend
 */
export function isBackendImage(src: string): boolean {
  if (!src) return false;

  return (
    src.includes("localhost:8000") ||
    src.includes("127.0.0.1:8000") ||
    src.includes("/storage/") ||
    src.includes("/api/image-proxy")
  );
}
