
import { apiJson } from "./api-http";
import type { Address, AddressInput } from "./types/api-types";
import { logger } from "./logger";

// Re-export types for consumers
export type { Address, AddressInput };

export async function getAddresses(
  authToken?: string
): Promise<Address[] | null> {
  const { res, data } = await apiJson<Address[]>("/addresses", {
    authToken,
    cache: "no-store",
  });

  if (!res.ok) {
    if (res.status !== 401) {
      logger.warn("getAddresses failed:", res.status);
    }
    return null;
  }

  return data;
}

export async function getAddressById(
  id: number,
  authToken?: string
): Promise<Address | null> {
  const { res, data } = await apiJson<Address>(`/addresses/${id}`, {
    authToken,
    cache: "no-store",
  });

  if (!res.ok) {
    if (res.status !== 404) {
      logger.warn("getAddressById failed:", res.status);
    }
    return null;
  }

  return data;
}

export async function createAddress(
  payload: AddressInput,
  authToken?: string
): Promise<Address | null> {
  const { res, data } = await apiJson<Address>("/addresses", {
    method: "POST",
    authToken,
    body: payload,
    cache: "no-store",
  });

  if (!res.ok) {
    const text = await res.text().catch(() => "");
    logger.warn("createAddress failed:", { status: res.status, text });
    return null;
  }

  return data;
}

export async function updateAddress(
  id: number,
  payload: AddressInput,
  authToken?: string
): Promise<Address | null> {
  const { res, data } = await apiJson<Address>(`/addresses/${id}`, {
    method: "PUT",
    authToken,
    body: payload,
    cache: "no-store",
  });

  if (!res.ok) {
    logger.warn("updateAddress failed:", res.status);
    return null;
  }

  return data;
}

export async function deleteAddress(
  id: number,
  authToken?: string
): Promise<boolean> {
  const { res } = await apiJson<unknown>(`/addresses/${id}`, {
    method: "DELETE",
    authToken,
    cache: "no-store",
  });

  if (!res.ok) {
    const text = await res.text().catch(() => "");
    logger.warn("deleteAddress failed:", { status: res.status, text });
    return false;
  }

  return true;
}

export async function setDefaultShippingAddress(
  id: number,
  authToken?: string
): Promise<Address | null> {
  const { res, data } = await apiJson<Address>(
    `/addresses/${id}/default-shipping`,
    {
      method: "POST",
      authToken,
      cache: "no-store",
    }
  );

  if (!res.ok) {
    logger.warn("setDefaultShippingAddress failed:", res.status);
    return null;
  }

  return data;
}

export async function setDefaultBillingAddress(
  id: number,
  authToken?: string
): Promise<Address | null> {
  const { res, data } = await apiJson<Address>(
    `/addresses/${id}/default-billing`,
    {
      method: "POST",
      authToken,
      cache: "no-store",
    }
  );

  if (!res.ok) {
    logger.warn("setDefaultBillingAddress failed:", res.status);
    return null;
  }

  return data;
}
