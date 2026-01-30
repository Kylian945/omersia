import { NextRequest, NextResponse } from "next/server";
import { apiJson } from "@/lib/api";
import { cookies } from "next/headers";

type ApplyDiscountResponse = {
  ok: boolean;
  code: string;
  label: string;
  type: "product" | "order" | "shipping" | "buy_x_get_y";
  value_type: "percentage" | "fixed_amount" | "free_shipping" | null;
  value: number | null;
  discount_amount: number;
  free_shipping: boolean;
};

export async function POST(req: NextRequest) {
  const body = await req.json();
  const authToken = (await cookies()).get("auth_token")?.value;

  const { res, data } = await apiJson<ApplyDiscountResponse>(
    "/cart/apply-discount",
    {
      method: "POST",
      body,
      extraHeaders: authToken
        ? {
            Authorization: `Bearer ${authToken}`,
          }
        : undefined,
    }
  );

  if (!res.ok || !data) {
    const text = await res.text().catch(() => null);

    return NextResponse.json(
      {
        error: "Apply discount failed",
        status: res.status,
        backend: text || null,
      },
      { status: res.status }
    );
  }

  return NextResponse.json(data, { status: res.status });
}
