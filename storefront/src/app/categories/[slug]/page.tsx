import { Metadata } from "next";
import { Header } from "@/components/common/Header";
import { Footer } from "@/components/common/Footer";
import { Container } from "@/components/common/Container";
import { CategoryProducts } from "@/components/category/CategoryProducts";
import { CategoryPageRenderer } from "@/components/category/CategoryPageRenderer";
import { getCategoryBySlug } from "@/lib/api-categories";
import { getEcommercePageByType } from "@/lib/api-ecommerce-pages";
import { getThemeSettings } from "@/lib/api-theme";
import Link from "next/link";

// Force dynamic rendering - no cache for Page Builder content
export const dynamic = 'force-dynamic';

type Props = {
  params: Promise<{ slug: string }>;
};

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;

  // PERF-002: Paralléliser les fetches de métadonnées
  const [pageData, data] = await Promise.all([
    getEcommercePageByType("category", slug, "fr"),
    getCategoryBySlug(slug, "fr"),
  ]);

  // Priorité aux métadonnées Page Builder
  if (pageData) {
    return {
      title: pageData.meta_title || pageData.title || "",
      description: pageData.meta_description || "",
    };
  }

  // Fallback to category metadata
  if (!data || !data.category) return {};

  const t = data.category.translations?.[0];

  return {
    title: t?.meta_title || t?.name || "",
    description: t?.meta_description || t?.description || "",
  };
}

export default async function CategoryPage({ params }: Props) {
  const { slug } = await params;

  // PERF-002: Paralléliser les fetches pour réduire le TTFB (-600ms)
  const [data, theme, pageData] = await Promise.all([
    getCategoryBySlug(slug, "fr"),
    getThemeSettings(),
    getEcommercePageByType("category", slug, "fr"),
  ]);

  if (!data || !data.category) {
    return (
      <>
        <Header />
        <main className="px-6 py-10 flex-1 flex items-center">
          <Container>
            <div className="max-w-md mx-auto text-center">
              <h1 className="text-2xl font-semibold">Catégorie introuvable</h1>
              <p className="mt-2 text-sm text-neutral-600">
                La catégorie que vous recherchez n&apos;existe pas ou n&apos;est plus disponible.
              </p>
              <Link
                href="/"
                className="mt-4 inline-block bg-black px-5 py-2 text-sm text-white hover:bg-neutral-900 rounded-full"
              >
                Retour à l&apos;accueil
              </Link>
            </div>
          </Container>
        </main>
        <Footer />
      </>
    );
  }

  const { category, products = [] } = data;
  const t = category.translations?.[0];

  // PERF-002: pageData déjà fetché en parallèle ci-dessus
  // If Page Builder content exists, use CategoryPageRenderer
  if (pageData) {
    return (
      <>
        <Header />
        <main className="flex-1">
          {/* Breadcrumb */}
          <Container>
            <nav className="text-xs text-neutral-500 py-4 flex flex-wrap items-center gap-x-1">
              <Link href="/" className="hover:text-black transition">
                Accueil
              </Link>
              <span className="flex items-center gap-1">
                <span>/</span>
                <span className="text-neutral-700">
                  {t?.name || "Catégorie"}
                </span>
              </span>
            </nav>
          </Container>

          {/* Page Builder Content */}
          <CategoryPageRenderer
            pageData={pageData}
            category={category}
            products={products}
            themePath={theme.component_path}
          />
        </main>
        <Footer />
      </>
    );
  }

  // Fallback: render default category page
  // Construit la chaîne de parents (du plus haut niveau au plus proche)
  const parents: Array<{ id: number; translations?: Array<{ slug?: string; name?: string }> }> = [];
  let current = category.parent;

  while (current) {
    parents.unshift(current);
    current = current.parent;
  }

  return (
    <>
      <Header />

      <main className="flex-1 py-8 md:py-10">
        <Container>
          {/* Fil d'Ariane */}
          <nav className="text-xs text-neutral-500 mb-3 flex flex-wrap items-center gap-x-1">
            <Link href="/" className="hover:text-black transition">
              Accueil
            </Link>
            {parents.map((parent) => {
              const pt = parent.translations?.[0];
              if (!pt?.slug) {
                return null;
              }
              if (pt.slug == "accueil") {
                return null;
              }

              return (
                <span key={parent.id} className="flex items-center gap-1">
                  <span>/</span>
                  <Link
                    href={`/categories/${pt.slug}`}
                    className="hover:text-black transition"
                  >
                    {pt.name || "Catégorie"}
                  </Link>
                </span>
              );
            })}
            <span className="flex items-center gap-1">
              <span>/</span>
              <span className="text-neutral-700">
                {t?.name || "Catégorie"}
              </span>
            </span>
          </nav>

          {/* Header catégorie */}
          <header className="mb-4 md:mb-6 space-y-1">
            <h1 className="text-2xl md:text-3xl font-semibold tracking-tight text-neutral-900">
              {t?.name || "Catégorie"}
            </h1>
            {t?.description && (
              <p className="max-w-2xl text-xs md:text-sm text-neutral-600 leading-relaxed">
                {t.description}
              </p>
            )}
          </header>

          {/* Sous-catégories directes */}
          {Array.isArray(category.children) && category.children.length > 0 && (
            <section className="mb-4">
              <div className="text-xs font-medium text-neutral-500 uppercase tracking-[.16em] mb-2">
                Sous-catégories
              </div>
              <div className="flex flex-wrap gap-3">
                {category.children.map((child) => {
                  const ct = child.translations?.[0];
                  if (!ct?.slug) return null;
                  return (
                    <Link
                      key={child.id}
                      href={`/categories/${ct.slug}`}
                      className="inline-flex items-center rounded-md border border-neutral-200 bg-white px-6 py-2 text-xs text-neutral-700 hover:border-black hover:text-black hover:bg-neutral-50 transition"
                    >
                      {ct.name || "Sans nom"}
                    </Link>
                  );
                })}
              </div>
            </section>
          )}

          {/* Filtres + résultats */}
          <CategoryProducts products={products} themePath={theme.component_path} />
        </Container>
      </main>

      <Footer />
    </>
  );
}
