import { OptimizedImage } from "@/components/common/OptimizedImage";
import { SmartContainer } from "@/components/common/SmartContainer";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

type Testimonial = {
  name: string;
  role?: string;
  avatar?: string;
  content: string;
  rating?: number;
};

type TestimonialsProps = {
  title?: string;
  testimonials?: Testimonial[];
  padding?: Record<string, unknown>;
  margin?: Record<string, unknown>;
};

export function Testimonials({
  title = "Ce que disent nos clients",
  testimonials = [],
  padding,
  margin,
}: TestimonialsProps) {
  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return (
    <section className={`${paddingClasses} ${marginClasses}`.trim()}>
      <SmartContainer>
        <h2
          className="mb-10 text-center text-2xl font-bold md:text-3xl"
          style={{ color: "var(--theme-heading-color, #111827)" }}
        >
          {title}
        </h2>

        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {testimonials.map((testimonial, index) => (
            <div
              key={index}
              className="rounded-xl p-6"
              style={{
                backgroundColor: "var(--theme-card-bg, #ffffff)",
                borderRadius: "var(--theme-border-radius, 12px)",
                border: "1px solid var(--theme-border-default, #e5e7eb)",
              }}
            >
              {/* Rating */}
              {testimonial.rating && (
                <div className="mb-3 flex gap-1">
                  {Array.from({ length: 5 }).map((_, i) => (
                    <span
                      key={i}
                      className="text-lg"
                      style={{
                        color:
                          i < testimonial.rating!
                            ? "var(--theme-promo-bg, #fbbf24)"
                            : "var(--theme-border-default, #e5e7eb)",
                      }}
                    >
                      ★
                    </span>
                  ))}
                </div>
              )}

              {/* Content */}
              <p
                className="text-sm leading-relaxed"
                style={{ color: "var(--theme-body-color, #374151)" }}
              >
                &ldquo;{testimonial.content}&rdquo;
              </p>

              {/* Author */}
              <div className="mt-4 flex items-center gap-3">
                {testimonial.avatar ? (
                  <div className="relative h-10 w-10 rounded-full overflow-hidden">
                    <OptimizedImage
                      src={testimonial.avatar}
                      alt={testimonial.name}
                      fill
                      sizes="40px"
                      className="object-cover"
                      fallback={
                        <div
                          className="flex h-full w-full items-center justify-center text-sm font-semibold"
                          style={{
                            backgroundColor: "var(--theme-primary, #111827)",
                            color: "var(--theme-button-primary-text, #ffffff)",
                          }}
                        >
                          {testimonial.name.charAt(0)}
                        </div>
                      }
                    />
                  </div>
                ) : (
                  <div
                    className="flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold"
                    style={{
                      backgroundColor: "var(--theme-primary, #111827)",
                      color: "var(--theme-button-primary-text, #ffffff)",
                    }}
                  >
                    {testimonial.name.charAt(0)}
                  </div>
                )}
                <div>
                  <p
                    className="text-sm font-semibold"
                    style={{ color: "var(--theme-heading-color, #111827)" }}
                  >
                    {testimonial.name}
                  </p>
                  {testimonial.role && (
                    <p
                      className="text-xs"
                      style={{ color: "var(--theme-muted-color, #6b7280)" }}
                    >
                      {testimonial.role}
                    </p>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      </SmartContainer>
    </section>
  );
}
