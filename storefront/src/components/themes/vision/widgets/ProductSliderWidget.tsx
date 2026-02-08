import { getProducts } from "@/lib/api-products";
import { ProductSlider } from "@/components/product/ProductSlider";
import { ListingProduct } from "@/components/product/ListingProducts";
import { SmartContainer } from "@/components/common/SmartContainer";
import { ProductGrid } from "@/components/product/ProductGrid";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

type ProductSliderWidgetProps = {
  mode?: "category" | "custom";
  categorySlug?: string;
  productIds?: number[];
  // Display configuration
  displayMode?: "slider" | "grid";
  slidesPerView?: { desktop: number; mobile: number };
  slidesToScroll?: { desktop: number; mobile: number };
  columns?: { desktop: number; mobile: number };
  showArrows?: boolean;
  showDots?: boolean;
  autoplay?: boolean;
  gap?: number;
  padding?: Record<string, unknown>;
  margin?: Record<string, unknown>;
};

export async function ProductSliderWidget({
  mode = "category",
  categorySlug,
  productIds,
  displayMode = "slider",
  slidesPerView = { desktop: 4, mobile: 2 },
  slidesToScroll = { desktop: 1, mobile: 1 },
  columns = { desktop: 4, mobile: 2 },
  showArrows = true,
  showDots = true,
  autoplay = false,
  gap = 16,
  padding,
  margin,
}: ProductSliderWidgetProps) {
  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  let products: ListingProduct[] = [];

  // Récupérer les produits selon le mode
  if (mode === "custom" && productIds && productIds.length > 0) {
    // Mode custom: récupérer tous les produits et filtrer par IDs
    const response = await getProducts({ locale: "fr", limit: 100 });
    if (response?.data && Array.isArray(response.data)) {
      products = response.data.filter((product: ListingProduct) =>
        productIds.includes(product.id)
      );
    }
  } else if (mode === "category" && categorySlug) {
    // Mode catégorie: filtrer côté API par catégorie
    const response = await getProducts({ locale: "fr", category: categorySlug, limit: 50 });
    if (response?.data && Array.isArray(response.data)) {
      products = response.data;
    }
  } else {
    // Mode par défaut: afficher tous les produits
    const response = await getProducts({ locale: "fr", limit: 50 });
    if (response?.data && Array.isArray(response.data)) {
      products = response.data;
    }
  }

  if (products.length === 0) {
    return null;
  }

  return (
    <section className={`${paddingClasses} ${marginClasses}`.trim()}>
      <SmartContainer>

        {products.length === 0 ? (
          <p
            className="text-xs px-6 py-2 border border-dashed"
            style={{
              color: 'var(--theme-muted-color, #6b7280)',
              borderColor: 'var(--theme-border-default, #e5e7eb)',
              borderRadius: 'var(--theme-border-radius, 12px)',
            }}
          >
            Aucun produit existant (Ajoutez des éléments dans l&apos;administration)
          </p>
        ) :
          displayMode === "slider" ? (
            <ProductSlider
              products={products}
              hrefBase="/products"
              slidesPerView={slidesPerView}
              slidesToScroll={slidesToScroll}
              showArrows={showArrows}
              showDots={showDots}
              autoplay={autoplay}
              gap={gap}
            />
          ) : (
            <ProductGrid
              products={products}
              hrefBase="/products"
              columns={columns}
              gap={gap}
            />
          )
        }
      </SmartContainer>
    </section>
  );
}
