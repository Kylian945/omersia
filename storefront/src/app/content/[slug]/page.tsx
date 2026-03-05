import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getPageBySlug } from "@/lib/api-pages";
import { PageBuilderWithTheme } from "@/components/builder/PageBuilderWithTheme";
import { Header } from "@/components/common/Header";
import { Footer } from "@/components/common/Footer";

type Props = {
    params: Promise<{ slug: string }>;
};

export async function generateMetadata({ params }: Props): Promise<Metadata> {
    const { slug } = await params;
    const page = await getPageBySlug(slug, "fr");
    if (!page) return {};
    return {
        title: page.meta_title || page.title,
        description: page.meta_description || undefined,
    };
}

export default async function ContentPage({ params }: Props) {
    const { slug } = await params;
    const page = await getPageBySlug(slug, "fr");

    if (!page) {
        notFound();
    }

    return (
        <>
            <Header />
            <main className="flex-1">
                <PageBuilderWithTheme layout={page.layout} />
            </main>
            <Footer />
        </>
    );
}
