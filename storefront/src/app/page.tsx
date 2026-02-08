import { Header } from "@/components/common/Header";
import { Footer } from "@/components/common/Footer";
import { Container } from "@/components/common/Container";
import { CategoriesGrid } from "@/components/common/CategoriesGrid";
import { ListingProduct } from "@/components/product/ListingProducts";
import { ProductSlider } from "@/components/product/ProductSlider";
import { PageBuilderWithTheme } from "@/components/builder/PageBuilderWithTheme";
import { getThemeWidgets } from "@/lib/theme-widgets";
import { getCategoryBySlug, getCategories } from "@/lib/api-categories";
import { getEcommercePageBySlug } from "@/lib/api-ecommerce-pages";
import Link from "next/link";
import { Metadata } from "next";

// Force dynamic rendering - no cache for Page Builder content
export const dynamic = 'force-dynamic';

export async function generateMetadata(): Promise<Metadata> {
  // Try to get ecommerce page metadata first
  const pageData = await getEcommercePageBySlug("accueil", "fr");
  if (pageData) {
    return {
      title: pageData.meta_title || pageData.title || "Accueil | Omersia",
      description: pageData.meta_description || "",
    };
  }

  // Fallback to default
  return {
    title: "Accueil | Omersia",
    description: "Découvrez notre sélection de produits d'exception",
  };
}

async function getFeaturedProducts(): Promise<ListingProduct[]> {
  // Slug en minuscule (doit matcher CategoryTranslation.slug côté Laravel)
  const data = await getCategoryBySlug("accueil", "fr");

  if (!data || !Array.isArray(data.products)) {
    return [];
  }

  return data.products as ListingProduct[];
}

export default async function Home() {
  // PERF-003: Paralléliser tous les fetches dès le début (-400ms)
  const [pageData, products, categories, widgets] = await Promise.all([
    getEcommercePageBySlug("accueil", "fr"),
    getFeaturedProducts(),
    getCategories("fr", true), // parentOnly = true pour avoir uniquement les catégories principales
    getThemeWidgets(),
  ]);

  // If Page Builder content exists, render it
  if (pageData && pageData.content.sections) {
    return (
      <>
        <Header />
        <main className="flex-1">
          <PageBuilderWithTheme layout={{ sections: pageData.content.sections }} />
        </main>
        <Footer />
      </>
    );
  }
  const featured = products.slice(0, 12);
  const { HeroBanner, FeaturesBar, PromoBanner, Testimonials, Newsletter } = widgets;

  const testimonials = [
    {
      name: "Marie Dupont",
      role: "Cliente fidèle",
      content:
        "Excellente qualité et livraison rapide. Je recommande vivement cette boutique !",
      rating: 5,
    },
    {
      name: "Thomas Martin",
      role: "Acheteur vérifié",
      content:
        "Service client au top et produits conformes à la description. Très satisfait.",
      rating: 5,
    },
    {
      name: "Sophie Bernard",
      role: "Cliente",
      content:
        "Super expérience d'achat, je reviendrai sans hésiter. Merci pour la qualité !",
      rating: 5,
    },
  ];

  return (
    <>
      <Header />

      <main className="flex-1">
        {/* Hero Banner */}
        <HeroBanner
          badge="Nouvelle Collection"
          title="Découvrez notre sélection"
          subtitle="de produits d'exception"
          description="Explorez notre catalogue de produits soigneusement sélectionnés pour vous offrir qualité et style au quotidien."
          primaryCta={{
            text: "Voir les produits",
            href: "/products",
          }}
          secondaryCta={{
            text: "En savoir plus",
            href: "/content/a-propos",
          }}
        />

        {/* Features Bar */}
        <FeaturesBar />

        {/* Categories */}
        <Container>
          <CategoriesGrid categories={categories} />
        </Container>
        {/* Produits accueil en slider */}
        <section id="products" className="py-10">
          <Container>
            <div className="mb-4 flex items-baseline justify-between gap-4">
              <div>
                <h2 className="text-lg font-semibold text-neutral-900">
                  Produits mis en avant
                </h2>
              </div>
              <Link
                href="/products"
                className="text-xs font-medium text-neutral-600 hover:text-black"
              >
                Voir tous les produits →
              </Link>
            </div>

            {featured.length === 0 ? (
              <p className="text-xs text-center mt-6 text-gray-400 px-6 py-2 border border-dashed rounded-md border-gray-300">Aucun produit existant (Ajoutez des éléments dans l&apos;administration)
              </p>
            ) : (
              <ProductSlider products={featured} hrefBase="/products" />
            )}
          </Container>
        </section>

        {/* Promotional Banner */}
        <PromoBanner
          badge="Offre Limitée"
          title="Profitez de -20% sur toute la boutique"
          description="Utilisez le code BIENVENUE20 lors de votre commande. Offre valable jusqu'à la fin du mois."
          ctaText="Découvrir les offres"
          ctaHref="/products"
          variant="gradient"
        />

        {/* Testimonials */}
        <Testimonials testimonials={testimonials} />

        {/* Newsletter */}
        <Newsletter />
      </main>

      <Footer />
    </>
  );
}
