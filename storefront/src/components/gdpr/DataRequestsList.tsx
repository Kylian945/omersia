"use client";

import { useState, useEffect } from "react";
import { Button } from "../common/Button";

interface DataRequest {
  id: number;
  type: string;
  status: string;
  reason: string | null;
  requested_at: string;
  completed_at: string | null;
  export_file_path: string | null;
  export_expires_at: string | null;
}

const requestTypeLabels: Record<string, string> = {
  access: "Accès aux données",
  export: "Export des données",
  deletion: "Suppression des données",
  rectification: "Rectification",
};

const statusLabels: Record<string, string> = {
  pending: "En attente",
  processing: "En cours",
  completed: "Terminé",
  rejected: "Rejeté",
};

const statusColors: Record<string, string> = {
  pending: "bg-yellow-100 text-yellow-800",
  processing: "bg-blue-100 text-blue-800",
  completed: "bg-green-100 text-green-800",
  rejected: "bg-red-100 text-red-800",
};

export function DataRequestsList() {
  const [requests, setRequests] = useState<DataRequest[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadRequests();
  }, []);

  const loadRequests = async () => {
    try {
      setIsLoading(true);
      const res = await fetch("/api/gdpr/data-requests");

      if (!res.ok) {
        throw new Error("Erreur lors du chargement des demandes");
      }

      const data = await res.json();
      setRequests(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue");
    } finally {
      setIsLoading(false);
    }
  };

  const handleDownload = async (requestId: number) => {
    try {
      const res = await fetch(`/api/gdpr/data-requests/${requestId}/download`);

      if (!res.ok) {
        const error = await res.json();
        throw new Error(error.message || error.error || "Erreur lors du téléchargement");
      }

      // Créer un blob et télécharger le fichier
      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "my_data_export.json";
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err) {
      alert(err instanceof Error ? err.message : "Erreur lors du téléchargement");
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("fr-FR", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const isExportAvailable = (request: DataRequest) => {
    if (
      request.type !== "export" ||
      request.status !== "completed" ||
      !request.export_file_path ||
      !request.export_expires_at
    ) {
      return false;
    }

    const expiresAt = new Date(request.export_expires_at);
    return expiresAt > new Date();
  };

  if (isLoading) {
    return (
      <div className="text-center py-8">
        <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p className="mt-2 text-gray-600">Chargement...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 text-red-800 p-4 rounded-lg">
        {error}
      </div>
    );
  }

  if (requests.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        Vous n&apos;avez aucune demande RGPD pour le moment.
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold mb-4">Mes demandes RGPD</h3>

      {requests.map((request) => (
        <div
          key={request.id}
          className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
        >
          <div className="flex justify-between items-start mb-3">
            <div>
              <h4 className="font-medium">
                {requestTypeLabels[request.type] || request.type}
              </h4>
              <p className="text-sm text-gray-500">
                Demandé le {formatDate(request.requested_at)}
              </p>
            </div>
            <span
              className={`px-3 py-1 rounded-full text-xs font-medium ${
                statusColors[request.status] || "bg-gray-100 text-gray-800"
              }`}
            >
              {statusLabels[request.status] || request.status}
            </span>
          </div>

          {request.reason && (
            <div className="mb-3 p-3 bg-gray-50 rounded">
              <p className="text-sm text-gray-700">
                <strong>Raison:</strong> {request.reason}
              </p>
            </div>
          )}

          {request.completed_at && (
            <p className="text-sm text-gray-600 mb-3">
              Traité le {formatDate(request.completed_at)}
            </p>
          )}

          {isExportAvailable(request) && (
            <div className="mt-3 pt-3 border-t border-gray-200">
              <div className="flex items-center justify-between">
                <div className="text-sm text-gray-600">
                  <p>Votre export est disponible</p>
                  <p className="text-xs">
                    Expire le {formatDate(request.export_expires_at!)}
                  </p>
                </div>
                <Button
                  onClick={() => handleDownload(request.id)}
                  variant="primary"
                  size="sm">
                  Télécharger
                </Button>
              </div>
            </div>
          )}

          {request.type === "export" &&
            request.status === "completed" &&
            !isExportAvailable(request) && (
              <p className="text-sm text-gray-500 mt-3 pt-3 border-t border-gray-200">
                Le fichier d&apos;export a expiré (disponible pendant 72h)
              </p>
            )}
        </div>
      ))}
    </div>
  );
}
