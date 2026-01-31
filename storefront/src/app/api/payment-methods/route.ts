import { NextRequest, NextResponse } from "next/server";
import { apiJson } from "@/lib/api-http";
import { cookies } from "next/headers";
import { logger } from "@/lib/logger";

export async function GET(_req: NextRequest) {
  const authToken = (await cookies()).get("auth_token")?.value;

  try {
    const { res, data } = await apiJson("/payment-methods", {
      method: "GET",
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
          ok: false,
          error: "Payment methods fetch failed",
          status: res.status,
          backend: text || null,
        },
        { status: res.status }
      );
    }

    return NextResponse.json(data, { status: 200 });
  } catch (err) {
    logger.error("Error calling backend /payment-methods:", err);

    return NextResponse.json(
      {
        ok: false,
        error: "Payment methods fetch failed",
      },
      { status: 500 }
    );
  }
}
