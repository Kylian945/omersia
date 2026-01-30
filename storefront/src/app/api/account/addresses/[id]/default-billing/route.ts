import { NextResponse } from "next/server";
import { fetchUserSSR } from "@/lib/auth/fetchUserSSR";
import { setDefaultBillingAddress } from "@/lib/api";
import { cookies } from "next/headers";

type Params = {
    params: Promise<{ id: string }>;
};


export async function POST(_req: Request, { params }: Params) {
    const token = (await cookies()).get('auth_token')?.value
    const user = await fetchUserSSR();
    if (!user) {
        return NextResponse.json({ message: "Unauthorized" }, { status: 401 });
    }

    const { id } = await params;
    if (Number.isNaN(Number(id))) {
        return NextResponse.json({ message: "Invalid id" }, { status: 400 });
    }

    const updated = await setDefaultBillingAddress(Number(id), token);
    if (!updated) {
        return NextResponse.json(
            { message: "Erreur lors de la mise à jour de l’adresse de facturation par défaut." },
            { status: 500 }
        );
    }

    return NextResponse.json(updated);
}
