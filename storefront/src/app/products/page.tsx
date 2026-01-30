import { Metadata } from "next";
import Link from "next/link";

import { Header } from "@/components/common/Header";
import { Footer } from "@/components/common/Footer";
import { Container } from "@/components/common/Container";
import { getProducts } from "@/lib/api-products";
import { getThemeSettings } from "@/lib/api-theme";
import { ProductsPageClient } from "@/components/product/ProductsPageClient";

export const metadata: Metadata = {
  title: "Tous les produits • Omersia",
  description: "Parcourez l'ensemble des produits disponibles sur la boutique.",
};

export default async function ProductsPage({
  searchParams,
}: {
  searchParams: { page?: string };
}) {
  const page = searchParams.page ? parseInt(searchParams.page, 10) : 1;
  const products = await getProducts("fr", page);
  const theme = await getThemeSettings();

  if (!products || !products.data || !Array.isArray(products.data)) {
    return (
      <>
        <Header />
        <main className="px-6 py-10 flex-1 flex items-center">
          <Container>
            <div className="max-w-md mx-auto text-center">
              <h1 className="text-2xl font-semibold">Aucun produit</h1>
              <p className="mt-2 text-sm text-neutral-600">
                Impossible de récupérer la liste des produits pour le moment.
              </p>
              <Link
                href="/"
                className="mt-4 inline-block bg-black px-5 py-2 text-sm text-white hover:bg-neutral-900 rounded-full"
              >
                Retour à l’accueil
              </Link>
            </div>
          </Container>
        </main>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header />
      <main className="flex-1 py-10">
        <Container>
          {/* Fil d’Ariane */}
          <div className="text-xs text-neutral-500 mb-2">
            <Link href="/" className="hover:text-black transition">
              Accueil
            </Link>
            <span className="mx-1">/</span>
            <span className="text-neutral-700">Produits</span>
          </div>

          {/* Titre */}
          <header className="mb-4 md:mb-6 space-y-1">
            <h1 className="text-2xl md:text-3xl font-semibold tracking-tight text-neutral-900">
              Tous les produits
            </h1>
            <p className="max-w-xl text-xs md:text-sm text-neutral-600">
              Explorez l’ensemble du catalogue, appliquez des filtres et triez selon vos besoins.
            </p>
          </header>

          <ProductsPageClient initialData={products} themePath={theme.component_path} />
        </Container>
      </main>
      <Footer />
    </>
  );
}
