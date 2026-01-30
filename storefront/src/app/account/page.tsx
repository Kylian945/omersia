import { redirect } from "next/navigation";
import { Container } from "@/components/common/Container";
import { Header } from "@/components/common/Header";
import { Footer } from "@/components/common/Footer";
import Link from "next/link";
import { fetchUserSSR } from "@/lib/auth/fetchUserSSR";
import { getAddresses, type Address, getOrders, type OrderSummary } from "@/lib/api";
import { AccountAddresses } from "@/components/account/AccountAddresses";
import { AccountProfile } from "@/components/account/AccountProfile";
import { cookies } from "next/headers";
import { LogoutButton } from "@/components/auth/LogoutButton";


function formatOrderStatus(status: string): { label: string; badgeClass: string } {
  switch (status) {
    case "confirmed":
      return { label: "Confirmée", badgeClass: "bg-blue-100 text-blue-700" };
    case "processing":
      return { label: "En préparation", badgeClass: "bg-sky-100 text-sky-700" };
    case "in_transit":
      return { label: "En transit", badgeClass: "bg-cyan-100 text-cyan-700" };
    case "out_for_delivery":
      return { label: "En cours de livraison", badgeClass: "bg-teal-100 text-teal-700" };
    case "delivered":
      return { label: "Livrée", badgeClass: "bg-lime-100 text-lime-700" };
    case "refunded":
      return { label: "Remboursée", badgeClass: "bg-gray-100 text-gray-700" };
    case "cancelled":
      return { label: "Annulée", badgeClass: "bg-gray-100 text-gray-700" };
    default:
      return { label: status, badgeClass: "bg-neutral-100 text-neutral-700" };
  }
}

export default async function AccountPage() {
  const token = (await cookies()).get("auth_token")?.value;
  const user = await fetchUserSSR();
  if (!user) redirect("/login");

  const addresses: Address[] = (await getAddresses(token)) ?? [];
  const orders: OrderSummary[] = (await getOrders(token)) ?? [];

  return (
    <>
      <Header />
      <main className="flex-1 py-10 bg-neutral-50">
        <Container>
          <div className="w-full">
            <div className="flex justify-between w-full">
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
              <div className="flex gap-3 mt-2 rounded-2xl bg-white border border-black/5 shadow-sm p-5">
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
                {orders.length === 0 ? (
                  <p className="text-xs text-neutral-500">
                    Vous n’avez pas encore passé de commande.
                  </p>
                ) : (
                  <div className="space-y-3">
                    {orders.map((order) => {
                      const { label, badgeClass } = formatOrderStatus(order.status);
                      return (
                        <Link
                          href={`/account/order/${order.number}`}
                          key={order.id}
                          className="border border-black/5 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between bg-white shadow-sm"
                        >
                          <div className="flex flex-col text-xs">
                            <span className="font-semibold text-neutral-900">
                              Commande #{order.number}
                            </span>
                            <span className="text-neutral-500 text-xs">
                              Passée le{" "}
                              {new Date(order.placed_at).toLocaleDateString("fr-FR")}
                            </span>
                          </div>

                          <div className="flex-1 sm:px-6 mt-2 sm:mt-0 text-xs flex gap-6 items-center">
                            {/* items_count vient de withCount */}
                            {order.items_count} article
                            {order.items_count > 1 && "s"}
                            <span
                              className={`px-2 py-0.5 rounded-full mt-1 text-xs font-medium ${badgeClass}`}
                            >
                              {label}
                            </span>
                          </div>

                          <div className="flex flex-col items-end text-xs sm:w-32 mt-3 sm:mt-0">
                            <span className="font-semibold">Total</span>
                            <span className="text-neutral-900">
                              {Number(order.total).toFixed(2)} €
                            </span>
                          </div>
                        </Link>
                      );
                    })}
                  </div>
                )}
              </div>
            </div>
          </div>
        </Container>
      </main>
      <Footer />
    </>
  );
}
