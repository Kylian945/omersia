import { Metadata } from "next";
import { DataRequestForm, DataRequestsList } from "@/components/gdpr";
import { Footer } from "@/components/common/Footer";
import { Header } from "@/components/common/Header";
import { Book, Box, Pencil, Trash2 } from "lucide-react";
import { fetchUserSSR } from "@/lib/auth/fetchUserSSR";
import { redirect } from "next/navigation";

export const metadata: Metadata = {
  title: "Mes données personnelles - RGPD",
  description: "Gérez vos données personnelles et exercez vos droits RGPD",
};

export default async function PrivacyPage() {
  const user = await fetchUserSSR();
  if (!user) {
    redirect("/login");
  }

  return (
    <>
      <Header />
      <div className="container mx-auto px-4 py-8 max-w-6xl">
        <h1 className="text-xl font-bold mb-8">Mes données personnelles</h1>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Formulaire de demande RGPD */}
          <div>
            <DataRequestForm />
          </div>

          {/* Liste des demandes */}
          <div className="bg-white rounded-lg shadow p-6">
            <DataRequestsList customerId={user.id} />
          </div>
        </div>

        {/* Informations sur vos droits */}
        <div className="mt-12 bg-gray-50 rounded-lg p-6">
          <h2 className="text-lg font-bold mb-4">Vos droits RGPD</h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 className="font-semibold mb-2 flex items-center">
                <span className="mr-2"><Book className="w-4 h-4" /></span> Droit d&apos;accès
              </h3>
              <p className="text-sm text-gray-700">
                Vous pouvez consulter toutes vos données personnelles que nous détenons.
                La plupart sont accessibles directement depuis votre compte.
              </p>
            </div>

            <div>
              <h3 className="font-semibold mb-2 flex items-center">
                <span className="mr-2"><Box className="w-4 h-4" /></span> Droit à la portabilité
              </h3>
              <p className="text-sm text-gray-700">
                Vous pouvez exporter toutes vos données dans un format structuré et lisible.
                Le fichier sera disponible pendant 72 heures.
              </p>
            </div>

            <div>
              <h3 className="font-semibold mb-2 flex items-center">
                <span className="mr-2"><Trash2 className="w-4 h-4" /></span> Droit à l&apos;oubli
              </h3>
              <p className="text-sm text-gray-700">
                Vous pouvez demander la suppression de toutes vos données personnelles.
                Les commandes seront anonymisées pour respecter nos obligations légales.
              </p>
            </div>

            <div>
              <h3 className="font-semibold mb-2 flex items-center">
                <span className="mr-2"><Pencil className="w-4 h-4" /></span> Droit de rectification
              </h3>
              <p className="text-sm text-gray-700">
                Vous pouvez corriger vos données directement dans votre compte, ou nous
                contacter pour les données que vous ne pouvez pas modifier.
              </p>
            </div>
          </div>

          <div className="mt-6 p-4 bg-white rounded border border-gray-200">
            <p className="text-sm text-gray-700">
              <strong>Délai de traitement:</strong> Nous traitons toutes les demandes RGPD
              dans un délai maximum d&apos;un mois, conformément à la réglementation européenne.
            </p>
            <p className="text-sm text-gray-700 mt-2">
              Pour toute question, consultez notre{" "}
              <a href="/privacy-policy" className="text-blue-600 hover:underline">
                Politique de confidentialité
              </a>{" "}
              ou contactez notre{" "}
              <a href="mailto:privacy@omersia.com" className="text-blue-600 hover:underline">
                Délégué à la Protection des Données
              </a>
              .
            </p>
          </div>
        </div>
      </div>
      <Footer />
    </>
  );
}
