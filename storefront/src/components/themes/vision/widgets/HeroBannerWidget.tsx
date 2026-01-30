import Image from "next/image";
import { Button } from "@/components/common/Button";
import { SmartContainer } from "@/components/common/SmartContainer";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

type HeroBannerProps = {
  title?: string;
  subtitle?: string;
  description?: string;
  primaryCta?: {
    text: string;
    href: string;
  };
  secondaryCta?: {
    text: string;
    href: string;
  };
  image?: string;
  badge?: string;
  padding?: Record<string, unknown>;
  margin?: Record<string, unknown>;
};

export function HeroBanner({
  title = "Bienvenue sur notre boutique",
  subtitle,
  description = "Découvrez nos produits exceptionnels",
  primaryCta = { text: "Découvrir", href: "/products" },
  secondaryCta,
  image,
  badge,
  padding,
  margin,
}: HeroBannerProps) {
  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return (
    <section
      className={`relative overflow-hidden py-16 md:py-24 ${paddingClasses} ${marginClasses}`.trim()}
      style={{
        background:
          "linear-gradient(to bottom, var(--theme-page-bg, #ffffff) 0%, var(--theme-card-bg, #f6f6f7) 100%)",
      }}
    >
      <SmartContainer>
        <div className="grid gap-8 md:grid-cols-2 md:items-center">
          {/* Left Content */}
          <div className="space-y-6">
            {badge && (
              <div className="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-medium shadow-sm"
                style={{
                  borderColor: "var(--theme-border-default, #e5e7eb)",
                  backgroundColor: "var(--theme-card-bg, #ffffff)",
                  color: "var(--theme-muted-color, #6b7280)",
                }}
              >
                {badge}
                <span className="h-2 w-2 rounded-full"
                  style={{ backgroundColor: "var(--theme-success-color, #10b981)" }}
                />
              </div>
            )}

            <div className="space-y-4">
              <h1
                className="text-4xl font-bold tracking-tight md:text-5xl lg:text-6xl"
                style={{ color: "var(--theme-heading-color, #111827)" }}
              >
                {title}
                {subtitle && (
                  <span
                    className="mt-2 block text-3xl font-normal md:text-4xl"
                    style={{ color: "var(--theme-muted-color, #6b7280)" }}
                  >
                    {subtitle}
                  </span>
                )}
              </h1>

              <p
                className="max-w-lg text-base md:text-lg"
                style={{ color: "var(--theme-body-color, #374151)" }}
              >
                {description}
              </p>
            </div>

            <div className="flex flex-wrap gap-3">
              <Button href={primaryCta.href} variant="primary" size="md">
                {primaryCta.text}
              </Button>
              {secondaryCta && (
                <Button
                  href={secondaryCta.href}
                  variant="secondary"
                  size="md"
                >
                  {secondaryCta.text}
                </Button>
              )}
            </div>
          </div>

          {/* Right Image */}
          <div className="relative">
            {image ? (
              <div className="relative aspect-square overflow-hidden rounded-2xl shadow-2xl">
                <Image
                  src={image}
                  alt={title || 'Hero banner'}
                  fill
                  priority
                  sizes="(max-width: 768px) 100vw, 50vw"
                  className="object-cover"
                />
              </div>
            ) : (
              <div
                className="relative aspect-square rounded-2xl p-8 shadow-2xl"
                style={{ backgroundColor: "var(--theme-card-bg, #ffffff)" }}
              >
                {/* Placeholder illustration */}
                <div className="grid h-full grid-cols-2 gap-4">
                  {[1, 2, 3, 4].map((i) => (
                    <div
                      key={i}
                      className="rounded-xl bg-gray-100"
                      // style={{
                      //   backgroundColor: "var(--theme-page-bg, #f6f6f7)",
                      // }}
                    />
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </SmartContainer>
    </section>
  );
}
