import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import Link from "next/link";
import Image from "next/image";
import { HeaderCheckout } from "@/components/common/HeaderCheckout";
import { Footer } from "@/components/common/Footer";
import { Container } from "@/components/common/Container";
import { fetchUserSSR } from "@/lib/auth/fetchUserSSR";
import { confirmDraftOrderByNumber, getShopInfo } from "@/lib/api";
import { buildImageUrl } from "@/lib/image-utils";
import { OrderSuccessStatusWatcher } from "@/components/checkout/OrderSuccessStatusWatcher";

type Props = {
  params: Promise<{ number: string }>;
  searchParams: Promise<{ payment_intent?: string | string[] }>;
};

function resolveItemImage(path?: string | null): string {
  if (!path) return "/images/product-placeholder.jpg";

  return buildImageUrl({ path }) || "/images/product-placeholder.jpg";
}

export default async function OrderSuccessPage({
  params,
  searchParams,
}: Props) {
  const user = await fetchUserSSR();
  const shopInfo = await getShopInfo();
  if (!user) redirect("/login");

  const cookieStore = await cookies();
  const token = cookieStore.get("auth_token")?.value;

  const number = (await params).number;
  const resolvedSearchParams = await searchParams;
  const paymentIntentParam = resolvedSearchParams.payment_intent;
  const paymentIntentId = Array.isArray(paymentIntentParam)
    ? paymentIntentParam[0]
    : paymentIntentParam;

  const order = token
    ? await confirmDraftOrderByNumber(number, token, paymentIntentId)
    : null;

  if (!order) {
    // ordre introuvable → on renvoie vers /account par ex.
    redirect("/account");
  }

  const isConfirmed =
    order.status === "confirmed" && order.payment_status === "paid";

  return (
    <>
      <HeaderCheckout shopInfo={shopInfo}/>
      <main className="flex-1 py-12 bg-neutral-50">
        <Container>
          <div className="max-w-4xl mx-auto">
            {/* Success message */}
            <div
              className={`rounded-2xl border p-6 mb-6 ${
                isConfirmed
                  ? "bg-gradient-to-br from-green-50 to-emerald-50 border-green-200"
                  : "bg-gradient-to-br from-amber-50 to-orange-50 border-amber-200"
              }`}
            >
              <div className="flex items-start gap-4">
                <div className="shrink-0">
                  <div
                    className={`w-12 h-12 rounded-full flex items-center justify-center ${
                      isConfirmed ? "bg-green-500" : "bg-amber-500"
                    }`}
                  >
                    <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
                <div className="flex-1">
                  <h1
                    className={`text-xl font-semibold tracking-tight ${
                      isConfirmed ? "text-green-900" : "text-amber-900"
                    }`}
                  >
                    {isConfirmed ? "Commande validée !" : "Paiement reçu, validation en cours"}
                  </h1>
                  <p
                    className={`mt-1 text-sm ${
                      isConfirmed ? "text-green-700" : "text-amber-700"
                    }`}
                  >
                    {isConfirmed
                      ? "Merci pour votre achat. Un email de confirmation a été envoyé à"
                      : "Votre paiement est bien enregistré. La commande sera visible dès validation définitive."}{" "}
                    {isConfirmed && (
                      <span className="font-medium">{order.customer_email}</span>
                    )}
                  </p>
                  <p
                    className={`mt-2 text-xs ${
                      isConfirmed ? "text-green-600" : "text-amber-600"
                    }`}
                  >
                    Numéro de commande : <span className="font-mono font-semibold">{order.number}</span>
                  </p>
                  <OrderSuccessStatusWatcher
                    customerId={user.id}
                    orderNumber={order.number}
                    initialStatus={order.status}
                    initialPaymentStatus={order.payment_status}
                    paymentIntentId={paymentIntentId}
                  />
                </div>
              </div>
            </div>

            {/* Next steps */}
            <div className="rounded-2xl bg-white border border-black/5 shadow-sm p-5 mb-6">
              <h2 className="text-sm font-semibold text-neutral-900 mb-3">
                Prochaines étapes
              </h2>
              <div className="space-y-3">
                <div className="flex items-start gap-3">
                  <div className="shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-semibold">
                    1
                  </div>
                  <div className="text-xs text-neutral-600">
                    <p className="font-medium text-neutral-900">Email de confirmation</p>
                    <p>Vous allez recevoir un email récapitulatif de votre commande</p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <div className="shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-semibold">
                    2
                  </div>
                  <div className="text-xs text-neutral-600">
                    <p className="font-medium text-neutral-900">Préparation de votre commande</p>
                    <p>Nous préparons votre colis avec soin</p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <div className="shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-semibold">
                    3
                  </div>
                  <div className="text-xs text-neutral-600">
                    <p className="font-medium text-neutral-900">Suivi de livraison</p>
                    <p>Vous recevrez un email avec le numéro de suivi dès l&apos;expédition</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="max-w-4xl mx-auto">
            <h2 className="text-lg font-semibold tracking-tight text-neutral-900 mb-4">
              Récapitulatif de votre commande
            </h2>

          <div className="mt-6 grid grid-cols-1 lg:grid-cols-[minmax(0,2fr)_280px] gap-6 items-start">
            {/* Détails commande */}
            <section className="rounded-2xl bg-white border border-black/5 shadow-sm p-5 space-y-4">
              <div className="flex items-center justify-between text-xs">
                <div>
                  <p className="font-semibold text-neutral-900">
                    Commande n° {order.number}
                  </p>
                  <p className="text-neutral-500">
                    Passée le{" "}
                    {new Date(order.placed_at).toLocaleDateString("fr-FR")}
                  </p>
                </div>
                {order.shipping_method && (
                  <div className="text-right text-xxs text-neutral-600">
                    <div>{order.shipping_method.name}</div>
                    {order.shipping_method.delivery_time && (
                      <div>{order.shipping_method.delivery_time}</div>
                    )}
                  </div>
                )}
              </div>

              <div>
                <h2 className="text-xs font-semibold text-neutral-900 mb-2">
                  Articles
                </h2>
                <div className="space-y-2 text-xs">
                  {order.items.map((item) => (
                    <div
                      key={item.id}
                      className="flex items-center justify-between border-b border-neutral-100 pb-2"
                    >
                      <div>
                        <div className="flex items-center gap-1.5">
                          <div className="relative w-10 h-10 rounded-md overflow-hidden">
                            <Image
                              src={resolveItemImage(item.image_url)}
                              alt={item.name}
                              fill
                              sizes="40px"
                              className="object-cover"
                            />
                          </div>
                          <div>
                            <div className="font-medium text-neutral-900">
                              {item.name}
                            </div>
                            <div className="text-xxs text-neutral-500">
                              Qté {item.quantity}
                            </div>
                          </div>
                        </div>

                      </div>
                      <div className="text-xs font-semibold text-neutral-900">
                        {Number(item.total_price).toFixed(2)} €
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                  <h3 className="font-semibold text-neutral-900 mb-1">
                    Adresse de livraison
                  </h3>
                  <div className="text-neutral-600">
                    {order.customer_firstname} {order.customer_lastname}
                    <br />
                    {order.shipping_address.line1}
                    {order.shipping_address.line2 && (
                      <>
                        <br />
                        {order.shipping_address.line2}
                      </>
                    )}
                    <br />
                    {order.shipping_address.postcode}{" "}
                    {order.shipping_address.city}
                    <br />
                    {order.shipping_address.country}
                  </div>
                </div>
                <div>
                  <h3 className="font-semibold text-neutral-900 mb-1">
                    Adresse de facturation
                  </h3>
                  <div className="text-neutral-600">
                    {order.billing_address.line1}
                    {order.billing_address.line2 && (
                      <>
                        <br />
                        {order.billing_address.line2}
                      </>
                    )}
                    <br />
                    {order.billing_address.postcode}{" "}
                    {order.billing_address.city}
                    <br />
                    {order.billing_address.country}
                  </div>
                </div>
              </div>
            </section>

            {/* Récap montant */}
            <aside className="rounded-2xl bg-white border border-black/5 shadow-sm p-5 text-xs space-y-2">
              <div className="flex justify-between">
                <span className="text-neutral-600">Sous-total</span>
                <span className="font-medium">
                  {Number(order.subtotal).toFixed(2)} €
                </span>
              </div>
              {order.discount_total > 0 && (
                <div className="flex justify-between text-emerald-600">
                  <span>Remises</span>
                  <span>- {Number(order.discount_total).toFixed(2)} €</span>
                </div>
              )}
              <div className="flex justify-between">
                <span className="text-neutral-600">Livraison</span>
                <span className="font-medium">
                  {order.shipping_total === 0
                    ? "Gratuite"
                    : `${Number(order.shipping_total).toFixed(2)} €`}
                </span>
              </div>
              {order.tax_total > 0 && (
                <div className="flex justify-between">
                  <span className="text-neutral-600">Taxes</span>
                  <span className="font-medium">
                    {Number(order.tax_total).toFixed(2)} €
                  </span>
                </div>
              )}
              <div className="pt-2 mt-2 border-t border-neutral-200 flex justify-between">
                <span className="font-semibold text-neutral-900">
                  Total TTC
                </span>
                <span className="font-semibold text-neutral-900">
                  {Number(order.total).toFixed(2)} €
                </span>
              </div>
            </aside>
          </div>

          {/* CTA Actions */}
          <div className="max-w-4xl mx-auto mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a
              href="/account"
              className="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-neutral-900 text-white text-sm font-medium hover:bg-black transition"
            >
              Voir mes commandes
            </a>
            <Link
              href="/"
              className="inline-flex items-center justify-center px-6 py-3 rounded-lg border border-neutral-200 bg-white text-neutral-900 text-sm font-medium hover:bg-neutral-50 transition"
            >
              Continuer mes achats
            </Link>
          </div>
          </div>
        </Container>
      </main>
      <Footer />
    </>
  );
}
