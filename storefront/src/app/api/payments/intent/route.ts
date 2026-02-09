import { NextRequest, NextResponse } from "next/server";
import { apiJson } from "@/lib/api-http";
import { cookies } from "next/headers";
import { logger } from "@/lib/logger";

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
    logger.error("Backend /payments/intent error", { status: res.status, text, data });

    const rawText = text ?? "";
    const lowered = rawText.toLowerCase();

    const looksLikeStripeConfigError =
      lowered.includes("no api key provided") ||
      lowered.includes("invalid api key") ||
      (lowered.includes("stripe") && lowered.includes("api key"));

    const looksLikeFrontApiKeyMissing =
      lowered.includes("api key not configured") ||
      lowered.includes("front_api_key");

    const messageFromBackend =
      typeof data === "object" &&
      data !== null &&
      "message" in data &&
      typeof data.message === "string"
        ? data.message
        : null;

    let message =
      messageFromBackend ||
      "Paiement impossible à traiter. Veuillez réessayer ou contacter le support.";

    if (looksLikeFrontApiKeyMissing) {
      message =
        "Clé API storefront manquante. Veuillez contacter le support.";
    } else if (looksLikeStripeConfigError) {
      message =
        "Stripe n'est pas configuré. Veuillez contacter le support.";
    }

    return NextResponse.json(
      {
        ok: false,
        message,
        backend: text,
      },
      { status: res.status }
    );
  }

  return NextResponse.json(data, { status: 200 });
}
