import { NextResponse } from "next/server";
import { apiJson } from "@/lib/api-http";
import { logger } from "@/lib/logger";

type TaxBreakdown = {
  items?: Array<{
    id: number;
    name: string;
    tax_amount: number;
  }>;
  [key: string]: unknown;
};

type TaxCalculationResponse = {
  tax_total: number;
  tax_rate: number;
  price_excluding_tax: number;
  tax_zone?: {
    id: number;
    name: string;
    code: string;
  } | null;
  breakdown?: TaxBreakdown;
};

export async function POST(req: Request) {
  try {
    const body = await req.json();

    if (!body.country) {
      return NextResponse.json(
        { message: "Pays requis pour le calcul des taxes" },
        { status: 422 }
      );
    }

    if (!body.subtotal || body.subtotal < 0) {
      return NextResponse.json(
        { message: "Subtotal requis" },
        { status: 422 }
      );
    }

    // Les prix sont TTC (Toutes Taxes Comprises)
    // On utilise /calculate-included-tax pour EXTRAIRE la taxe du prix TTC
    const payload = {
      price_including_tax: body.subtotal,
      address: {
        country: body.country,
        postal_code: body.postcode || "",
        state: body.state || null,
      },
    };

    // Appel au backend Laravel pour extraire les taxes du prix TTC
    const { res, data } = await apiJson<TaxCalculationResponse>(
      "/calculate-included-tax",
      {
        method: "POST",
        body: payload,
      }
    );

    if (!res.ok || !data) {
      logger.error("Erreur calcul taxes:", { status: res.status, statusText: res.statusText });
      // En cas d'erreur, retourner 0 pour ne pas bloquer le checkout
      return NextResponse.json({
        tax_total: 0,
        tax_rate: 0,
      });
    }

    return NextResponse.json({
      tax_total: data.tax_total,
      tax_rate: data.tax_rate,
    });
  } catch (err) {
    logger.error("Erreur lors du calcul des taxes:", err);
    // En cas d'erreur, retourner 0 pour ne pas bloquer le checkout
    return NextResponse.json({
      tax_total: 0,
      tax_rate: 0,
    });
  }
}
