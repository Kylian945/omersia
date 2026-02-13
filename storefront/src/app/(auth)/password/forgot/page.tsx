import Link from "next/link";
import { Container } from "@/components/common/Container";
import { HeaderAuth } from "@/components/common/HeaderAuth";
import { Button } from "@/components/common/Button";
import { getShopInfo } from "@/lib/api-shop";
import { safeDecodeURIComponent } from "@/lib/utils/error-utils";

type ForgotPasswordPageSearchParams = {
  success?: string;
  error?: string;
};

type ForgotPasswordPageProps = {
  searchParams: Promise<ForgotPasswordPageSearchParams>;
};

export default async function ForgotPasswordPage({
  searchParams
}: ForgotPasswordPageProps) {
  const params = await searchParams;
  const success = params?.success ? safeDecodeURIComponent(params.success) : null;
  const error = params?.error ? safeDecodeURIComponent(params.error) : null;
  const shopInfo = await getShopInfo();

  return (
    <div className="min-h-screen flex flex-col bg-[var(--theme-page-bg,#f6f6f7)]">
      <HeaderAuth shopInfo={shopInfo}/>

      <main className="flex-1 flex items-center">
        <Container>
          <div className="w-full max-w-md mx-auto">
            <div className="mb-6">
              <h1 className="text-2xl font-semibold tracking-tight text-[var(--theme-heading-color,#111827)]">
                Mot de passe oublié
              </h1>
              <p className="mt-1 text-xs text-[var(--theme-muted-color,#6b7280)]">
                Saisissez votre adresse e-mail et nous vous enverrons un lien pour réinitialiser votre mot de passe.
              </p>
            </div>

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

              <form
                method="POST"
                action="/auth/password/forgot"
                className="space-y-3"
              >
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

                <Button
                  type="submit"
                  size="md"
                  variant="primary"
                  className="w-full">
                  Envoyer le lien de réinitialisation
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
              Si l&apos;adresse e-mail est enregistrée, vous recevrez un lien de réinitialisation dans quelques instants.
            </p>
          </div>
        </Container>
      </main>
    </div>
  );
}
