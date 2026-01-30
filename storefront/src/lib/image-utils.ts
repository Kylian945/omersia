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

const BACKEND_URL =
  process.env.NEXT_PUBLIC_BACKEND_URL ||
  process.env.BACKEND_URL ||
  "";

/**
 * Construit l'URL complète d'une image produit
 * Compatible avec ProductImage et autres types d'images similaires
 */
export function buildImageUrl(img?: ImageLike | null): string | null {
  if (!img) return null;

  // Si l'image a déjà une URL complète, l'utiliser
  if (img.url) return img.url;

  // Si le path est déjà une URL complète (http/https), le retourner directement
  if (img.path && (img.path.startsWith('http://') || img.path.startsWith('https://'))) {
    return img.path;
  }

  // Sinon, construire l'URL à partir du path
  if (img.path && BACKEND_URL) {
    const base = BACKEND_URL.replace(/\/+$/, "");
    const path = img.path.replace(/^\/+/, "");
    return `${base}/storage/${path}`;
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
  if (product.main_image_url) return product.main_image_url;

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
