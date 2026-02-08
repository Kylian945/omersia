import { NextRequest, NextResponse } from "next/server";
import { apiJson } from "@/lib/api-http";
import { cookies } from "next/headers";

type CartSyncResponse = {
  id: number;
  token: string;
  subtotal: number;
  total_qty: number;
  currency: string;
};

export async function POST(req: NextRequest) {
  const body = await req.json();

  const authToken = (await cookies()).get("auth_token")?.value;

  const { res, data } = await apiJson<CartSyncResponse>("/cart/sync", {
    method: "POST",
    body,
    extraHeaders: authToken
      ? {
          Authorization: `Bearer ${authToken}`,
        }
      : undefined,
  });

  if (!res.ok || !data) {
    const text = await res.text().catch(() => null);

    return NextResponse.json(
      {
        error: "Cart sync failed",
        status: res.status,
        backend: text || null,
      },
      { status: res.status }
    );
  }

  return NextResponse.json(data, { status: res.status });
}
