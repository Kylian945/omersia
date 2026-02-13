import { SmartContainer } from "@/components/common/SmartContainer";
import { Button } from "@/components/common/Button";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";
import { createElement } from "react";
import { sanitizeHTML } from "@/lib/html-sanitizer";

type PromoBannerProps = {
  title?: string;
  titleTag?: string;
  description?: string;
  ctaText?: string;
  ctaHref?: string;
  badge?: string;
  variant?: "default" | "gradient";
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
  containerClass: "rounded-2xl",
  gradientBackground:
    "linear-gradient(90deg, var(--theme-page-bg, #f6f6f7) 0%, var(--theme-input-bg, #ffffff) 100%)",
  flatBackground:
    "linear-gradient(135deg, var(--theme-primary, #111827) 0%, var(--theme-secondary, #6366f1) 100%)",
  contentClass: "relative z-10 mx-auto max-w-3xl text-center",
  badgeClass: "rounded-full",
  titleClass: "",
  descClass: "",
  ctaClass: "mt-6",
};

export function PromoBanner({
  title = "Offre speciale",
  titleTag = "h2",
  description,
  ctaText,
  ctaHref,
  badge,
  variant = "default",
  padding,
  margin,
}: PromoBannerProps) {
  const ui = VISION_UI;
  const isGradient = variant === "gradient";
  const normalizedTitleTag = normalizeHeadingTag(titleTag, "h2");
  const safeDescription = sanitizeHTML(description || "");

  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return (
    <section className={`${paddingClasses} ${marginClasses}`.trim()}>
      <SmartContainer>
        <div
          className={`relative overflow-hidden p-8 md:p-12 ${ui.containerClass}`.trim()}
          style={{ background: isGradient ? ui.gradientBackground : ui.flatBackground }}
        >
          <div className="absolute inset-0 opacity-10">
            <div
              className="absolute right-0 top-0 h-64 w-64 rounded-full blur-3xl"
              style={{ backgroundColor: "var(--theme-card-bg, #ffffff)" }}
            />
            <div
              className="absolute bottom-0 left-0 h-64 w-64 rounded-full blur-3xl"
              style={{ backgroundColor: "var(--theme-card-bg, #ffffff)" }}
            />
          </div>

          <div className={ui.contentClass}>
            {badge && (
              <div
                className={`mb-4 inline-flex items-center gap-2 px-4 py-1.5 text-xs font-medium backdrop-blur-sm ${ui.badgeClass}`.trim()}
                style={{
                  backgroundColor: "var(--theme-primary, #111827)",
                  color: "var(--theme-button-primary-text, #ffffff)",
                }}
              >
                {badge}
              </div>
            )}

            {createElement(
              normalizedTitleTag,
              {
                className: `text-3xl font-bold text-[var(--theme-heading-color,#111827)] md:text-4xl ${ui.titleClass}`.trim(),
              },
              title
            )}

            {description && (
              <div
                className={`mt-4 text-base text-[var(--theme-body-color,#374151)] md:text-lg ${ui.descClass}`.trim()}
                dangerouslySetInnerHTML={{ __html: safeDescription }}
              />
            )}

            {ctaText && ctaHref && (
              <div className={ui.ctaClass}>
                <Button href={ctaHref} variant="secondary" size="md">
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
