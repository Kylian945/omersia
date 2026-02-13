import { Header } from "@/components/common/Header";
import { Footer } from "@/components/common/Footer";
import { Container } from "@/components/common/Container";
import { OrderDetailClient } from "./ui/OrderDetailClient";
import { getOrderByNumber } from "@/lib/api-orders";
import type { OrderApi } from "@/lib/types/order-types";
import { fetchUserSSR } from "@/lib/auth/fetchUserSSR";
import { redirect } from "next/navigation";
import { cookies } from "next/headers";

type Props = {
  params: Promise<{ id: string }>;
};

export default async function OrderDetailPage({ params }: Props) {

  const { id } = await params;
  const user = await fetchUserSSR();
  if (!user) redirect("/login");

  const token = (await cookies()).get("auth_token")?.value;
  if (!token) redirect("/login");

  const order: OrderApi | null = await getOrderByNumber(id, token);
  if (!order) {
    redirect("/account");
  }

  return (
    <>
      <Header />
      <main className="flex-1 py-10 bg-[var(--theme-page-bg,#f6f6f7)]">
        <Container>
          <OrderDetailClient order={order} user={user} />
        </Container>
      </main>
      <Footer />
    </>
  );
}
