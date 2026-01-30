// app/checkout/page.tsx
import { Footer } from "@/components/common/Footer";
import { HeaderCheckout } from "@/components/common/HeaderCheckout";
import CheckoutClient from "@/components/checkout/CheckoutClient";

import { fetchUserSSR } from "@/lib/auth/fetchUserSSR"; // adapte le chemin
import { getAddresses, getShopInfo, type Address } from "@/lib/api"; // ton api.ts storefront
import { cookies } from "next/headers";


export default async function CheckoutPage() {
    // 1) User côté serveur via /auth/me
    const user = await fetchUserSSR();

    // 2) Adresses côté serveur via Storefront API
    const token = (await cookies()).get("auth_token")?.value;
    const addresses: Address[] = user ? (await getAddresses(token)) ?? [] : [];
    const shopInfo = await getShopInfo();

    return (
        <>
            <HeaderCheckout shopInfo={shopInfo} />
            <CheckoutClient initialUser={user} initialAddresses={addresses} />
            <Footer />
        </>
    );
}
