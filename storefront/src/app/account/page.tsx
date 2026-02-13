import { redirect } from "next/navigation";
import { Container } from "@/components/common/Container";
import { Header } from "@/components/common/Header";
import { Footer } from "@/components/common/Footer";
import Link from "next/link";
import { fetchUserSSR } from "@/lib/auth/fetchUserSSR";
import { getAddresses } from "@/lib/api-addresses";
import { getOrders } from "@/lib/api-orders";
import type { Address } from "@/lib/types/addresses-types";
import type { OrderSummary } from "@/lib/types/order-types";
import { AccountAddresses } from "@/components/account/AccountAddresses";
import { AccountProfile } from "@/components/account/AccountProfile";
import { cookies } from "next/headers";
import { LogoutButton } from "@/components/auth/LogoutButton";
import { AccountOrdersRealtime } from "@/components/account/AccountOrdersRealtime";

export default async function AccountPage() {
  const token = (await cookies()).get("auth_token")?.value;
  const user = await fetchUserSSR();
  if (!user) redirect("/login");

  const addresses: Address[] = (await getAddresses(token)) ?? [];
  const orders: OrderSummary[] = (await getOrders(token)) ?? [];

  return (
    <>
      <Header />
      <main className="flex-1 pt-10 pb-6 bg-[var(--theme-page-bg,#f6f6f7)] flex">
        <Container>
          <div className="w-full flex flex-col justify-between h-full">
            <div>
              <div className="flex flex-wrap justify-between w-full">
                <div className="flex flex-col">
                  <h1 className="text-2xl font-semibold tracking-tight text-[var(--theme-heading-color,#111827)]">
                    Mon compte
                  </h1>
                  <p className="mt-1 text-xs text-[var(--theme-muted-color,#6b7280)]">
                    Gérez vos informations personnelles et consultez vos commandes.
                  </p>
                </div>
                <div className="logout pt-3">
                  <LogoutButton className="inline-flex items-center justify-center px-4 py-2 text-xs font-medium text-[var(--theme-button-primary-text,#ffffff)] bg-[var(--theme-primary,#111827)] rounded-lg hover:opacity-90 transition">
                    Se déconnecter
                  </LogoutButton>
                </div>
              </div>

              {/* Profil */}
              <div className="profile">
                <p className="text-xs text-[var(--theme-heading-color,#111827)] font-semibold mt-6">Profil</p>
                <div className="theme-panel theme-account-card flex flex-wrap gap-3 mt-2 rounded-2xl bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] shadow-sm p-5">
                  <div className="self-start flex-1 flex flex-col">
                    <p className="text-xs text-[var(--theme-heading-color,#111827)] font-semibold mb-2">Identité</p>
                    <AccountProfile
                      initialUser={{
                        firstname: user.firstname,
                        lastname: user.lastname,
                        email: user.email,
                        phone: user.phone ?? null,
                      }}
                    />
                  </div>
                  <AccountAddresses initialAddresses={addresses} />

                </div>
              </div>

              {/* Commandes */}
              <div className="orders">
                <p className="text-xs text-[var(--theme-heading-color,#111827)] mt-6 font-semibold">Commandes</p>
                <div className="theme-panel theme-account-card mt-2 rounded-2xl bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] shadow-sm p-5 space-y-3">
                  <AccountOrdersRealtime customerId={user.id} initialOrders={orders} />
                </div>
              </div>
            </div>
            <Link href="/account/privacy" className="text-xs hover:underline mt-4 block bottom-0 text-[var(--theme-muted-color,#6b7280)]">
              Mes données personnelles
            </Link>
          </div>
        </Container>
      </main>
      <Footer />
    </>
  );
}
