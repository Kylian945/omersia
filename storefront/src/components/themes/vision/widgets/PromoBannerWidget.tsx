import { SmartContainer } from "@/components/common/SmartContainer";
import { Button } from "@/components/common/Button";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

type PromoBannerProps = {
  title?: string;
  description?: string;
  ctaText?: string;
  ctaHref?: string;
  badge?: string;
  variant?: "default" | "gradient";
  padding?: Record<string, unknown>;
  margin?: Record<string, unknown>;
};

export function PromoBanner({
  title = "Offre spéciale",
  description,
  ctaText,
  ctaHref,
  badge,
  variant = "default",
  padding,
  margin,
}: PromoBannerProps) {
  const isGradient = variant === "gradient";

  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return (
    <section className={`${paddingClasses} ${marginClasses}`.trim()}>
      <SmartContainer>
        <div
          className={`relative overflow-hidden rounded-2xl p-8 md:p-12 ${
            isGradient
              ? "bg-linear-to-r from-gray-50 to-gray-100"
              : ""
          }`}
          style={
            !isGradient
              ? {
                  background: `linear-gradient(135deg, var(--theme-primary, #111827) 0%, var(--theme-secondary, #6366f1) 100%)`,
                }
              : undefined
          }
        >
          {/* Background Pattern */}
          <div className="absolute inset-0 opacity-10">
            <div className="absolute right-0 top-0 h-64 w-64 rounded-full bg-white blur-3xl" />
            <div className="absolute bottom-0 left-0 h-64 w-64 rounded-full bg-white blur-3xl" />
          </div>

          <div className="relative z-10 mx-auto max-w-3xl text-center">
            {badge && (
              <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-black px-4 py-1.5 text-xs font-medium text-white backdrop-blur-sm">
                {badge}
              </div>
            )}

            <h2 className="text-3xl font-bold text-black md:text-4xl">
              {title}
            </h2>

            {description && (
              <p className="mt-4 text-base text-gray-500/90 md:text-lg">
                {description}
              </p>
            )}

            {ctaText && ctaHref && (
              <div className="mt-6">
                <Button
                  href={ctaHref}
                  variant="secondary"
                  size="md"
                >
                  {ctaText}
                </Button>
              </div>
            )}
          </div>
        </div>
      </SmartContainer>
    </section>
  );
}
