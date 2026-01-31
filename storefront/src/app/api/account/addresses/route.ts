import { NextResponse } from "next/server";
import { fetchUserSSR } from "@/lib/auth/fetchUserSSR";
import {
  getAddresses,
  createAddress,
  type AddressInput,
} from "@/lib/api";
import { cookies } from "next/headers";
import { logger } from "@/lib/logger";


export async function GET() {
  const user = await fetchUserSSR();
  const token = (await cookies()).get('auth_token')?.value
  if (!user) {
    return NextResponse.json({ message: "Unauthorized" }, { status: 401 });
  }

  const addresses = await getAddresses(token);
  return NextResponse.json(addresses ?? []);
}

export async function POST(req: Request) {
  const user = await fetchUserSSR();
  const token = (await cookies()).get("auth_token")?.value;

  if (!user) {
    return NextResponse.json({ message: "Unauthorized" }, { status: 401 });
  }

  const body = (await req.json()) as Partial<AddressInput>;

  if (!body.label || !body.line1 || !body.postcode || !body.city) {
    return NextResponse.json(
      { message: "Champs obligatoires manquants." },
      { status: 422 }
    );
  }

  const payload: AddressInput = {
    label: body.label,
    line1: body.line1,
    line2: body.line2,
    postcode: body.postcode,
    city: body.city,
    country: body.country ?? "FR",
    phone: body.phone,
    is_default_billing: body.is_default_billing ?? false,
    is_default_shipping: body.is_default_shipping ?? false,
  };

  try {
    const created = await createAddress(payload, token);

    if (!created) {
      return NextResponse.json(
        { message: "Erreur lors de la création de l’adresse." },
        { status: 502 } // "bad gateway" = backend a refusé
      );
    }

    return NextResponse.json(created, { status: 201 });
  } catch (e) {
    logger.error("POST /api/account/addresses error:", e);
    return NextResponse.json(
      { message: "Erreur interne côté Next." },
      { status: 500 }
    );
  }
}

