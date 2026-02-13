import { SmartContainer } from "@/components/common/SmartContainer";
import * as LucideIcons from "lucide-react";
import { createElement } from "react";
import type { ComponentType, SVGProps } from "react";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

type Feature = {
  icon: string;
  title: string;
  description: string;
};

type FeaturesBarProps = {
  features?: Feature[];
  featureTitleTag?: string;
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

const defaultFeatures: Feature[] = [
  {
    icon: "Truck",
    title: "Livraison gratuite",
    description: "À partir de 50€ d'achat",
  },
  {
    icon: "ShieldCheck",
    title: "Paiement sécurisé",
    description: "Transactions 100% sécurisées",
  },
  {
    icon: "Undo2",
    title: "Retours faciles",
    description: "30 jours pour changer d'avis",
  },
  { icon: "MessageCircle", title: "Support client", description: "Disponible 7j/7" },
];

export function FeaturesBar({
  features = defaultFeatures,
  featureTitleTag = "h3",
  padding,
  margin,
}: FeaturesBarProps) {
  const normalizedFeatureTitleTag = normalizeHeadingTag(featureTitleTag, "h3");
  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  return (
    <section
      className={`border-y py-8 ${paddingClasses} ${marginClasses}`.trim()}
      style={{
        borderColor: "var(--theme-border-default, #e5e7eb)",
        backgroundColor: "var(--theme-card-bg, #ffffff)",
      }}
    >
      <SmartContainer>
        <div className="grid grid-cols-2 gap-6 md:grid-cols-4">
          {features.map((feature, index) => {
            const Icon =
              LucideIcons[feature.icon as keyof typeof LucideIcons] as ComponentType<
                SVGProps<SVGSVGElement>
              >;

            return (
              <div
                key={index}
                className="text-center"
              >
                <div className="mb-2 flex justify-center">
                  <Icon
                    className="h-8 w-8"
                    style={{ color: "var(--theme-primary, #000000)" }}
                  />
                </div>

                {createElement(
                  normalizedFeatureTitleTag,
                  {
                    className: "text-sm font-semibold",
                    style: { color: "var(--theme-heading-color, #111827)" },
                  },
                  feature.title
                )}

                <p
                  className="mt-1 text-xs"
                  style={{ color: "var(--theme-muted-color, #6b7280)" }}
                >
                  {feature.description}
                </p>
              </div>
            );
          })}
        </div>
      </SmartContainer>
    </section>
  );
}
