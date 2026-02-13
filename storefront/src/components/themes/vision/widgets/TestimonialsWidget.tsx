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

const VISION_UI = {
  headingWrapClass: "mb-10 text-center",
  headingClass: "text-2xl font-bold md:text-3xl",
  gridClass: "grid gap-6 md:grid-cols-2 lg:grid-cols-3",
  cardClass: "rounded-2xl border p-6 shadow-sm",
  ratingClass: "mb-3 flex gap-1",
  quoteClass: "text-sm leading-relaxed",
  authorWrapClass: "mt-4 flex items-center gap-3",
  nameClass: "text-sm font-semibold",
  roleClass: "text-xs",
};

export function Testimonials({
  title = "Ce que disent nos clients",
  testimonials = [],
  padding,
  margin,
}: TestimonialsProps) {
  const ui = VISION_UI;

  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return (
    <section className={`${paddingClasses} ${marginClasses}`.trim()}>
      <SmartContainer>
        <div className={ui.headingWrapClass}>
          <h2
            className={ui.headingClass}
            style={{ color: "var(--theme-heading-color, #111827)" }}
          >
            {title}
          </h2>
        </div>

        <div className={ui.gridClass}>
          {testimonials.map((testimonial, index) => {
            const rating =
              typeof testimonial.rating === "number" ? testimonial.rating : 0;

            return (
              <div
                key={index}
                className={ui.cardClass}
                style={{
                  backgroundColor: "var(--theme-card-bg, #ffffff)",
                  borderRadius: "var(--theme-border-radius, 12px)",
                  border: "1px solid var(--theme-border-default, #e5e7eb)",
                }}
              >
                {rating > 0 && (
                  <div className={ui.ratingClass}>
                    {Array.from({ length: 5 }).map((_, i) => (
                      <span
                        key={i}
                        className="text-lg"
                        style={{
                          color:
                            i < rating
                              ? "var(--theme-promo-bg, #fbbf24)"
                              : "var(--theme-border-default, #e5e7eb)",
                        }}
                      >
                        ★
                      </span>
                    ))}
                  </div>
                )}

                <p
                  className={ui.quoteClass}
                  style={{ color: "var(--theme-body-color, #374151)" }}
                >
                  &ldquo;{testimonial.content}&rdquo;
                </p>

                <div className={ui.authorWrapClass}>
                  {testimonial.avatar ? (
                    <div className="relative h-10 w-10 overflow-hidden rounded-full">
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
                      className={ui.nameClass}
                      style={{ color: "var(--theme-heading-color, #111827)" }}
                    >
                      {testimonial.name}
                    </p>
                    {testimonial.role && (
                      <p
                        className={ui.roleClass}
                        style={{ color: "var(--theme-muted-color, #6b7280)" }}
                      >
                        {testimonial.role}
                      </p>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </SmartContainer>
    </section>
  );
}
