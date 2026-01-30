import { NextRequest, NextResponse } from "next/server";
import { apiJson } from "@/lib/api";
import { cookies } from "next/headers";

type BackendPaymentIntentResponse = {
  ok: boolean;
  data?: {
    provider: string;
    client_secret: string;
  };
  message?: string;
};

export async function POST(req: NextRequest) {
  const body = await req.json();
  const authToken = (await cookies()).get("auth_token")?.value || undefined;

  const { res, data } = await apiJson<BackendPaymentIntentResponse>(
    "/payments/intent",
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

  // Debug utile si ça foire
  if (!res.ok || !data) {
    const text = await res.text().catch(() => null);
    console.error("Backend /payments/intent error", res.status, text, data);

    return NextResponse.json(
      {
        ok: false,
        message:
          (typeof data === 'object' && data !== null && 'message' in data && typeof data.message === 'string'
            ? data.message
            : "Payment intent failed (backend error)."),
        backend: text,
      },
      { status: res.status }
    );
  }

  return NextResponse.json(data, { status: 200 });
}
