// app/(auth)/password/reset/page.tsx
import Link from "next/link";
import { Container } from "@/components/common/Container";
import { HeaderAuth } from "@/components/common/HeaderAuth";
import { Button } from "@/components/common/Button";
import { getShopInfo } from "@/lib/api-shop";
import { redirect } from "next/navigation";
import { safeDecodeURIComponent } from "@/lib/utils/error-utils";

type ResetPasswordPageSearchParams = {
  token?: string;
  email?: string;
  error?: string;
};

type ResetPasswordPageProps = {
  searchParams: Promise<ResetPasswordPageSearchParams>;
};

export default async function ResetPasswordPage({
  searchParams
}: ResetPasswordPageProps) {
  const params = await searchParams;
  const token = params?.token;
  const email = params?.email;
  const error = params?.error ? safeDecodeURIComponent(params.error) : null;
  const shopInfo = await getShopInfo();

  // Si pas de token ou d'email, rediriger vers la page de demande
  if (!token || !email) {
    redirect("/password/forgot");
  }

  return (
    <div className="min-h-screen flex flex-col bg-[var(--theme-page-bg,#f6f6f7)]">
      <HeaderAuth shopInfo={shopInfo}/>

      <main className="flex-1 flex items-center">
        <Container>
          <div className="w-full max-w-md mx-auto">
            <div className="mb-6">
              <h1 className="text-2xl font-semibold tracking-tight text-[var(--theme-heading-color,#111827)]">
                Nouveau mot de passe
              </h1>
              <p className="mt-1 text-xs text-[var(--theme-muted-color,#6b7280)]">
                Choisissez un nouveau mot de passe sécurisé pour votre compte.
              </p>
            </div>

            <div className="rounded-2xl bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] shadow-sm p-5 space-y-4">
              {error && (
                <div className="text-xs text-rose-600 bg-rose-50 border border-rose-100 px-3 py-2 rounded-xl">
                  {error}
                </div>
              )}

              <form
                method="POST"
                action="/auth/password/reset"
                className="space-y-3"
              >
                <input type="hidden" name="token" value={token} />
                <input type="hidden" name="email" value={email} />

                <div className="space-y-1.5">
                  <label
                    htmlFor="password"
                    className="block text-xs font-medium text-[var(--theme-body-color,#374151)]"
                  >
                    Nouveau mot de passe
                  </label>
                  <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    minLength={8}
                    autoComplete="new-password"
                    className="w-full rounded-xl border border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-page-bg,#f6f6f7)] px-3 py-2 text-xs text-[var(--theme-heading-color,#111827)] placeholder:text-[var(--theme-muted-color,#6b7280)] focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)] focus:bg-[var(--theme-card-bg,#ffffff)] transition"
                    placeholder="Minimum 8 caractères"
                  />
                </div>

                <div className="space-y-1.5">
                  <label
                    htmlFor="password_confirmation"
                    className="block text-xs font-medium text-[var(--theme-body-color,#374151)]"
                  >
                    Confirmer le mot de passe
                  </label>
                  <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    minLength={8}
                    autoComplete="new-password"
                    className="w-full rounded-xl border border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-page-bg,#f6f6f7)] px-3 py-2 text-xs text-[var(--theme-heading-color,#111827)] placeholder:text-[var(--theme-muted-color,#6b7280)] focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)] focus:bg-[var(--theme-card-bg,#ffffff)] transition"
                    placeholder="Confirmez votre mot de passe"
                  />
                </div>

                <div className="pt-1">
                  <p className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
                    Le mot de passe doit contenir au moins 8 caractères.
                  </p>
                </div>

                <Button
                  type="submit"
                  size="md"
                  variant="primary"
                  className="w-full">
                  Réinitialiser le mot de passe
                </Button>
              </form>

              <div className="pt-2 border-t border-[var(--theme-border-default,#e5e7eb)] flex flex-col gap-1">
                <p className="text-xxxs text-[var(--theme-muted-color,#6b7280)]">
                  Vous vous souvenez de votre mot de passe ?
                  <Link
                    href="/login"
                    className="ml-1 text-xxxs text-[var(--theme-heading-color,#111827)] font-medium hover:underline"
                  >
                    Se connecter
                  </Link>
                </p>
              </div>
            </div>

            <p className="mt-4 text-xxxs text-[var(--theme-muted-color,#6b7280)] text-center">
              Votre mot de passe sera modifié de manière sécurisée.
            </p>
          </div>
        </Container>
      </main>
    </div>
  );
}
