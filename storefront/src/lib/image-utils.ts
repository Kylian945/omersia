import { ListingProduct } from "@/components/product/ListingProducts";

// Type générique pour les images (compatible avec différents types d'images)
type ImageLike = {
  url?: string | null;
  path?: string | null;
  is_main?: boolean;
  position?: number | null;
};

// Type pour un produit avec images
type ProductWithImages = {
  main_image_url?: string | null;
  images?: ImageLike[] | null;
};

// Use image proxy in Docker development (set via NEXT_PUBLIC_USE_IMAGE_PROXY)
// This must be a NEXT_PUBLIC_ var so it's consistent between server and client
const USE_IMAGE_PROXY = process.env.NEXT_PUBLIC_USE_IMAGE_PROXY === "true";

// Backend URL for non-Docker environments
const BACKEND_URL =
  process.env.NEXT_PUBLIC_BACKEND_URL ||
  process.env.BACKEND_URL ||
  "http://localhost:8000";

/**
 * Extracts the path from a backend image URL
 */
function extractImagePath(url: string): string | null {
  if (!url) return null;

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

  if (url.startsWith("/")) {
    return url;
  }

  return null;
}

/**
 * Normalizes an image URL for Next.js Image optimization
 * In Docker, uses the image proxy to handle internal network routing
 */
export function normalizeBackendUrl(url: string): string {
  if (!url) return url;

  // Already a proxy URL - don't transform again
  if (url.startsWith("/api/image-proxy")) {
    return url;
  }

  if (USE_IMAGE_PROXY) {
    const path = extractImagePath(url);
    // Only transform storage paths, not API paths
    if (path && path.startsWith("/storage")) {
      return `/api/image-proxy?path=${encodeURIComponent(path)}`;
    }
  }

  // For non-Docker environments, normalize to localhost:8000
  return url
    .replace(/^http:\/\/backend:8001/, "http://localhost:8000")
    .replace(/^http:\/\/backend:8000/, "http://localhost:8000")
    .replace(/^http:\/\/backend\//, "http://localhost:8000/")
    .replace(/^http:\/\/nginx(?::\d+)?\//, "http://localhost:8000/")
    .replace(/^http:\/\/127\.0\.0\.1:8000/, "http://localhost:8000")
    .replace(/^http:\/\/127\.0\.0\.1\//, "http://localhost:8000/");
}

/**
 * Construit l'URL complète d'une image produit
 * Compatible avec ProductImage et autres types d'images similaires
 */
export function buildImageUrl(img?: ImageLike | null): string | null {
  if (!img) return null;

  // Si l'image a déjà une URL complète, la normaliser
  if (img.url) {
    return normalizeBackendUrl(img.url);
  }

  // Si le path est déjà une URL complète (http/https), le normaliser
  if (img.path && (img.path.startsWith("http://") || img.path.startsWith("https://"))) {
    return normalizeBackendUrl(img.path);
  }

  // Sinon, construire l'URL à partir du path
  if (img.path) {
    const cleanPath = img.path.replace(/^\/+/, "");
    const storagePath = `/storage/${cleanPath}`;

    if (USE_IMAGE_PROXY) {
      return `/api/image-proxy?path=${encodeURIComponent(storagePath)}`;
    }

    const base = BACKEND_URL.replace(/\/+$/, "");
    return `${base}${storagePath}`;
  }

  return null;
}

/**
 * Récupère l'image principale d'un produit
 * Ordre de priorité : image marquée comme principale > première image par position
 */
export function getMainImage(product: ListingProduct): string | null {
  if (!product.images || !product.images.length) return null;

  // Chercher une image explicitement marquée comme principale
  const explicit = product.images.find((img) => img.is_main);
  if (explicit) return buildImageUrl(explicit);

  // Sinon, prendre la première image selon la position
  const sorted = [...product.images].sort(
    (a, b) => (a.position || 0) - (b.position || 0)
  );
  return buildImageUrl(sorted[0]) || null;
}

/**
 * Récupère toutes les images d'un produit avec leurs URLs construites
 */
export function getProductImages(product: ListingProduct): string[] {
  if (!product.images || !product.images.length) return [];

  return product.images
    .sort((a, b) => (a.position || 0) - (b.position || 0))
    .map((img) => buildImageUrl(img))
    .filter((url): url is string => url !== null);
}

/**
 * Récupère l'image principale d'un produit (version générique)
 * Compatible avec ProductVariantContext et autres
 * Ordre de priorité : main_image_url > image marquée comme principale > première image par position
 */
export function getMainImageUrl(product: ProductWithImages): string | null {
  // Si le produit a déjà une main_image_url, l'utiliser
  if (product.main_image_url) {
    return normalizeBackendUrl(product.main_image_url);
  }

  const imgs = product.images || [];
  if (!imgs.length) return null;

  // Chercher une image explicitement marquée comme principale
  const explicit = imgs.find((i) => i?.is_main);
  if (explicit) return buildImageUrl(explicit);

  // Sinon, prendre la première image selon la position
  const sorted = [...imgs].sort(
    (a, b) => (a?.position || 0) - (b?.position || 0)
  );
  return buildImageUrl(sorted[0]);
}
