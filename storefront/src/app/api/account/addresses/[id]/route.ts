import { NextResponse } from "next/server";
import { fetchUserSSR } from "@/lib/auth/fetchUserSSR";
import { updateAddress, deleteAddress, type AddressInput } from "@/lib/api-addresses";
import { cookies } from "next/headers";

type Params = {
    params: Promise<{ id: string }>;
};



export async function PUT(req: Request, { params }: Params) {
    const user = await fetchUserSSR();
    const token = (await cookies()).get('auth_token')?.value
    if (!user) {
        return NextResponse.json({ message: "Unauthorized" }, { status: 401 });
    }
    const { id } = await params;

    if (Number.isNaN(Number(id))) {
        return NextResponse.json({ message: "Invalid id" }, { status: 400 });
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

    const updated = await updateAddress(Number(id), payload, token);
    if (!updated) {
        return NextResponse.json(
            { message: "Erreur lors de la mise à jour de l’adresse." },
            { status: 500 }
        );
    }

    return NextResponse.json(updated);
}

export async function DELETE(_req: Request, { params }: Params) {
    const user = await fetchUserSSR();
    const token = (await cookies()).get('auth_token')?.value
    if (!user) {
        return NextResponse.json({ message: "Unauthorized" }, { status: 401 });
    }

    const { id } = await params;
    if (Number.isNaN(Number(id))) {
        return NextResponse.json({ message: "Invalid id" }, { status: 400 });
    }

    const ok = await deleteAddress(Number(id), token);
    if (!ok) {
        return NextResponse.json(
            { message: "Erreur lors de la suppression de l’adresse." },
            { status: 500 }
        );
    }

    return NextResponse.json({ success: true });
}
