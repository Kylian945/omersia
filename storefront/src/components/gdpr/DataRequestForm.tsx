"use client";

import { useState } from "react";
import { Button } from "../common/Button";

type RequestType = "access" | "export" | "deletion" | "rectification";

export function DataRequestForm() {
  const [requestType, setRequestType] = useState<RequestType>("export");
  const [reason, setReason] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState<{ type: "success" | "error"; text: string } | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setMessage(null);

    try {
      const res = await fetch("/api/gdpr/data-requests", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          type: requestType,
          reason: reason || undefined,
        }),
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.message || data.error || "Une erreur est survenue");
      }

      setMessage({
        type: "success",
        text: data.message || "Votre demande a été enregistrée avec succès",
      });
      setReason("");
    } catch (error) {
      setMessage({
        type: "error",
        text: error instanceof Error ? error.message : "Une erreur est survenue",
      });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow">
      <h2 className="text-lg font-bold mb-6">Demande d&apos;accès aux données (RGPD)</h2>

      {message && (
        <div
          className={`mb-4 p-4 rounded-lg ${
            message.type === "success"
              ? "bg-green-50 text-green-800"
              : "bg-red-50 text-red-800"
          }`}
        >
          {message.text}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Type de demande */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Type de demande
          </label>
          <select
            value={requestType}
            onChange={(e) => setRequestType(e.target.value as RequestType)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="access">
              Accès à mes données - Consulter mes données personnelles
            </option>
            <option value="export">
              Export de mes données - Télécharger toutes mes données
            </option>
            <option value="deletion">
              Suppression de mes données - Exercer mon droit à l&apos;oubli
            </option>
            <option value="rectification">
              Rectification - Corriger des données inexactes
            </option>
          </select>
        </div>

        {/* Description selon le type */}
        <div className="p-4 bg-blue-50 rounded-lg">
          {requestType === "access" && (
            <p className="text-sm text-blue-800">
              Vous pouvez consulter vos données personnelles directement dans votre compte.
              Cette demande sera traitée pour confirmation.
            </p>
          )}
          {requestType === "export" && (
            <p className="text-sm text-blue-800">
              Nous générerons un fichier contenant toutes vos données personnelles.
              Le téléchargement sera disponible pendant 72 heures.
            </p>
          )}
          {requestType === "deletion" && (
            <p className="text-sm text-red-800">
              <strong>Attention :</strong> Cette action est irréversible. Toutes vos données
              seront supprimées ou anonymisées conformément au RGPD.
              Les commandes en cours doivent être finalisées avant la suppression.
            </p>
          )}
          {requestType === "rectification" && (
            <p className="text-sm text-blue-800">
              Vous pouvez modifier la plupart de vos informations directement dans votre
              compte. Cette demande concerne les données que vous ne pouvez pas modifier
              vous-même.
            </p>
          )}
        </div>

        {/* Raison (optionnelle) */}
        <div>
          <label htmlFor="reason" className="block text-sm font-medium text-gray-700 mb-2">
            Raison de votre demande (optionnelle)
          </label>
          <textarea
            id="reason"
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            rows={4}
            maxLength={1000}
            placeholder="Vous pouvez préciser la raison de votre demande..."
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <p className="mt-1 text-sm text-gray-500">{reason.length}/1000 caractères</p>
        </div>

        {/* Bouton de soumission */}
        <div>
          <Button
            type="submit"
            disabled={isLoading}
            variant="primary"
          >
            {isLoading ? "Envoi en cours..." : "Soumettre ma demande"}
          </Button>
        </div>

        {/* Informations légales */}
        <div className="text-xs text-gray-600 space-y-2">
          <p>
            Conformément au Règlement Général sur la Protection des Données (RGPD),
            nous traiterons votre demande dans un délai d&apos;un mois maximum.
          </p>
          <p>
            Pour en savoir plus sur vos droits et notre politique de confidentialité,
            consultez notre{" "}
            <a href="/privacy-policy" className="text-blue-600 hover:underline">
              Politique de confidentialité
            </a>
            .
          </p>
        </div>
      </form>
    </div>
  );
}
