import React from 'react';
import { PageBuilderWithTheme } from '../builder/PageBuilderWithTheme';
import { EcommercePage } from '@/lib/api-ecommerce-pages';
import { Container } from '../common/Container';
import Link from 'next/link';
import { ProductGallery } from './ProductGallery';
import { VariantSelector } from './VariantSelector';
import { SimpleAddToCart } from './SimpleAddToCart';
import { StickyAddToCartBar } from './StickyAddToCartBar';
import { ThemedProductCard } from './ThemedProductCard';
import { buildImageUrl } from '@/lib/image-utils';
import { ModuleHooks } from '@/components/modules/ModuleHooks';
import { sanitizeHTML } from '@/lib/html-sanitizer';
import type {
  ProductImage,
  ProductTranslation,
  ProductWithVariants
} from '@/lib/types/product-types';
import type { ListingProduct } from './ListingProducts';

interface ProductPageRendererProps {
  pageData: EcommercePage;
  product: ProductWithVariants;
  relatedProducts?: ListingProduct[];
}

type NormalizedImage = {
  id: number;
  url: string;
  is_main?: boolean;
  position?: number | null;
};

function normalizeImages(apiImages: ProductImage[] | undefined | null): NormalizedImage[] {
  if (!apiImages) return [];
  return apiImages.flatMap((img) => {
    const url = buildImageUrl(img);
    if (!url) return [];
    return {
      id: img.id,
      url,
      is_main: img.is_main,
      position: img.position ?? 0,
    };
  });
}

export function ProductPageRenderer({
  pageData,
  product,
  relatedProducts = []
}: ProductPageRendererProps) {
  const { content } = pageData;

  const t = product.translations?.[0] as ProductTranslation | undefined;
  const categories = product.categories || [];

  // Normalize images
  const images = normalizeImages(product.images);
  let mainImage: NormalizedImage | null = null;

  if (images.length > 0) {
    const explicitMain = images.find((img) => img.is_main);
    if (explicitMain) {
      mainImage = explicitMain;
    } else {
      const sorted = [...images].sort(
        (a, b) => (a.position || 0) - (b.position || 0)
      );
      mainImage = sorted[0] || null;
    }
  } else if (product.main_image_url && typeof product.main_image_url === 'string') {
    mainImage = {
      id: 0,
      url: product.main_image_url,
    };
  }

  // Variants logic
  const hasVariants =
    !!product.has_variants &&
    Array.isArray(product.variants) &&
    product.variants.length > 0;

  let displayPrice: string | null = null;
  let displayCompareAt: string | null = null;
  let displayOnSale = false;
  let inStock = false;

  if (hasVariants) {
    const activeVariants = (product.variants || []).filter(
      (v) => v && (v.is_active === undefined || v.is_active)
    );

    const prices = activeVariants
      .map((v) => (typeof v.price === "number" ? v.price : null))
      .filter((p): p is number => !!p && p > 0);

    if (prices.length) {
      const minPrice = Math.min(...prices);
      displayPrice = `À partir de ${minPrice.toFixed(2)} €`;
    } else {
      displayPrice = "Prix non défini";
    }

    const comparePrices = activeVariants
      .map((v) =>
        typeof v.compare_at_price === "number" ? v.compare_at_price : null
      )
      .filter((p): p is number => !!p && p > 0);

    if (prices.length && comparePrices.length) {
      const minPrice = Math.min(...prices);
      const minCompare = Math.min(...comparePrices);
      if (minCompare > minPrice) {
        displayCompareAt = minCompare.toFixed(2) + " €";
        displayOnSale = true;
      }
    }

    inStock = activeVariants.some(
      (v) =>
        typeof v.stock_qty === "number" ? v.stock_qty > 0 : false
    );
  } else {
    const price =
      typeof product.price === "number" ? product.price : 0;
    const compareAt =
      typeof product.compare_at_price === "number"
        ? product.compare_at_price
        : null;

    displayPrice = price > 0 ? `${price.toFixed(2)} €` : "Prix non défini";
    if (compareAt !== null && compareAt > price && price > 0) {
      displayCompareAt = `${compareAt.toFixed(2)} €`;
      displayOnSale = true;
    }

    inStock =
      typeof product.stock_qty === "number"
        ? product.stock_qty > 0
        : true;
  }

  const withRelated = relatedProducts.length > 0;

  // Rendu avec contenu natif (beforeNative + product details + afterNative)
  const { beforeNative, afterNative } = content;

  return (
    <div className="product-page-with-builder">
      {/* CONTENU NATIF: Détails du produit (non-éditable) */}
      <div className="native-content-container py-10">
        <Container>
          {/* Fil d'Ariane */}
          <div className="text-xs text-neutral-500 mb-2">
            <Link href="/" className="hover:text-black transition">
              Accueil
            </Link>
            <span className="mx-1">/</span>
            <Link href="/products" className="hover:text-black transition">
              Produits
            </Link>
            <span className="mx-1">/</span>
            <span className="text-neutral-700">
              {t?.name || "Produit"}
            </span>
          </div>

          {/* Header produit */}
          <div className="flex flex-col gap-1">
            <h1 className="text-2xl md:text-3xl font-semibold tracking-tight">
              {t?.name}
            </h1>
            {!hasVariants && product.sku && (
              <p className="text-xs text-neutral-400">
                SKU : {product.sku}
              </p>
            )}
          </div>
        </Container>

        {/* Sections AVANT le contenu natif */}
        {beforeNative?.sections && beforeNative.sections.length > 0 && (
          <div className="before-native-content">
            <PageBuilderWithTheme layout={beforeNative} />
          </div>
        )}

        <Container>
          {/* Layout principal */}
          <div className="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">
            <div className="lg:sticky lg:top-24 self-start">
              <ProductGallery
                images={images}
                mainImage={mainImage}
                alt={t?.name || "Image produit"}
              />
            </div>

            <section className="w-full">
              <div className="rounded-2xl bg-white border border-black/5 shadow-sm p-4 space-y-4">
                <div className="flex items-center justify-between gap-3">
                  <div className="flex items-baseline gap-2">
                    <div className="text-xl font-semibold tracking-tight text-neutral-900">
                      {displayPrice}
                    </div>

                    {displayCompareAt && !hasVariants && (
                      <div className="text-xs text-neutral-400 line-through">
                        {displayCompareAt}
                      </div>
                    )}
                  </div>

                  <div className="flex items-center gap-3">
                    {displayOnSale && (
                      <span className="ml-1 inline-flex items-center rounded-full bg-black px-2 py-1 text-xs font-medium text-white">
                        Promo
                      </span>
                    )}

                    <div
                      className={`text-xs px-2 py-1 rounded-full border ${inStock
                        ? hasVariants ? "bg-indigo-50 text-indigo-700 border-indigo-100 "
                          : "bg-emerald-50 text-emerald-700 border-emerald-100 " : 'bg-red-50 text-red-600 border-red-100'
                        }`}
                    >
                      {inStock ? (

                        <div className="flex items-center gap-2">
                          {!hasVariants && (<div className="h-2 w-2 bg-emerald-500 rounded-full" />)}
                          <span>{hasVariants ? "Plusieurs modèles" : "En stock"}</span>
                        </div>
                      ) : (
                        <div className="flex items-center gap-2">
                          <div className="h-2 w-2 bg-rose-500 rounded-full" />
                          <span>Actuellement indisponible</span>
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                {categories.length > 0 && (
                  <div className="flex flex-wrap gap-1">
                    {categories.map((cat) =>
                      cat.translations?.[0]?.name !== "Accueil" ? (
                        <span
                          key={cat.id}
                          className="px-2 py-0.5 rounded-full bg-neutral-100 text-xs text-neutral-700"
                        >
                          {cat.translations?.[0]?.name || "Catégorie"}
                        </span>
                      ) : null
                    )}
                  </div>
                )}

                {t?.short_description && (
                  <p className="text-xs text-neutral-600">
                    {t.short_description}
                  </p>
                )}

                {/* Hook: product.detail.badges - Permet d'ajouter des badges personnalisés au produit */}
                <ModuleHooks
                  hookName="product.detail.badges"
                  context={{
                    productId: product.id,
                    price: typeof product.price === "number" ? product.price : 0,
                    inStock,
                    displayOnSale,
                  }}
                />

                {/* CTA (toujours décoratif ici) */}
                <div id="product-cta" className="space-y-2">
                  {hasVariants ? (
                    <VariantSelector />
                  ) : (
                    <SimpleAddToCart
                      productId={product.id}
                      name={t?.name || "Produit"}
                      price={typeof product.price === "number" ? product.price : 0}
                      oldPrice={
                        typeof product.compare_at_price === "number"
                          ? product.compare_at_price
                          : undefined
                      }
                      imageUrl={mainImage?.url}
                      disabled={!inStock}
                    />
                  )}
                </div>

                {/* Hook: product.detail.actions - Permet d'ajouter des actions personnalisées au produit */}
                <ModuleHooks
                  hookName="product.detail.actions"
                  context={{
                    productId: product.id,
                    inStock,
                  }}
                />

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2">
                  <div className="rounded-xl border border-neutral-100 px-2 py-2 text-xxxs text-neutral-600">
                    <div className="font-medium text-neutral-900 text-xs">
                      Paiement sécurisé
                    </div>
                    CB, Visa, Mastercard…
                  </div>
                  <div className="rounded-xl border border-neutral-100 px-2 py-2 text-xxxs text-neutral-600">
                    <div className="font-medium text-neutral-900 text-xs">
                      Livraison rapide
                    </div>
                    Suivi & numéro de tracking.
                  </div>
                  <div className="rounded-xl border border-neutral-100 px-2 py-2 text-xxxs text-neutral-600">
                    <div className="font-medium text-neutral-900 text-xs">
                      Support réactif
                    </div>
                    Une équipe pour vous aider.
                  </div>
                </div>
              </div>

              {t?.description && (
                <div className="mt-4 rounded-2xl bg-white border border-black/5 shadow-sm p-4">
                  <h2 className="text-sm font-semibold text-neutral-900 mb-1.5">
                    Description détaillée
                  </h2>
                  <div className="prose prose-sm max-w-none text-xs text-neutral-700">
                    <div
                      dangerouslySetInnerHTML={{
                        __html: sanitizeHTML(t.description),
                      }}
                    />
                  </div>

                  {/* Hook: product.detail.after-description - Permet d'ajouter du contenu après la description */}
                  <ModuleHooks
                    hookName="product.detail.after-description"
                    context={{
                      productId: product.id,
                    }}
                  />
                </div>
              )}
            </section>
          </div>
        </Container>

        {/* Hook: product.detail.upsell - Permet d'ajouter des recommandations de produits */}
        <ModuleHooks
          hookName="product.detail.upsell"
          context={{
            productId: product.id,
            categoryId: categories.length > 0 ? categories[0].id : undefined,
          }}
        />

        {/* Produits associés */}
        {withRelated && (
          <section className="mt-10">
            <Container>
              <div className="flex items-center justify-between mb-3">
                <div>
                  <h2 className="text-md font-semibold text-neutral-900">
                    Produits associés
                  </h2>
                  <p className="text-sm text-neutral-500">
                    Découvrez d&apos;autres articles qui complètent ce produit.
                  </p>
                </div>
              </div>

              <div className="flex gap-3 overflow-x-auto pb-2 -mx-1 px-1 md:grid md:grid-cols-4 md:gap-4 md:overflow-visible">
                {relatedProducts.map((rp) => {
                  return (
                    <ThemedProductCard key={rp.id} product={rp} />
                  );
                })}
              </div>
            </Container>
          </section>
        )}

        <StickyAddToCartBar
          product={product}
          inStock={inStock}
        />
      </div>

      {/* Sections APRÈS le contenu natif */}
      {afterNative?.sections && afterNative.sections.length > 0 && (
        <div className="after-native-content">
          <PageBuilderWithTheme layout={afterNative} />
        </div>
      )}
    </div>
  );
}
