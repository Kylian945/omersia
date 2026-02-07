"use client";

import { useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { logger } from "@/lib/logger";
import { subscribeToPrivateRealtimeEvent } from "@/lib/realtime/reverb-client";

type Props = {
  customerId: number;
  orderNumber: string;
  initialStatus: string;
  initialPaymentStatus: string;
  paymentIntentId?: string;
};

type OrderUpdatedPayload = {
  order?: {
    number?: string | null;
    status?: string;
    payment_status?: string;
  };
};

function isConfirmedAndPaid(status: string, paymentStatus: string): boolean {
  return status === "confirmed" && paymentStatus === "paid";
}

export function OrderSuccessStatusWatcher({
  customerId,
  orderNumber,
  initialStatus,
  initialPaymentStatus,
  paymentIntentId,
}: Props) {
  const router = useRouter();
  const [status, setStatus] = useState(initialStatus);
  const [paymentStatus, setPaymentStatus] = useState(initialPaymentStatus);
  const hasRefreshedRef = useRef(false);
  const statusRef = useRef(initialStatus);
  const paymentStatusRef = useRef(initialPaymentStatus);

  const isChecking = !isConfirmedAndPaid(status, paymentStatus);

  useEffect(() => {
    statusRef.current = status;
  }, [status]);

  useEffect(() => {
    paymentStatusRef.current = paymentStatus;
  }, [paymentStatus]);

  useEffect(() => {
    if (!isChecking) {
      return;
    }

    let isMounted = true;
    let unsubscribe: (() => void) | null = null;

    const completeIfConfirmed = (nextStatus: string, nextPaymentStatus: string) => {
      if (!isConfirmedAndPaid(nextStatus, nextPaymentStatus)) {
        return;
      }

      if (!hasRefreshedRef.current) {
        hasRefreshedRef.current = true;
        router.refresh();
      }
    };

    subscribeToPrivateRealtimeEvent<OrderUpdatedPayload>({
      channelName: `customer.orders.${customerId}`,
      eventName: "orders.updated",
      onEvent: (payload) => {
        const incoming = payload.order;
        if (!incoming) {
          return;
        }

        if (incoming.number && incoming.number !== orderNumber) {
          return;
        }

        const nextStatus =
          typeof incoming.status === "string" ? incoming.status : statusRef.current;
        const nextPaymentStatus =
          typeof incoming.payment_status === "string"
            ? incoming.payment_status
            : paymentStatusRef.current;

        if (typeof incoming.status === "string") {
          statusRef.current = incoming.status;
          setStatus(incoming.status);
        }

        if (typeof incoming.payment_status === "string") {
          paymentStatusRef.current = incoming.payment_status;
          setPaymentStatus(incoming.payment_status);
        }

        completeIfConfirmed(nextStatus, nextPaymentStatus);
      },
    })
      .then((cleanup) => {
        if (!isMounted) {
          cleanup?.();
          return;
        }

        unsubscribe = cleanup;

        // One-shot manual confirmation attempt, then a one-shot state sync.
        fetch(`/api/orders/${encodeURIComponent(orderNumber)}/confirm`, {
          method: "POST",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(
            paymentIntentId ? { payment_intent_id: paymentIntentId } : {}
          ),
        })
          .catch(() => null)
          .finally(async () => {
            try {
              const res = await fetch(`/api/orders/${encodeURIComponent(orderNumber)}`, {
                method: "GET",
                credentials: "include",
                cache: "no-store",
                headers: {
                  Accept: "application/json",
                },
              });

              if (!res.ok) {
                return;
              }

              const data = (await res.json()) as OrderUpdatedPayload;
              const syncedStatus =
                typeof data.order?.status === "string"
                  ? data.order.status
                  : statusRef.current;
              const syncedPaymentStatus =
                typeof data.order?.payment_status === "string"
                  ? data.order.payment_status
                  : paymentStatusRef.current;

              statusRef.current = syncedStatus;
              paymentStatusRef.current = syncedPaymentStatus;
              setStatus(syncedStatus);
              setPaymentStatus(syncedPaymentStatus);
              completeIfConfirmed(syncedStatus, syncedPaymentStatus);
            } catch (error) {
              logger.warn("Order success one-shot sync failed", error);
            }
          });
      })
      .catch((error) => {
        logger.warn("Order success realtime subscription failed", error);
      });

    return () => {
      isMounted = false;
      unsubscribe?.();
    };
  }, [customerId, isChecking, orderNumber, paymentIntentId, router]);

  if (!isChecking) {
    return null;
  }

  return (
    <div className="mt-3 inline-flex items-center gap-2 text-xs font-medium text-amber-700">
      <span className="inline-flex h-3 w-3 animate-spin rounded-full border-2 border-amber-300 border-t-amber-700" />
      Vérification automatique de la confirmation...
    </div>
  );
}
