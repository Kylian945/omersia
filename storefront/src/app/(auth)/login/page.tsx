// app/(auth)/login/page.tsx
import { Container } from "@/components/common/Container";
import { HeaderAuth } from "@/components/common/HeaderAuth";
import { getShopInfo } from "@/lib/api-shop";
import { LoginForm } from "@/components/auth/LoginForm";
import { Suspense } from "react";

export default async function LoginPage() {
  const shopInfo = await getShopInfo();
  
  return (
    <div className="min-h-screen flex flex-col bg-neutral-50">
      <HeaderAuth shopInfo={shopInfo}/>

      <main className="flex-1 flex items-center">
        <Container>
          <div className="w-full max-w-md mx-auto">
            <div className="mb-6">
              <h1 className="text-2xl font-semibold tracking-tight text-neutral-900">
                Connexion
              </h1>
              <p className="mt-1 text-xs text-neutral-500">
                Accédez à votre espace client, suivez vos commandes et gérez vos
                informations.
              </p>
            </div>

            <Suspense fallback={<div>Chargement...</div>}>
              <LoginForm />
            </Suspense>

            <p className="mt-4 text-xxxs text-neutral-400 text-center">
              En vous connectant, vous acceptez nos conditions générales de vente
              et notre politique de confidentialité.
            </p>
          </div>
        </Container>
      </main>
    </div>
  );
}
