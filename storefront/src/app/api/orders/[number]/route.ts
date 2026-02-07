import { NextResponse } from "next/server";
import { apiJson } from "@/lib/api-http";
import { cookies } from "next/headers";
import { logger } from "@/lib/logger";
import type { OrderApi } from "@/lib/types/order-types";

export async function GET(
  _req: Request,
  { params }: { params: Promise<{ number: string }> }
) {
  const { number } = await params;
  const authToken = (await cookies()).get("auth_token")?.value;

  if (!authToken) {
    return NextResponse.json(
      { ok: false, error: "Unauthorized" },
      { status: 401 }
    );
  }

  try {
    const encodedNumber = encodeURIComponent(number);
    const { res, data } = await apiJson<OrderApi>(`/orders/${encodedNumber}`, {
      authToken,
      cache: "no-store",
    });

    if (!res.ok || !data) {
      return NextResponse.json(
        {
          ok: false,
          error: "Order fetch failed",
          status: res.status,
        },
        { status: res.status }
      );
    }

    return NextResponse.json({ ok: true, order: data }, { status: 200 });
  } catch (err) {
    logger.error("Error calling backend /orders/{number}:", err);

    return NextResponse.json(
      {
        ok: false,
        error: "Order fetch failed",
      },
      { status: 500 }
    );
  }
}
