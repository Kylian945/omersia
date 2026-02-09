import React from 'react';
import { PageBuilderWithTheme } from '../builder/PageBuilderWithTheme';
import { CategoryProducts } from './CategoryProducts';
import { EcommercePage } from '@/lib/api-ecommerce-pages';
import { Container } from '../common/Container';
import Link from 'next/link';
import { Category } from '@/lib/types/category-types';
import { ListingProduct } from '@/components/product/ListingProducts';

interface CategoryPageRendererProps {
  pageData: EcommercePage;
  category: Category;
  products: ListingProduct[];
  themePath?: string;
}

export function CategoryPageRenderer({
  pageData,
  category,
  products,
  themePath = "vision"
}: CategoryPageRendererProps) {
  const { content } = pageData;

  const t = category.translations?.[0];

  // Rendu avec contenu natif (beforeNative + grid + afterNative)
  const { beforeNative, afterNative } = content;

  return (
    <div className="category-page-with-builder pb-6">


      {/* CONTENU NATIF: Grille de produits (non-éditable) */}
      <div className="native-content-container">
        <Container>
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
        </Container>
        {/* Sections AVANT le contenu natif */}
        {beforeNative?.sections && beforeNative.sections.length > 0 && (
          <div className="before-native-content">
            <PageBuilderWithTheme layout={beforeNative} />
          </div>
        )}
        <Container>
          <CategoryProducts products={products} themePath={themePath} />
        </Container>
      </div>

      {/* Sections APRÈS le contenu natif */}
      {afterNative?.sections && afterNative.sections.length > 0 && (
        <div className="after-native-content">
          <PageBuilderWithTheme layout={afterNative} />
        </div>
      )}
    </div>
  );
}
