import { CategoriesGrid } from "@/components/common/CategoriesGrid";
import { CategoriesSlider } from "@/components/common/CategoriesSlider";
import { getCategories } from "@/lib/api";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

type CategoriesGridWidgetProps = {
  categorySlugs?: string[];
  maxCategories?: number | null;
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

export async function CategoriesGridWidget({
  categorySlugs = [],
  maxCategories,
  displayMode = "grid",
  slidesPerView = { desktop: 4, mobile: 2 },
  slidesToScroll = { desktop: 1, mobile: 1 },
  columns = { desktop: 4, mobile: 2 },
  showArrows = true,
  showDots = true,
  autoplay = false,
  gap = 16,
  padding,
  margin,
}: CategoriesGridWidgetProps) {
  // Fetch ALL categories from API (including children)
  const allCategories = await getCategories("fr", false);

  // Filter categories if categorySlugs is provided and not empty
  let categories = allCategories;
  if (categorySlugs && categorySlugs.length > 0) {
    // Filter out empty strings
    const validSlugs = categorySlugs.filter((slug) => slug && slug.trim());
    if (validSlugs.length > 0) {
      // Filter categories by selected slugs and preserve order
      categories = validSlugs
        .map((slug) => allCategories.find((cat) => cat.slug === slug))
        .filter((cat) => cat !== undefined) as typeof allCategories;
    }
  } else if (maxCategories && maxCategories > 0) {
    // No categories selected, apply maxCategories limit
    categories = allCategories.slice(0, maxCategories);
  }

  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  if (categories.length === 0) {
    return (
      <div className={`${paddingClasses} ${marginClasses}`.trim()}>
        <p
          className="text-xs px-6 py-2 border border-dashed"
          style={{
            color: "var(--theme-muted-color, #6b7280)",
            borderColor: "var(--theme-border-default, #e5e7eb)",
            borderRadius: "var(--theme-border-radius, 12px)",
          }}
        >
          Aucune catégorie existante (Ajoutez des éléments dans l&apos;administration)
        </p>
      </div>
    );
  }

  return (
    <div className={`${paddingClasses} ${marginClasses}`.trim()}>
      {displayMode === "slider" ? (
        <CategoriesSlider
          categories={categories}
          slidesPerView={slidesPerView}
          slidesToScroll={slidesToScroll}
          showArrows={showArrows}
          showDots={showDots}
          autoplay={autoplay}
          gap={gap}
        />
      ) : (
        <CategoriesGrid categories={categories} columns={columns} gap={gap} />
      )}
    </div>
  );
}
