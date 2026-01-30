
import { apiJson } from "./api-http";
import type {
  CheckoutOrderPayload,
  OrderSummary,
  OrderApi,
} from "./types/api-types";

export async function getOrders(
  authToken?: string
): Promise<OrderSummary[] | null> {
  const { res, data } = await apiJson<OrderSummary[]>("/orders", {
    authToken,
    cache: "no-store",
  });

  if (!res.ok) {
    if (res.status !== 401) {
      console.warn("getOrders failed:", res.status);
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
    console.warn("createOrder failed:", res.status, text);
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
    console.warn("updateOrder failed:", res.status, text);
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
      console.warn("getOrderByNumber failed:", res.status);
    }
    return null;
  }

  return data;
}
