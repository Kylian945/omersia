
import { apiJson } from "./api-http";
import type { Address, AddressInput } from "./types/api-types";

export async function getAddresses(
  authToken?: string
): Promise<Address[] | null> {
  const { res, data } = await apiJson<Address[]>("/addresses", {
    authToken,
    cache: "no-store",
  });

  if (!res.ok) {
    if (res.status !== 401) {
      console.warn("getAddresses failed:", res.status);
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
      console.warn("getAddressById failed:", res.status);
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
    console.warn("createAddress failed:", res.status, text);
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
    console.warn("updateAddress failed:", res.status);
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
    console.warn("deleteAddress failed:", res.status, text);
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
    console.warn("setDefaultShippingAddress failed:", res.status);
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
    console.warn("setDefaultBillingAddress failed:", res.status);
    return null;
  }

  return data;
}
