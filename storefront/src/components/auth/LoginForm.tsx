"use client";

import { useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { Button } from "@/components/common/Button";
import { safeDecodeURIComponent } from "@/lib/utils/error-utils";
import { logger } from "@/lib/logger";

export function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const error = searchParams.get("error") ? safeDecodeURIComponent(searchParams.get("error")!) : null;
  const success = searchParams.get("success") ? safeDecodeURIComponent(searchParams.get("success")!) : null;

  // Dispatch auth:changed event when returning from successful login
  useEffect(() => {
    if (success) {
      window.dispatchEvent(new Event("auth:changed"));
    }
  }, [success]);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);

    try {
      const response = await fetch("/auth/login", {
        method: "POST",
        body: formData,
      });

      if (response.redirected) {
        // Dispatch event before redirect
        window.dispatchEvent(new Event("auth:changed"));
        window.location.href = response.url;
      } else if (response.ok) {
        window.dispatchEvent(new Event("auth:changed"));
        router.push("/");
      }
    } catch (error) {
      logger.error("Login error:", error);
    }
  };

  return (
    <div className="rounded-2xl bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] shadow-sm p-5 space-y-4">
      {success && (
        <div className="text-xs text-emerald-700 bg-emerald-50 border border-emerald-100 px-3 py-2 rounded-xl">
          {success}
        </div>
      )}

      {error && (
        <div className="text-xs text-rose-600 bg-rose-50 border border-rose-100 px-3 py-2 rounded-xl">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-3">
        <div className="space-y-1.5">
          <label
            htmlFor="email"
            className="block text-xs font-medium text-[var(--theme-body-color,#374151)]"
          >
            Adresse e-mail
          </label>
          <input
            id="email"
            name="email"
            type="email"
            required
            autoComplete="email"
            className="w-full rounded-xl border border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-page-bg,#f6f6f7)] px-3 py-2 text-xs text-[var(--theme-heading-color,#111827)] placeholder:text-[var(--theme-muted-color,#6b7280)] focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)] focus:bg-[var(--theme-card-bg,#ffffff)] transition"
            placeholder="vous@example.com"
          />
        </div>

        <div className="space-y-1.5">
          <div className="flex items-center justify-between">
            <label
              htmlFor="password"
              className="block text-xs font-medium text-[var(--theme-body-color,#374151)]"
            >
              Mot de passe
            </label>
            <Link
              href="/password/forgot"
              className="text-xxxs text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-heading-color,#111827)] transition"
            >
              Mot de passe oublié ?
            </Link>
          </div>
          <input
            id="password"
            name="password"
            type="password"
            required
            autoComplete="current-password"
            className="w-full rounded-xl border border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-page-bg,#f6f6f7)] px-3 py-2 text-xs text-[var(--theme-heading-color,#111827)] placeholder:text-[var(--theme-muted-color,#6b7280)] focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)] focus:bg-[var(--theme-card-bg,#ffffff)] transition"
            placeholder="Votre mot de passe"
          />
        </div>

        <div className="flex items-center justify-between gap-3 pt-1">
          <label className="inline-flex items-center gap-1.5 cursor-pointer">
            <input
              type="checkbox"
              name="remember"
              defaultChecked
              className="h-3 w-3 rounded border-[var(--theme-border-default,#e5e7eb)] text-[var(--theme-heading-color,#111827)] focus:ring-[var(--theme-primary,#111827)]"
            />
            <span className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
              Rester connecté
            </span>
          </label>

          <div className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
            Connexion sécurisée 🔒
          </div>
        </div>

        <Button
          type="submit"
          size="md"
          variant="primary"
          className="w-full"
        >
          Se connecter
        </Button>
      </form>

      <div className="pt-2 border-t border-[var(--theme-border-default,#e5e7eb)] flex flex-col gap-1">
        <p className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
          Nouveau client ?
          <Link
            href="/register"
            className="ml-1 text-xxxs text-[var(--theme-heading-color,#111827)] font-medium hover:underline"
          >
            Créer un compte
          </Link>
        </p>
      </div>
    </div>
  );
}
