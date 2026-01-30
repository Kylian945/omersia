"use client";

import { SmartContainer } from "@/components/common/SmartContainer";
import { useState } from "react";
import { Button } from "@/components/common/Button";
import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

type NewsletterProps = {
  title?: string;
  description?: string;
  placeholder?: string;
  buttonText?: string;
  padding?: Record<string, unknown>;
  margin?: Record<string, unknown>;
};

export function Newsletter({
  title = "Restez informé",
  description = "Inscrivez-vous à notre newsletter pour recevoir nos offres exclusives et nouveautés en avant-première.",
  placeholder = "Votre adresse email",
  buttonText = "S'inscrire",
  padding,
  margin,
}: NewsletterProps) {
  const [email, setEmail] = useState("");
  const [status, setStatus] = useState<"idle" | "loading" | "success" | "error">("idle");
  const [message, setMessage] = useState("");

  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(padding);
  const marginConfig = validateSpacingConfig(margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setStatus("loading");

    // Placeholder implementation - simulates newsletter subscription
    setTimeout(() => {
      setStatus("success");
      setMessage("Merci pour votre inscription !");
      setEmail("");
    }, 1000);
  };

  return (
    <section
      className={`rounded-2xl ${paddingClasses} ${marginClasses}`.trim()}
      style={{
        backgroundColor: "var(--theme-page-bg, #f6f6f7)",
      }}
    >
      <SmartContainer>
        <div
          className="rounded-2xl p-8 md:p-12"
          style={{
            backgroundColor: "var(--theme-card-bg, #ffffff)",
            borderRadius: "var(--theme-border-radius, 16px)",
          }}
        >
          <div className="mx-auto max-w-2xl text-center">
            <h2
              className="text-2xl font-bold md:text-3xl"
              style={{ color: "var(--theme-heading-color, #111827)" }}
            >
              {title}
            </h2>
            <p
              className="mt-3 text-sm md:text-base"
              style={{ color: "var(--theme-body-color, #6b7280)" }}
            >
              {description}
            </p>

            <form onSubmit={handleSubmit} className="mt-6">
              <div className="flex flex-col gap-3 sm:flex-row">
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder={placeholder}
                  required
                  disabled={status === "loading" || status === "success"}
                  className="flex-1 rounded-lg border px-4 py-2 text-sm focus:outline-none focus:ring-2"
                  style={{
                    borderColor: "var(--theme-border-default, #e5e7eb)",
                    backgroundColor: "var(--theme-input-bg, #ffffff)",
                    color: "var(--theme-body-color, #111827)",
                  }}
                />
                <Button
                  type="submit"
                  size="md"
                  variant="primary"
                >
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
            </form>

            <p
              className="mt-4 text-xs"
              style={{ color: "var(--theme-muted-color, #9ca3af)" }}
            >
              En vous inscrivant, vous acceptez notre politique de
              confidentialité. Vous pouvez vous désinscrire à tout moment.
            </p>
          </div>
        </div>
      </SmartContainer>
    </section>
  );
}
