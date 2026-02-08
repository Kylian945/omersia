import type { Metadata } from "next";
import { getPageBySlug } from "@/lib/api";
import { PageBuilderWithTheme } from "@/components/builder/PageBuilderWithTheme";
import { Header } from "@/components/common/Header";
import { Footer } from "@/components/common/Footer";
import { Container } from "@/components/common/Container";

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
        return (
            <>
                <Header />
                <main className="px-6 py-10 flex-1">
                    <Container>
                        <h1 className="text-2xl font-semibold">Page introuvable</h1>
                        <p className="mt-2 text-sm text-neutral-600">
                            La page demandée n’existe pas ou n’est plus disponible.
                        </p>
                    </Container>
                </main>
                <Footer />
            </>

        );
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
