import { ListingProduct } from "./ListingProducts";
import { ThemedProductCard } from "./ThemedProductCard";
import { ProductSliderShell } from "./ProductSliderShell";

type Props = {
  products: ListingProduct[];
  hrefBase?: string;
  slidesPerView?: { desktop: number; mobile: number };
  slidesToScroll?: { desktop: number; mobile: number };
  showArrows?: boolean;
  showDots?: boolean;
  autoplay?: boolean;
  gap?: number;
};

export async function ProductSlider({
  products,
  hrefBase = "/products",
  slidesPerView = { desktop: 4, mobile: 2 },
  slidesToScroll = { desktop: 1, mobile: 1 },
  showArrows = true,
  showDots = true,
  autoplay = false,
  gap = 16,
}: Props) {
  if (!products || products.length === 0) return null;

  return (
    <ProductSliderShell
      slidesPerView={slidesPerView}
      slidesToScroll={slidesToScroll}
      showArrows={showArrows}
      showDots={showDots}
      autoplay={autoplay}
    >
      {products.map((product) => (
        <div
          key={product.id}
          className="embla__slide pb-4"
          data-slides-mobile={slidesPerView.mobile}
          data-slides-desktop={slidesPerView.desktop}
          style={{ "--slide-gap": `${gap}px` } as React.CSSProperties}
        >
          <ThemedProductCard product={product} hrefBase={hrefBase} />
        </div>
      ))}
    </ProductSliderShell>
  );
}
