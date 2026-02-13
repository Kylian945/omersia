import { OptimizedImage } from "@/components/common/OptimizedImage";
import { Button } from "@/components/common/Button";
import { SmartContainer } from "@/components/common/SmartContainer";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";
import { createElement } from "react";
import { sanitizeHTML } from "@/lib/html-sanitizer";

type HeroBannerProps = {
  title?: string;
  titleTag?: string;
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

type HeadingTag = "h1" | "h2" | "h3" | "h4" | "h5" | "h6";

function normalizeHeadingTag(tag: string | undefined, fallback: HeadingTag): HeadingTag {
  const normalizedTag = tag?.toLowerCase();
  const validTags: HeadingTag[] = ["h1", "h2", "h3", "h4", "h5", "h6"];
  return normalizedTag && validTags.includes(normalizedTag as HeadingTag)
    ? (normalizedTag as HeadingTag)
    : fallback;
}

const VISION_UI = {
  rootClass: "",
  rootBackground:
    "linear-gradient(to bottom, var(--theme-page-bg, #ffffff) 0%, var(--theme-card-bg, #f6f6f7) 100%)",
  layoutClass: "grid gap-8 md:grid-cols-2 md:items-center",
  contentClass: "space-y-6",
  badgeClass: "rounded-full",
  titleClass: "text-4xl font-bold tracking-tight md:text-5xl lg:text-6xl",
  descriptionClass: "max-w-lg text-base md:text-lg",
  ctaClass: "flex flex-wrap gap-3",
  imageClass: "rounded-2xl shadow-2xl",
  placeholderClass: "rounded-2xl shadow-2xl",
  subtitleClass: "font-normal",
};

export function HeroBanner({
  title = "Bienvenue sur notre boutique",
  titleTag = "h1",
  subtitle,
  description = "Decouvrez nos produits exceptionnels",
  primaryCta = { text: "Decouvrir", href: "/products" },
  secondaryCta,
  image,
  badge,
  padding,
  margin,
}: HeroBannerProps) {
  const ui = VISION_UI;
  const normalizedTitleTag = normalizeHeadingTag(titleTag, "h1");
  const safeDescription = sanitizeHTML(description || "");

  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return (
    <section
      className={`relative overflow-hidden py-16 md:py-24 ${ui.rootClass} ${paddingClasses} ${marginClasses}`.trim()}
      style={{ background: ui.rootBackground }}
    >
      <SmartContainer>
        <div className={ui.layoutClass}>
          <div className={ui.contentClass}>
            {badge && (
              <div
                className={`inline-flex items-center gap-2 border px-4 py-1.5 text-xs font-medium shadow-sm ${ui.badgeClass}`.trim()}
                style={{
                  borderColor: "var(--theme-border-default, #e5e7eb)",
                  backgroundColor: "var(--theme-card-bg, #ffffff)",
                  color: "var(--theme-muted-color, #6b7280)",
                }}
              >
                {badge}
                <span
                  className="h-2 w-2 rounded-full"
                  style={{ backgroundColor: "var(--theme-success-color, #10b981)" }}
                />
              </div>
            )}

            <div className="space-y-4">
              {createElement(
                normalizedTitleTag,
                {
                  className: ui.titleClass,
                  style: { color: "var(--theme-heading-color, #111827)" },
                },
                <>
                  {title}
                  {subtitle && (
                    <span
                      className={`mt-2 block text-3xl md:text-4xl ${ui.subtitleClass}`.trim()}
                      style={{ color: "var(--theme-muted-color, #6b7280)" }}
                    >
                      {subtitle}
                    </span>
                  )}
                </>
              )}

              <div
                className={ui.descriptionClass}
                style={{ color: "var(--theme-body-color, #374151)" }}
                dangerouslySetInnerHTML={{ __html: safeDescription }}
              />
            </div>

            <div className={ui.ctaClass}>
              <Button href={primaryCta.href} variant="primary" size="md">
                {primaryCta.text}
              </Button>
              {secondaryCta && (
                <Button href={secondaryCta.href} variant="secondary" size="md">
                  {secondaryCta.text}
                </Button>
              )}
            </div>
          </div>

          <div className="relative">
            {image ? (
              <div className={`relative aspect-square overflow-hidden ${ui.imageClass}`.trim()}>
                <OptimizedImage
                  src={image}
                  alt={title || "Hero banner"}
                  fill
                  priority
                  sizes="(max-width: 768px) 100vw, 50vw"
                  className="object-cover"
                  fallback={<div className="h-full w-full bg-[var(--theme-input-bg,#ffffff)]" />}
                />
              </div>
            ) : (
              <div
                className={`relative aspect-square overflow-hidden ${ui.placeholderClass}`.trim()}
                style={{
                  backgroundColor: "var(--theme-card-bg, #ffffff)",
                  border: "1px dashed var(--theme-border-default, #e5e7eb)",
                }}
              />
            )}
          </div>
        </div>
      </SmartContainer>
    </section>
  );
}
