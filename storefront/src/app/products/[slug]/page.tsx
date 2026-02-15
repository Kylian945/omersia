import { Container } from "@/components/common/Container";
import { Footer } from "@/components/common/Footer";
import { Header } from "@/components/common/Header";
import { getProductBySlug } from "@/lib/api-products";
import { getEcommercePageByType } from "@/lib/api-ecommerce-pages";
import { Metadata } from "next";
import { ProductGallery } from "@/components/product/ProductGallery";
import { ProductPageRenderer } from "@/components/product/ProductPageRenderer";
import { StickyAddToCartBar } from "@/components/product/StickyAddToCartBar";
import Link from "next/link";
import { VariantSelector } from "@/components/product/VariantSelector";
import { SimpleAddToCart } from "@/components/product/SimpleAddToCart";
import { ProductVariantProvider } from "@/components/product/ProductVariantContext";
import { ThemedProductCard } from "@/components/product/ThemedProductCard";
import { buildImageUrl } from "@/lib/image-utils";
import { sanitizeHTML, sanitizePlainText } from "@/lib/html-sanitizer";

// Force dynamic rendering - no cache for Page Builder content
export const dynamic = 'force-dynamic';

type Props = {
  params: Promise<{ slug: string }>;
};

type ApiImage = {
  id: number;
  url?: string | null;
  path?: string | null;
  is_main?: boolean;
  position?: number | null;
};

type NormalizedImage = {
  id: number;
  url: string;
  is_main?: boolean;
  position?: number | null;
};

function normalizeImages(apiImages: ApiImage[] | undefined | null): NormalizedImage[] {
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

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;

  // PERF-004: Paralléliser les fetches de métadonnées
  const [pageData, product] = await Promise.all([
    getEcommercePageByType("product", slug, "fr"),
    getProductBySlug(slug, "fr"),
  ]);

  // Priorité aux métadonnées Page Builder
  if (pageData) {
    return {
      title: pageData.meta_title || pageData.title || "",
      description: pageData.meta_description || "",
    };
  }

  // Fallback to product metadata
  if (!product) return {};

  const t = product.translations?.[0];

  return {
    title: t?.meta_title || t?.name || "",
    description: sanitizePlainText(
      t?.meta_description || t?.short_description || ""
    ),
  };
}

export default async function ProductPage({ params }: Props) {
  const { slug } = await params;

  // PERF-001: Paralléliser les fetches pour réduire le TTFB (-800ms)
  const [product, pageData] = await Promise.all([
    getProductBySlug(slug, "fr"),
    getEcommercePageByType("product", slug, "fr"),
  ]);

  if (!product) {
    return (
      <>
        <Header />
        <main className="px-6 py-10 flex-1 flex items-center">
          <Container>
            <div className="max-w-md mx-auto text-center">
              <h1 className="text-2xl font-semibold">Page introuvable</h1>
              <p className="mt-2 text-sm text-neutral-600">
                Le produit que vous recherchez n&apos;existe pas ou n&apos;est plus disponible.
              </p>
              <Link
                href="/"
                className="mt-4 inline-block bg-black px-5 py-2 text-sm text-white hover:bg-neutral-900 rounded-full"
              >
                Retour à l&apos;accueil
              </Link>
            </div>
          </Container>
        </main>
        <Footer />
      </>
    );
  }

  const t = product.translations?.[0];
  const safeShortDescription = sanitizeHTML(t?.short_description);
  const safeDescription = sanitizeHTML(t?.description);

  // Fetch related products
  const rawRelated: unknown[] =
    product.related_products ||
    product.relatedProducts ||
    [];

  const relatedProducts = (rawRelated || [])
    .filter((rp): rp is { id: number; name?: string; slug?: string; base_price?: number; image_url?: string } =>
      typeof rp === 'object' && rp !== null && 'id' in rp && rp.id !== product.id
    )
    .slice(0, 12);

  // PERF-001: pageData déjà fetché en parallèle ci-dessus
  // If Page Builder content exists, use ProductPageRenderer
  if (pageData) {
    const hasVariants =
      !!product.has_variants &&
      Array.isArray(product.variants) &&
      product.variants.length > 0;

    const pageContent = (
      <main className="flex-1">
        <ProductPageRenderer
          pageData={pageData}
          product={product}
          relatedProducts={relatedProducts}
        />
      </main>
    );

    return (
      <>
        <Header />
        {hasVariants ? (
          <ProductVariantProvider product={product}>
            {pageContent}
          </ProductVariantProvider>
        ) : (
          pageContent
        )}
        <Footer />
      </>
    );
  }

  // Fallback: render default product page
  const categories = product.categories || [];

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
  } else if (product.main_image_url) {
    mainImage = {
      id: 0,
      url: product.main_image_url,
    };
  }

  // -------- Variantes --------

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

  // -------- Produits associés --------
  const withRelated = relatedProducts.length > 0;

  const pageContent = (
    <main className="flex-1 py-10">
      <Container>
        {/* Fil d’Ariane */}
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

              {safeShortDescription && (
                <div
                  className="prose prose-sm max-w-none text-xs text-neutral-600"
                  dangerouslySetInnerHTML={{
                    __html: safeShortDescription,
                  }}
                />
              )}

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

            {safeDescription && (
              <div className="mt-4 rounded-2xl bg-white border border-black/5 shadow-sm p-4">
                <h2 className="text-sm font-semibold text-neutral-900 mb-1.5">
                  Description détaillée
                </h2>
                <div className="prose prose-sm max-w-none text-xs text-neutral-700">
                  <div
                    dangerouslySetInnerHTML={{
                      __html: safeDescription,
                    }}
                  />
                </div>
              </div>
            )}
          </section>
        </div>
      </Container>

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
                  Découvrez d’autres articles qui complètent ce produit.
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
    </main>
  );

  return (
    <>
      <Header />
      {hasVariants ? (
        <ProductVariantProvider product={product}>
          {pageContent}
        </ProductVariantProvider>
      ) : (
        pageContent
      )}
      <Footer />
    </>
  );
}
