"use client";

import { SmartContainer } from "@/components/common/SmartContainer";
import { createElement, useState } from "react";
import { Button } from "@/components/common/Button";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";
import type { FormEvent } from "react";
import { sanitizeHTML } from "@/lib/html-sanitizer";

type NewsletterProps = {
  title?: string;
  titleTag?: string;
  description?: string;
  placeholder?: string;
  buttonText?: string;
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
  sectionClass: "rounded-2xl",
  panelClass: "rounded-2xl p-8 md:p-12",
  layoutClass: "mx-auto max-w-2xl text-center",
  titleClass: "text-2xl font-bold md:text-3xl",
  descriptionClass: "mt-3 text-sm md:text-base",
  formClass: "mt-6",
  inputClass: "flex-1 rounded-lg border px-4 py-2 text-sm focus:outline-none focus:ring-2",
  noteClass: "mt-4 text-xs",
};

export function Newsletter({
  title = "Restez informe",
  titleTag = "h2",
  description = "Inscrivez-vous a notre newsletter pour recevoir nos offres exclusives et nouveautes en avant-premiere.",
  placeholder = "Votre adresse email",
  buttonText = "S'inscrire",
  padding,
  margin,
}: NewsletterProps) {
  const ui = VISION_UI;
  const normalizedTitleTag = normalizeHeadingTag(titleTag, "h2");
  const safeDescription = sanitizeHTML(description || "");

  const [email, setEmail] = useState("");
  const [status, setStatus] = useState<"idle" | "loading" | "success" | "error">(
    "idle"
  );
  const [message, setMessage] = useState("");

  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setStatus("loading");

    setTimeout(() => {
      setStatus("success");
      setMessage("Merci pour votre inscription !");
      setEmail("");
    }, 1000);
  };

  return (
    <section
      className={`${ui.sectionClass} ${paddingClasses} ${marginClasses}`.trim()}
      style={{ backgroundColor: "var(--theme-page-bg, #f6f6f7)" }}
    >
      <SmartContainer>
        <div
          className={ui.panelClass}
          style={{
            backgroundColor: "var(--theme-card-bg, #ffffff)",
            borderRadius: "var(--theme-border-radius, 16px)",
            borderColor: "var(--theme-border-default, #e5e7eb)",
          }}
        >
          <div className={ui.layoutClass}>
            <div>
              {createElement(
                normalizedTitleTag,
                {
                  className: ui.titleClass,
                  style: { color: "var(--theme-heading-color, #111827)" },
                },
                title
              )}
              <p
                className={ui.descriptionClass}
                style={{ color: "var(--theme-body-color, #6b7280)" }}
                dangerouslySetInnerHTML={{ __html: safeDescription }}
              />
            </div>

            <form onSubmit={handleSubmit} className={ui.formClass}>
              <div className="flex flex-col gap-3 sm:flex-row">
                <input
                  type="email"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  placeholder={placeholder}
                  required
                  disabled={status === "loading" || status === "success"}
                  className={ui.inputClass}
                  style={{
                    borderColor: "var(--theme-border-default, #e5e7eb)",
                    backgroundColor: "var(--theme-input-bg, #ffffff)",
                    color: "var(--theme-body-color, #111827)",
                  }}
                />
                <Button type="submit" size="md" variant="primary">
                  {status === "loading" ? "Envoi..." : buttonText}
                </Button>
              </div>

              {status === "success" && (
                <p
                  className="mt-3 text-sm"
                  style={{ color: "var(--theme-success-color, #10b981)" }}
                >
                  {message}
                </p>
              )}

              {status === "error" && (
                <p
                  className="mt-3 text-sm"
                  style={{ color: "var(--theme-error-color, #ef4444)" }}
                >
                  {message}
                </p>
              )}

              <p
                className={ui.noteClass}
                style={{ color: "var(--theme-muted-color, #9ca3af)" }}
              >
                En vous inscrivant, vous acceptez notre politique de
                confidentialite. Vous pouvez vous desinscrire a tout moment.
              </p>
            </form>
          </div>
        </div>
      </SmartContainer>
    </section>
  );
}
