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
      <main className="flex-1 pt-10 pb-6 bg-neutral-50 flex">
        <Container>
          <div className="w-full flex flex-col justify-between h-full">
            <div>
              <div className="flex flex-wrap justify-between w-full">
                <div className="flex flex-col">
                  <h1 className="text-2xl font-semibold tracking-tight text-neutral-900">
                    Mon compte
                  </h1>
                  <p className="mt-1 text-xs text-neutral-500">
                    Gérez vos informations personnelles et consultez vos commandes.
                  </p>
                </div>
                <div className="logout pt-3">
                  <LogoutButton className="inline-flex items-center justify-center px-4 py-2 text-xs font-medium text-white bg-black rounded-lg hover:bg-black/90 transition">
                    Se déconnecter
                  </LogoutButton>
                </div>
              </div>

              {/* Profil */}
              <div className="profile">
                <p className="text-xs text-black font-semibold mt-6">Profil</p>
                <div className="flex flex-wrap gap-3 mt-2 rounded-2xl bg-white border border-black/5 shadow-sm p-5">
                  <div className="self-start flex-1 flex flex-col">
                    <p className="text-xs text-black font-semibold mb-2">Identité</p>
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
                <p className="text-xs text-black mt-6 font-semibold">Commandes</p>
                <div className="mt-2 rounded-2xl bg-white border border-black/5 shadow-sm p-5 space-y-3">
                  <AccountOrdersRealtime customerId={user.id} initialOrders={orders} />
                </div>
              </div>
            </div>
            <Link href="/account/privacy" className="text-xs hover:underline mt-4 block bottom-0 text-neutral-500">
              Mes données personnelles
            </Link>
          </div>
        </Container>
      </main>
      <Footer />
    </>
  );
}
