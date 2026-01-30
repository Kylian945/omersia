import { ListingProduct } from "./ListingProducts";
import { ThemedProductCard } from "./ThemedProductCard";

type Props = {
  products: ListingProduct[];
  hrefBase?: string;
  columns?: { desktop: number; mobile: number };
  gap?: number;
};

export async function ProductGrid({
  products,
  hrefBase = "/products",
  columns = { desktop: 4, mobile: 2 },
  gap = 16,
}: Props) {
  if (!products || products.length === 0) return null;

  // Map columns to Tailwind classes
  // Using explicit classes to ensure they're included in Tailwind's purge
  const mobileGridClasses: Record<number, string> = {
    1: "grid-cols-1",
    2: "grid-cols-2",
    3: "grid-cols-3",
  };

  const desktopGridClasses: Record<number, string> = {
    2: "lg:grid-cols-2",
    3: "lg:grid-cols-3",
    4: "lg:grid-cols-4",
    5: "lg:grid-cols-5",
    6: "lg:grid-cols-6",
  };

  const mobileClass = mobileGridClasses[columns.mobile] || "grid-cols-2";
  const desktopClass = desktopGridClasses[columns.desktop] || "lg:grid-cols-4";

  return (
    <div
      className={`grid ${mobileClass} ${desktopClass}`}
      style={{ gap: `${gap}px` }}
    >
      {products.map((product) => (
        <div key={product.id}>
          <ThemedProductCard product={product} hrefBase={hrefBase} />
        </div>
      ))}
    </div>
  );
}
