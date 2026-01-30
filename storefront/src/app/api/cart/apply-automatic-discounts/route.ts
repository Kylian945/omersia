// app/api/cart/apply-automatic-discounts/route.ts
import { NextRequest, NextResponse } from "next/server";
import { apiJson } from "@/lib/api";
import { cookies } from "next/headers";

export async function POST(req: NextRequest) {
  const body = await req.json();

  const authToken = (await cookies()).get("auth_token")?.value;

  const { res, data } = await apiJson("/cart/apply-automatic-discounts", {
    method: "POST",
    body,
    extraHeaders: authToken
      ? { Authorization: `Bearer ${authToken}` }
      : undefined,
  });

  if (!res.ok || !data) {
    const text = await res.text().catch(() => null);

    return NextResponse.json(
      {
        ok: false,
        message: "Erreur lors du calcul des remises automatiques.",
        status: res.status,
        backend: text || null,
      },
      { status: res.status }
    );
  }

  return NextResponse.json(data);
}
