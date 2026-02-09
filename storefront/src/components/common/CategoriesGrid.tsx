import Link from "next/link";
import { OptimizedImage } from "./OptimizedImage";
import { Category } from "@/lib/types/category-types";

type CategoriesGridProps = {
  title?: string;
  categories: Category[];
  columns?: { desktop: number; mobile: number };
  gap?: number;
};

// Helper to get grid columns class
function getGridColsClass(cols: number, prefix: string = ""): string {
  const colsMap: Record<number, string> = {
    1: "grid-cols-1",
    2: "grid-cols-2",
    3: "grid-cols-3",
    4: "grid-cols-4",
    5: "grid-cols-5",
    6: "grid-cols-6",
  };
  const className = colsMap[cols] || "grid-cols-4";
  return prefix ? className.replace("grid-cols-", `${prefix}:grid-cols-`) : className;
}

export function CategoriesGrid({
  title,
  categories,
  columns = { desktop: 4, mobile: 2 },
  gap = 16,
}: CategoriesGridProps) {
  const mobileColsClass = getGridColsClass(columns.mobile);
  const desktopColsClass = getGridColsClass(columns.desktop, "lg");

  return (
    <section>
      {title && (
        <h2 className="text-lg font-semibold text-neutral-900 mb-4"
          style={{ color: "var(--theme-heading-color, #111827)" }}
        >
          {title}
        </h2>
      )}

      {categories.length === 0 ? (
        <p className="text-xs text-center mt-6 text-gray-400 px-6 py-2 border border-dashed rounded-md border-gray-300">Aucune catégorie existante (Ajoutez des éléments dans l&apos;administration)
        </p>
      ) : (
        <div
          className={`grid ${mobileColsClass} md:grid-cols-3 ${desktopColsClass}`}
          style={{ gap: `${gap}px` }}
        >
          {categories.map((category) => (
            <Link
              key={category.slug}
              href={`/categories/${category.slug}`}
              prefetch={true}
              className="group relative overflow-hidden rounded-xl transition-transform hover:scale-105 border border-gray-100"
              style={{
                backgroundColor: "var(--theme-card-bg, #ffffff)",
                borderRadius: "var(--theme-border-radius, 12px)",
              }}
            >
              {/* Image */}
              <div className="relative aspect-square overflow-hidden">
                {category.image ? (
                  <OptimizedImage
                    src={category.image}
                    alt={category.name}
                    fill
                    sizes="(max-width: 768px) 50vw, (max-width: 1200px) 33vw, 25vw"
                    className="object-cover transition-transform duration-300 group-hover:scale-110"
                    fallback={<div className="h-full w-full bg-gray-100" />}
                  />
                ) : (
                  <div
                    className="h-full w-full bg-gray-100"
                  // style={{
                  //   backgroundColor: "var(--theme-page-bg, #f6f6f7)",
                  // }}
                  />
                )}

                {/* Overlay on hover */}
                <div className="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/20" />
              </div>

              {/* Category Info */}
              <div className="p-4">
                <div
                  className="text-sm font-semibold"
                  style={{ color: "var(--theme-heading-color, #111827)" }}
                >
                  {category.name}
                </div>
                {category.count !== undefined && (
                  <p
                    className="mt-1 text-xs"
                    style={{ color: "var(--theme-muted-color, #6b7280)" }}
                  >
                    {category.count} produit{category.count > 1 ? "s" : ""}
                  </p>
                )}
              </div>
            </Link>
          ))}
        </div>
      )}
    </section>
  );
}
