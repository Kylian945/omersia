import { NextRequest, NextResponse } from "next/server";
import { apiJson } from "@/lib/api-http";
import { cookies } from "next/headers";
import { logger } from "@/lib/logger";

export async function POST(
  _req: NextRequest,
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
    const { res, data } = await apiJson(`/orders/${number}/confirm`, {
      method: "POST",
      extraHeaders: {
        Authorization: `Bearer ${authToken}`,
      },
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
    logger.error("Error calling backend /orders/{number}/confirm:", err);

    return NextResponse.json(
      {
        ok: false,
        error: "Order confirmation failed",
      },
      { status: 500 }
    );
  }
}
