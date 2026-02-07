"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import type { OrderSummary } from "@/lib/types/order-types";
import { logger } from "@/lib/logger";
import { subscribeToPrivateRealtimeEvent } from "@/lib/realtime/reverb-client";

type Props = {
  customerId: number;
  initialOrders: OrderSummary[];
};

type OrderUpdatedPayload = {
  order?: {
    id?: number;
    number?: string | null;
    status?: string | null;
    total?: number;
    placed_at?: string | null;
    items_count?: number;
  };
};

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

function sortByLatest(orders: OrderSummary[]): OrderSummary[] {
  return [...orders].sort((a, b) => {
    const aTime = Number.isFinite(new Date(a.placed_at).getTime())
      ? new Date(a.placed_at).getTime()
      : 0;
    const bTime = Number.isFinite(new Date(b.placed_at).getTime())
      ? new Date(b.placed_at).getTime()
      : 0;
    return bTime - aTime;
  });
}

export function AccountOrdersRealtime({ customerId, initialOrders }: Props) {
  const [orders, setOrders] = useState<OrderSummary[]>(sortByLatest(initialOrders));

  useEffect(() => {
    let unsubscribe: (() => void) | null = null;
    let isMounted = true;

    subscribeToPrivateRealtimeEvent<OrderUpdatedPayload>({
      channelName: `customer.orders.${customerId}`,
      eventName: "orders.updated",
      onEvent: (payload) => {
        const order = payload.order;
        if (!order?.id || !order.number || !order.status) {
          return;
        }

        const incoming: OrderSummary = {
          id: order.id,
          number: order.number,
          status: order.status,
          total: Number(order.total ?? 0),
          placed_at: order.placed_at ?? new Date().toISOString(),
          items_count: Number(order.items_count ?? 0),
        };

        setOrders((previous) => {
          const index = previous.findIndex((item) => item.id === incoming.id);
          if (index === -1) {
            return sortByLatest([incoming, ...previous]);
          }

          const updated = [...previous];
          updated[index] = { ...updated[index], ...incoming };
          return sortByLatest(updated);
        });
      },
    })
      .then((cleanup) => {
        if (!isMounted) {
          cleanup?.();
          return;
        }
        unsubscribe = cleanup;
      })
      .catch((error) => {
        logger.warn("Account realtime subscription failed", error);
      });

    return () => {
      isMounted = false;
      unsubscribe?.();
    };
  }, [customerId]);

  if (orders.length === 0) {
    return (
      <p className="text-xs text-neutral-500">
        Vous n’avez pas encore passé de commande.
      </p>
    );
  }

  return (
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
  );
}
