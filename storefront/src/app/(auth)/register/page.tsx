// app/(auth)/register/page.tsx
import Link from "next/link";
import { Container } from "@/components/common/Container";
import { HeaderAuth } from "@/components/common/HeaderAuth";
import { Button } from "@/components/common/Button";
import { getShopInfo } from "@/lib/api-shop";
import { safeDecodeURIComponent } from "@/lib/utils/error-utils";

type RegisterPageSearchParams = {
  error?: string;
};

type RegisterPageProps = {
  searchParams: Promise<RegisterPageSearchParams>;
};

export default async function RegisterPage({ searchParams }: RegisterPageProps) {
  const params = await searchParams;
  const error = params?.error ? safeDecodeURIComponent(params.error) : null;
  const shopInfo = await getShopInfo();

  return (
    <div className="min-h-screen flex flex-col bg-neutral-50">
      <HeaderAuth shopInfo={shopInfo}/>

      <main className="flex-1 flex items-center">
        <Container>
          <div className="w-full max-w-md mx-auto">
            <div className="mb-6">
              <h1 className="text-2xl font-semibold tracking-tight text-neutral-900">
                Créer un compte
              </h1>
              <p className="mt-1 text-xs text-neutral-500">
                Profitez d’un suivi simplifié de vos commandes et d’une
                expérience personnalisée.
              </p>
            </div>

            <div className="rounded-2xl bg-white border border-black/5 shadow-sm p-5 space-y-4">
              {error && (
                <div className="text-xs text-rose-600 bg-rose-50 border border-rose-100 px-3 py-2 rounded-xl">
                  {error}
                </div>
              )}

              <form
                method="POST"
                action="/auth/register"
                className="space-y-3"
              >
                <div className="grid grid-cols-2 gap-2">
                  <div className="space-y-1.5">
                    <label
                      htmlFor="firstname"
                      className="block text-xs font-medium text-neutral-800"
                    >
                      Prénom
                    </label>
                    <input
                      id="firstname"
                      name="firstname"
                      type="text"
                      autoComplete="given-name"
                      className="w-full rounded-xl border border-neutral-200 bg-neutral-50/60 px-3 py-2 text-xs text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-1 focus:ring-black/80 focus:bg-white transition"
                      placeholder="Votre prénom"
                    />
                  </div>
                  <div className="space-y-1.5">
                    <label
                      htmlFor="lastname"
                      className="block text-xs font-medium text-neutral-800"
                    >
                      Nom
                    </label>
                    <input
                      id="lastname"
                      name="lastname"
                      type="text"
                      autoComplete="family-name"
                      className="w-full rounded-xl border border-neutral-200 bg-neutral-50/60 px-3 py-2 text-xs text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-1 focus:ring-black/80 focus:bg-white transition"
                      placeholder="Votre nom"
                    />
                  </div>
                </div>

                <div className="space-y-1.5">
                  <label
                    htmlFor="email"
                    className="block text-xs font-medium text-neutral-800"
                  >
                    Adresse e-mail
                  </label>
                  <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autoComplete="email"
                    className="w-full rounded-xl border border-neutral-200 bg-neutral-50/60 px-3 py-2 text-xs text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-1 focus:ring-black/80 focus:bg-white transition"
                    placeholder="vous@example.com"
                  />
                </div>

                <div className="space-y-1.5">
                  <label
                    htmlFor="password"
                    className="block text-xs font-medium text-neutral-800"
                  >
                    Mot de passe
                  </label>
                  <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autoComplete="new-password"
                    className="w-full rounded-xl border border-neutral-200 bg-neutral-50/60 px-3 py-2 text-xs text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-1 focus:ring-black/80 focus:bg-white transition"
                    placeholder="••••••••"
                  />
                </div>

                <div className="space-y-1.5">
                  <label
                    htmlFor="password_confirmation"
                    className="block text-xs font-medium text-neutral-800"
                  >
                    Confirmer le mot de passe
                  </label>
                  <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autoComplete="new-password"
                    className="w-full rounded-xl border border-neutral-200 bg-neutral-50/60 px-3 py-2 text-xs text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-1 focus:ring-black/80 focus:bg-white transition"
                    placeholder="••••••••"
                  />
                </div>

                <div className="space-y-2 pt-1">
                  <label className="inline-flex items-center gap-1.5 cursor-pointer">
                    <input
                      type="checkbox"
                      name="newsletter"
                      defaultChecked
                      className="h-3 w-3 rounded border-neutral-300 text-black focus:ring-black/70"
                    />
                    <span className="text-xxxs text-neutral-600">
                      Je souhaite recevoir les offres et nouveautés par e-mail.
                    </span>
                  </label>

                  <label className="inline-flex items-start gap-1.5 cursor-pointer">
                    <input
                      type="checkbox"
                      name="accept_terms"
                      className="mt-0.5 h-3 w-3 rounded border-neutral-300 text-black focus:ring-black/70"
                      required
                    />
                    <span className="text-xxxs text-neutral-600 leading-snug">
                      J’ai lu et j’accepte les{" "}
                      <Link
                        href="/cgv"
                        className="underline hover:text-neutral-900"
                      >
                        conditions générales de vente
                      </Link>{" "}
                      et la{" "}
                      <Link
                        href="/privacy"
                        className="underline hover:text-neutral-900"
                      >
                        politique de confidentialité
                      </Link>
                      .
                    </span>
                  </label>
                </div>
                <Button
                  type="submit"
                  size="md"
                  variant="primary"
                  className="w-full">
                  Créer mon compte
                </Button>
              </form>

              <div className="pt-2 border-t border-neutral-100 flex flex-col gap-1">
                <p className="text-xxxs text-neutral-500">
                  Vous avez déjà un compte ?
                  <Link
                    href="/login"
                    className="ml-1 text-xxxs text-neutral-900 font-medium hover:underline"
                  >
                    Se connecter
                  </Link>
                </p>
              </div>
            </div>

            <p className="mt-4 text-xxxs text-neutral-400 text-center">
              Vos données sont utilisées uniquement pour la gestion de votre
              compte et de vos commandes.
            </p>
          </div>
        </Container>
      </main>
    </div>
  );
}
