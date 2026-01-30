// components/account/OrderDetailClient.tsx
"use client";

import { useState } from "react";
import Image from "next/image";
import { BadgeCheck, Truck, MapPin, CheckCircle2 } from "lucide-react";
import type { OrderApi } from "@/lib/api";
import { Button } from "@/components/common/Button";

const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL || "";

type AuthUser = {
  id: number;
  firstname?: string | null;
  lastname?: string | null;
  email: string;
};

type PropsOrder = {
  order: OrderApi;
  user: AuthUser;
};

function badgeClass(status: string) {
  switch (status) {
    case "confirmed":
      return "bg-blue-100 text-blue-700";
    case "processing":
      return "bg-sky-100 text-sky-700";
    case "in_transit":
      return "bg-cyan-100 text-cyan-700";
    case "out_for_delivery":
      return "bg-teal-100 text-teal-700";
    case "delivered":
      return "bg-lime-100 text-lime-700";
    case "cancelled":
      return "bg-gray-100 text-gray-700";
    case "refunded":
      return "bg-gray-100 text-gray-700";
    default:
      return "bg-neutral-100 text-neutral-700";
  }
}

function humanStatus(status: string): string {
  switch (status) {
    case "confirmed":
      return "Confirmée";
    case "processing":
      return "En préparation";
    case "in_transit":
      return "En transit";
    case "out_for_delivery":
      return "En cours de livraison";
    case "delivered":
      return "Livrée";
    case "refunded":
      return "Remboursée";
    case "cancelled":
      return "Annulée";
    default:
      return status;
  }
}

function fmtEUR(n: number) {
  return Number(n).toFixed(2) + " €";
}

export function OrderDetailClient({ order, user }: PropsOrder) {
  const [isDownloading, setIsDownloading] = useState(false);

  const handleDownloadInvoice = async () => {
    setIsDownloading(true);
    try {
      const response = await fetch(`/api/orders/${order.number}/invoice`);

      if (!response.ok) {
        console.error("Failed to download invoice:", response.status);
        alert("Erreur lors du téléchargement de la facture");
        return;
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `facture-${order.number}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error("Error downloading invoice:", error);
      alert("Erreur lors du téléchargement de la facture");
    } finally {
      setIsDownloading(false);
    }
  };

  // On n'a pas encore de vraie timeline en BDD → on simule une timeline
  // simple en fonction du status actuel.
  const STEP_META = [
    { label: "Confirmée", key: "confirmed", Icon: BadgeCheck },
    { label: "En transit", key: "in_transit", Icon: Truck },
    { label: "En cours de livraison", key: "out_for_delivery", Icon: MapPin },
    { label: "Livrée", key: "delivered", Icon: CheckCircle2 },
  ] as const;

  const statusOrder: Record<string, number> = {
    confirmed: 0,
    processing: 0,
    in_transit: 1,
    out_for_delivery: 2,
    delivered: 3,
  };

  const currentStepIndex =
    statusOrder[order.status] !== undefined ? statusOrder[order.status] : 0;

  const activeSteps = STEP_META.slice(0, currentStepIndex + 1);

  return (
    <div className="w-full space-y-4">
      {/* Top bar */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-neutral-900">
            Commande #{order.number}
          </h1>
          <p className="mt-1 text-xs text-neutral-500">
            Passée le {new Date(order.placed_at).toLocaleDateString("fr-FR")}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            href="/account"
            variant="secondary"
            size="sm"
          >
            Retour au compte
          </Button>
          <Button
            variant="primary"
            size="sm"
            onClick={handleDownloadInvoice}
            disabled={isDownloading}
          >
            {isDownloading ? "Téléchargement..." : "Télécharger la facture (PDF)"}
          </Button>
        </div>
      </div>

      {/* Order Status + timeline */}
      <div className="rounded-2xl bg-white border border-black/5 shadow-sm p-5">
        <div className="flex items-center justify-between text-xs">
          <div>
            <p className="text-black font-semibold">Suivi de commande</p>
            {order.shipping_method && (
              <p className="text-neutral-500">
                {order.shipping_method.name}
                {order.shipping_method.delivery_time &&
                  ` · ${order.shipping_method.delivery_time}`}
              </p>
            )}
          </div>
        </div>

        {/* Tracking card - style Shopify */}
        {order.meta?.tracking && (
          order.meta.tracking.number ||
          order.meta.tracking.carrier ||
          order.meta.tracking.url
        ) && (
            <div className="mt-3">
              <div className="flex items-start gap-3">
                
                <div className="flex-1 min-w-0">
                  
                  <div className="space-y-1 text-xs">
                   
                    {order.meta.tracking.number && (
                      <div className="flex items-center gap-1.5 text-neutral-600">
                        <span className="text-neutral-500">N° de suivi :</span>
                        {order.meta.tracking.url ? (
                          <a
                            href={order.meta.tracking.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-xs font-medium text-neutral-900 hover:text-neutral-700 underline"
                          >
                            <span className="font-mono text-neutral-900 select-all">
                              {order.meta.tracking.number}
                            </span>
                          </a>
                        ) : (
                          <span className="font-mono text-neutral-900 select-all">
                            {order.meta.tracking.number}
                          </span>
                        )}
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          )}

        <div className="relative mt-5">
          <ul className="relative flex flex-col-reverse gap-6">
            {activeSteps.map((s, idx) => {
              const Icon = s.Icon;
              return (
                <li key={s.key} className="relative flex items-start gap-3">
                  {/* Segment au-dessus (sauf le dernier visuel) */}
                  {idx !== activeSteps.length - 1 && (
                    <div className="absolute -left-1 -top-3 w-6 border-t border-dashed rotate-90" />
                  )}

                  <div className="relative">
                    {s.key === "confirmed" ? (
                      <div className="w-2 h-2 mt-1 ml-1 rounded-full bg-black text-white flex items-center justify-center shadow-sm" />
                    ) : (
                      <Icon className="w-4 h-4 mt-0.5" />
                    )}
                  </div>

                  <div className="flex flex-col">
                    <span className="text-xs font-semibold text-black">
                      {s.label}
                    </span>
                    {/* Pour l'instant, pas de date détaillée */}
                  </div>
                </li>
              );
            })}
          </ul>
        </div>
      </div>

      {/* Summary card */}
      <div className="rounded-2xl bg-white border border-black/5 shadow-sm p-5">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div className="text-xs">
            <div className="text-black font-semibold">Statut</div>
            <div
              className={`inline-flex px-2 py-0.5 rounded-full mt-1 text-xs font-medium ${badgeClass(
                order.status
              )}`}
            >
              {humanStatus(order.status)}
            </div>
          </div>
          <div className="text-xs">
            <div className="text-black font-semibold">Paiement</div>
            <div className="font-medium text-neutral-900">
              {/* Pour l'instant on n'a pas le détail du moyen de paiement → on se base sur payment_status */}
              {order.payment_status === "paid"
                ? "Payé"
                : order.payment_status === "pending"
                  ? "En attente"
                  : order.payment_status === "refunded"
                    ? "Remboursé"
                    : order.payment_status === "partially_refunded"
                      ? "Partiellement remboursé"
                      : "Non payé"}
            </div>
          </div>
          <div className="text-xs">
            <div className="text-black font-semibold">Livraison</div>
            <div className="font-medium text-neutral-900">
              {order.shipping_method?.name ?? "—"}
            </div>
          </div>
          {order.discount_total > 0 && (
            <div className="text-xs">
              <div className="text-black font-semibold">Réduction</div>
              <div className="font-medium text-green-600">
                -{fmtEUR(order.discount_total)}
              </div>
            </div>
          )}
          <div className="text-xs">
            <div className="text-black font-semibold">Total</div>
            <div className="font-semibold text-neutral-900">
              {fmtEUR(order.total)}
            </div>
          </div>
        </div>
      </div>

      {/* Main grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {/* Items */}
        <div className="lg:col-span-2 rounded-2xl bg-white border border-black/5 shadow-sm p-5">
          <p className="text-xs text-black font-semibold mb-3">Articles</p>
          <div className="divide-y divide-neutral-100">
            {order.items.map((item) => (
              <div
                key={item.id}
                className="py-3 flex items-center justify-between text-xs"
              >
                <div className="flex items-center gap-3">
                  {item.image_url ? (
                    <div className="relative w-12 h-12 overflow-hidden rounded">
                      <Image
                        src={`${BACKEND_URL}/storage/${item.image_url.replace(/^\/+/, "")}`}
                        alt={item.name}
                        fill
                        sizes="48px"
                        className="object-cover"
                      />
                    </div>
                  ) : (
                    <span className="text-xxxs text-neutral-400">Image</span>
                  )}
                  <div className="flex flex-col">
                    <span className="font-medium text-neutral-900">
                      {item.name}
                    </span>
                    <span className="text-neutral-500">Qté {item.quantity}</span>
                  </div>
                </div>
                <div className="text-neutral-900 font-medium">
                  {fmtEUR(item.total_price)}
                </div>
              </div>
            ))}
          </div>

          {/* Totaux */}
          <div className="mt-4 border-t border-neutral-100 pt-4 text-xs">
            <div className="flex justify-between">
              <span className="text-neutral-500">Sous-total</span>
              <span className="font-medium text-neutral-900">
                {fmtEUR(order.subtotal)}
              </span>
            </div>

            {order.discount_total > 0 && (
              <div className="flex justify-between">
                <span className="text-neutral-500">Remise</span>
                <span className="font-medium text-neutral-900">
                  -{fmtEUR(order.discount_total)}
                </span>
              </div>
            )}
            <div className="flex justify-between">
              <span className="text-neutral-500">Livraison</span>
              <span className="font-medium text-neutral-900">
                {order.shipping_total === 0
                  ? "Gratuite"
                  : fmtEUR(order.shipping_total)}
              </span>
            </div>
            {order.tax_total > 0 && (
              <div className="flex justify-between">
                <span className="text-neutral-500">Taxes</span>
                <span className="font-medium text-neutral-900">
                  {fmtEUR(order.tax_total)}
                </span>
              </div>
            )}
            <div className="flex justify-between mt-2">
              <span className="font-semibold text-neutral-900">Total</span>
              <span className="font-semibold text-neutral-900">
                {fmtEUR(order.total)}
              </span>
            </div>
            <div className="text-xs text-neutral-500 mt-1">
              TVA incluse si applicable.
            </div>
          </div>
        </div>

        {/* Addresses + identité */}
        <div className="space-y-4">
          <div className="rounded-2xl bg-white border border-black/5 shadow-sm p-5">
            <p className="text-xs text-black font-semibold mb-2">Identité</p>
            <div className="text-xs text-neutral-900 space-y-0.5">
              <div className="font-medium">
                {(user.firstname || "") + " " + (user.lastname || "")}
              </div>
              <div className="font-medium">{user.email}</div>
            </div>
          </div>

          <div className="rounded-2xl bg-white border border-black/5 shadow-sm p-5">
            <p className="text-xs text-black font-semibold mb-2">
              Adresse de livraison
            </p>
            <div className="text-xs text-neutral-900 space-y-0.5">
              <div>
                {order.customer_firstname} {order.customer_lastname}
              </div>
              <div>{order.shipping_address.line1}</div>
              {order.shipping_address.line2 && (
                <div>{order.shipping_address.line2}</div>
              )}
              <div>
                {order.shipping_address.postcode} {order.shipping_address.city}
              </div>
              <div>{order.shipping_address.country.toUpperCase()}</div>
            </div>
          </div>

          <div className="rounded-2xl bg-white border border-black/5 shadow-sm p-5">
            <p className="text-xs text-black font-semibold mb-2">
              Adresse de facturation
            </p>
            <div className="text-xs text-neutral-900 space-y-0.5">
              <div>{order.billing_address.line1}</div>
              {order.billing_address.line2 && (
                <div>{order.billing_address.line2}</div>
              )}
              <div>
                {order.billing_address.postcode} {order.billing_address.city}
              </div>
              <div>{order.billing_address.country.toUpperCase()}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
