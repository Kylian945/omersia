// app/(auth)/login/page.tsx
import { Container } from "@/components/common/Container";
import { HeaderAuth } from "@/components/common/HeaderAuth";
import { getShopInfo } from "@/lib/api-shop";
import { LoginForm } from "@/components/auth/LoginForm";
import { Suspense } from "react";

export default async function LoginPage() {
  const shopInfo = await getShopInfo();
  
  return (
    <div className="min-h-screen flex flex-col bg-[var(--theme-page-bg,#f6f6f7)]">
      <HeaderAuth shopInfo={shopInfo}/>

      <main className="flex-1 flex items-center">
        <Container>
          <div className="w-full max-w-md mx-auto">
            <div className="mb-6">
              <h1 className="text-2xl font-semibold tracking-tight text-[var(--theme-heading-color,#111827)]">
                Connexion
              </h1>
              <p className="mt-1 text-xs text-[var(--theme-muted-color,#6b7280)]">
                Accédez à votre espace client, suivez vos commandes et gérez vos
                informations.
              </p>
            </div>

            <Suspense fallback={<div>Chargement...</div>}>
              <LoginForm />
            </Suspense>

            <p className="mt-4 text-xxxs text-[var(--theme-muted-color,#6b7280)] text-center">
              En vous connectant, vous acceptez nos conditions générales de vente
              et notre politique de confidentialité.
            </p>
          </div>
        </Container>
      </main>
    </div>
  );
}
