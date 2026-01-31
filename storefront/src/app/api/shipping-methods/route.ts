import { NextRequest, NextResponse } from "next/server";
import { apiJson } from "@/lib/api-http"; // adapte si ton fichier est ailleurs
import { cookies } from "next/headers";
import { logger } from "@/lib/logger";

export async function GET(req: NextRequest) {
  const authToken = (await cookies()).get("auth_token")?.value;

  // Récupérer les paramètres de l'URL
  const searchParams = req.nextUrl.searchParams;
  const cartTotal = searchParams.get("cart_total");
  const weight = searchParams.get("weight");
  const countryCode = searchParams.get("country_code");
  const postalCode = searchParams.get("postal_code");

  // Construire l'URL avec les paramètres
  const params = new URLSearchParams();
  if (cartTotal) params.append("cart_total", cartTotal);
  if (weight) params.append("weight", weight);
  if (countryCode) params.append("country_code", countryCode);
  if (postalCode) params.append("postal_code", postalCode);

  const url = `/shipping-methods${params.toString() ? `?${params.toString()}` : ""}`;

  try {
    const { res, data } = await apiJson(url, {
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
          error: "Shipping methods fetch failed",
          status: res.status,
          backend: text || null,
        },
        { status: res.status }
      );
    }

    // Laravel renvoie déjà { ok: true, data: [...] }
    return NextResponse.json(data, { status: 200 });
  } catch (err) {
    logger.error("Error calling backend /shipping-methods:", err);

    return NextResponse.json(
      {
        ok: false,
        error: "Shipping methods fetch failed",
      },
      { status: 500 }
    );
  }
}
