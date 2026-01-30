import { NextResponse } from "next/server";
import { fetchUserSSR } from "@/lib/auth/fetchUserSSR";
import { setDefaultShippingAddress } from "@/lib/api";
import { cookies } from "next/headers";

type Params = {
  params: Promise<{ id: string }>;
};


export async function POST(_req: Request, { params }: Params) {
  const user = await fetchUserSSR();
  const token = (await cookies()).get('auth_token')?.value
  if (!user) {
    return NextResponse.json({ message: "Unauthorized" }, { status: 401 });
  }

  const { id } = await params;
  if (Number.isNaN(Number(id))) {
    return NextResponse.json({ message: "Invalid id" }, { status: 400 });
  }

  const updated = await setDefaultShippingAddress(Number(id), token);
  if (!updated) {
    return NextResponse.json(
      { message: "Erreur lors de la mise à jour de l’adresse de livraison par défaut." },
      { status: 500 }
    );
  }

  return NextResponse.json(updated);
}
