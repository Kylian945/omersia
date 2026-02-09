import { Footer } from "@/components/common/Footer";
import CartClient from "@/components/cart/CartClient";
import { Header } from "@/components/common/Header";

export default function CheckoutPage() {
    return (
        <>
            <Header />
            <CartClient />
            <Footer />
        </>
    );
}
