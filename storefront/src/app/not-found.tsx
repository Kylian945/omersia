import { Footer } from "@/components/common/Footer";
import { Header } from "@/components/common/Header";
import Link from "next/link";

export default function NotFound() {
  return (
    <>
    <Header />
    
    <div className="min-h-screen bg-[#f6f6f7] flex items-center justify-center px-4">
      <div className="max-w-2xl w-full text-center">
        <div className="mb-8">
          <h1 className="text-9xl font-black text-gray-200">404</h1>
          <div className="mt-4">
            <h2 className="text-3xl font-bold text-gray-900 mb-2">
              Page introuvable
            </h2>
            <p className="text-gray-600 text-lg">
              Désolé, la page que vous recherchez n&apos;existe pas ou a été déplacée.
            </p>
          </div>
        </div>

        <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <Link
            href="/"
            className="inline-flex items-center justify-center px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-800 transition-colors font-medium"
          >
            Retour à l&apos;accueil
          </Link>
        </div>

        <div className="mt-12 pt-8 border-t border-gray-200">
          <p className="text-sm text-gray-500">
            Si vous pensez qu&apos;il s&apos;agit d&apos;une erreur, n&apos;hésitez pas à{" "}
            <Link href="/contact" className="text-black underline hover:no-underline">
              nous contacter
            </Link>
            .
          </p>
        </div>
      </div>
    </div>
    <Footer />
    </>
  );
}
