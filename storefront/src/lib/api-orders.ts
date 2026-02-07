
import { apiJson } from "./api-http";
import type {
  CheckoutOrderPayload,
  OrderSummary,
  OrderApi,
} from "./types/api-types";
import { logger } from "./logger";

export async function getOrders(
  authToken?: string
): Promise<OrderSummary[] | null> {
  const { res, data } = await apiJson<OrderSummary[]>("/orders", {
    authToken,
    cache: "no-store",
  });

  if (!res.ok) {
    if (res.status !== 401) {
      logger.warn("getOrders failed:", res.status);
    }
    return null;
  }

  return data;
}

export async function createOrder(
  payload: CheckoutOrderPayload,
  authToken?: string
): Promise<OrderApi | null> {
  const { res, data } = await apiJson<OrderApi>("/orders", {
    method: "POST",
    authToken,
    body: payload,
    cache: "no-store",
  });

  if (!res.ok) {
    const text = await res.text().catch(() => "");
    logger.warn("createOrder failed:", res.status, text);
    return null;
  }

  return data;
}

export async function updateOrder(
  orderId: number,
  payload: CheckoutOrderPayload,
  authToken?: string
): Promise<OrderApi | null> {
  const { res, data } = await apiJson<OrderApi>(`/orders/${orderId}`, {
    method: "PUT",
    authToken,
    body: payload,
    cache: "no-store",
  });

  if (!res.ok) {
    const text = await res.text().catch(() => "");
    logger.warn("updateOrder failed:", res.status, text);
    return null;
  }

  return data;
}

export async function getOrderByNumber(
  number: string,
  authToken?: string
): Promise<OrderApi | null> {
  const safeNumber = encodeURIComponent(number);
  const { res, data } = await apiJson<OrderApi>(`/orders/${safeNumber}`, {
    authToken,
    cache: "no-store",
  });

  if (!res.ok) {
    if (res.status !== 404) {
      logger.warn("getOrderByNumber failed:", res.status);
    }
    return null;
  }

  return data;
}

type ConfirmOrderResponse = {
  message?: string;
  order?: OrderApi;
};

export async function confirmDraftOrderByNumber(
  number: string,
  authToken?: string,
  paymentIntentId?: string
): Promise<OrderApi | null> {
  const current = await getOrderByNumber(number, authToken);
  if (!current) return null;

  if (current.status !== "draft") {
    return current;
  }

  const body =
    paymentIntentId && paymentIntentId.trim().length > 0
      ? { payment_intent_id: paymentIntentId }
      : {};

  const { res, data } = await apiJson<ConfirmOrderResponse>(
    `/orders/${current.id}/confirm`,
    {
      method: "POST",
      authToken,
      body,
      cache: "no-store",
    }
  );

  if (!res.ok) {
    const text = await res.text().catch(() => "");
    logger.warn("confirmDraftOrderByNumber failed:", res.status, text);
    return current;
  }

  // Re-fetch full order payload (items + shipping relations) after confirmation.
  return (await getOrderByNumber(number, authToken)) ?? data?.order ?? current;
}
