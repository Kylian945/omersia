// app/checkout/page.tsx
import { Footer } from "@/components/common/Footer";
import { HeaderCheckout } from "@/components/common/HeaderCheckout";
import CheckoutClient from "@/components/checkout/CheckoutClient";

import { fetchUserSSR } from "@/lib/auth/fetchUserSSR"; // adapte le chemin
import { getAddresses, getShopInfo, type Address } from "@/lib/api"; // ton api.ts storefront
import { cookies } from "next/headers";


export default async function CheckoutPage() {
    const [user, shopInfo, cookieStore] = await Promise.all([
        fetchUserSSR(),
        getShopInfo(),
        cookies(),
    ]);

    const token = cookieStore.get("auth_token")?.value;
    const addresses: Address[] = user ? (await getAddresses(token)) ?? [] : [];

    return (
        <>
            <HeaderCheckout shopInfo={shopInfo} />
            <CheckoutClient initialUser={user} initialAddresses={addresses} />
            <Footer />
        </>
    );
}
