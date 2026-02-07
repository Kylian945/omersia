import { NextRequest, NextResponse } from "next/server";
import { apiJson } from "@/lib/api-http";
import { cookies } from "next/headers";
import { logger } from "@/lib/logger";

export async function POST(
  req: NextRequest,
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
    const payload = (await req.json().catch(() => ({}))) as {
      payment_intent_id?: string;
    };

    const paymentIntentId =
      typeof payload.payment_intent_id === "string"
        ? payload.payment_intent_id
        : undefined;

    const encodedNumber = encodeURIComponent(number);
    const { res: orderRes, data: order } = await apiJson<{ id: number }>(
      `/orders/${encodedNumber}`,
      {
        authToken,
        cache: "no-store",
      }
    );

    if (!orderRes.ok || !order) {
      return NextResponse.json(
        {
          ok: false,
          error: "Order not found",
          status: orderRes.status,
        },
        { status: orderRes.status }
      );
    }

    const { res, data } = await apiJson(`/orders/${order.id}/confirm`, {
      method: "POST",
      authToken,
      body: paymentIntentId ? { payment_intent_id: paymentIntentId } : {},
      cache: "no-store",
    });

    if (!res.ok || !data) {
      const text = await res.text().catch(() => null);

      return NextResponse.json(
        {
          ok: false,
          error: "Order confirmation failed",
          status: res.status,
          backend: text || null,
        },
        { status: res.status }
      );
    }

    return NextResponse.json(data, { status: 200 });
  } catch (err) {
    logger.error("Error calling backend /orders/{id}/confirm:", err);

    return NextResponse.json(
      {
        ok: false,
        error: "Order confirmation failed",
      },
      { status: 500 }
    );
  }
}
