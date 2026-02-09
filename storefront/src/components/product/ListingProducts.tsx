import { ThemedProductCard } from "./ThemedProductCard";
import { ListingProductsClient } from "./ListingProductsClient";

type ProductTranslation = {
  name?: string | null;
  slug?: string | null;
  short_description?: string | null;
};

type ProductImage = {
  id: number;
  url?: string | null;
  path?: string | null;
  is_main?: boolean;
  position?: number | null;
};

export type ListingProduct = {
  id: number;
  sku?: string | null;
  price?: number | null;
  compare_at_price?: number | null;
  stock_qty?: number | null;
  images?: ProductImage[];
  translations?: ProductTranslation[];
  variants?: ListingProduct[];
  type?: string;
  name?: string;
  has_variants?: boolean;
  from_price?: number | null;
};

type ListingProductsProps = {
  products: ListingProduct[];
  hrefBase?: string; // ex: "/products"
  emptyMessage?: string;
};

export async function ListingProducts({
  products,
  hrefBase = "/products",
  emptyMessage = "Aucun produit ne correspond à vos filtres.",
}: ListingProductsProps) {
  if (!products || !products.length) {
    return <ListingProductsClient isEmpty emptyMessage={emptyMessage} />;
  }

  return (
    <ListingProductsClient>
      {products.map((product) => (
        <ThemedProductCard
          key={product.id}
          product={product}
          hrefBase={hrefBase}
        />
      ))}
    </ListingProductsClient>
  );
}
