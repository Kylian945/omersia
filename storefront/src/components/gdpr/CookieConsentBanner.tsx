"use client";

import { useState, useEffect } from "react";
import { Button } from "../common/Button";
import { logger } from "@/lib/logger";

interface CookieConsent {
  necessary: boolean;
  functional: boolean;
  analytics: boolean;
  marketing: boolean;
}

export function CookieConsentBanner() {
  const [showBanner, setShowBanner] = useState(false);
  const [showDetails, setShowDetails] = useState(false);
  const [consent, setConsent] = useState<CookieConsent>({
    necessary: true,
    functional: false,
    analytics: false,
    marketing: false,
  });

  useEffect(() => {
    checkExistingConsent();
  }, []);

  const checkExistingConsent = async () => {
    try {
      const res = await fetch("/api/gdpr/cookie-consent", {
        credentials: "include",
      });
      const data = await res.json();

      if (!data.has_consent) {
        setShowBanner(true);
      }
    } catch (error) {
      logger.error("Error checking cookie consent:", error);
      setShowBanner(true);
    }
  };

  const saveConsent = async (customConsent?: CookieConsent) => {
    const consentToSave = customConsent || consent;

    try {
      await fetch("/api/gdpr/cookie-consent", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          functional: consentToSave.functional,
          analytics: consentToSave.analytics,
          marketing: consentToSave.marketing,
        }),
      });

      setShowBanner(false);
    } catch (error) {
      logger.error("Error saving cookie consent:", error);
    }
  };

  const acceptAll = () => {
    const allAccepted: CookieConsent = {
      necessary: true,
      functional: true,
      analytics: true,
      marketing: true,
    };
    saveConsent(allAccepted);
  };

  const rejectAll = () => {
    const allRejected: CookieConsent = {
      necessary: true,
      functional: false,
      analytics: false,
      marketing: false,
    };
    saveConsent(allRejected);
  };

  const saveCustom = () => {
    saveConsent();
  };

  if (!showBanner) {
    return null;
  }

  return (
    <div className="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-lg">
      <div className="max-w-6xl mx-auto p-6">
        {!showDetails ? (
          // Vue simple
          <div className="flex flex-col md:flex-row items-center justify-between gap-4">
            <div className="flex-1">
              <h3 className="text-lg font-semibold mb-2">
                Nous respectons votre vie privée
              </h3>
              <p className="text-sm text-gray-600">
                Nous utilisons des cookies pour améliorer votre expérience sur notre site.
                Vous pouvez accepter tous les cookies ou personnaliser vos préférences.
              </p>
            </div>
            <div className="flex gap-3">
              <Button
                onClick={() => setShowDetails(true)}
                variant="secondary"
                size="sm"
              >
                Personnaliser
              </Button>
              <Button
                onClick={rejectAll}
                variant="secondary"
                size="sm"
              >
                Refuser
              </Button>
              <Button
                onClick={acceptAll}
                variant="primary"
                size="sm"
              >
                Tout accepter
              </Button>
            </div>
          </div>
        ) : (
          // Vue détaillée
          <div>
            <div className="mb-4">
              <Button
                onClick={() => setShowDetails(false)}
                variant="secondary"
                size="sm"
                className="text-gray-600"
              >
                ← Retour
              </Button>
            </div>

            <h3 className="text-lg font-semibold mb-4">
              Personnalisez vos préférences de cookies
            </h3>

            <div className="space-y-4 mb-6">
              {/* Cookies nécessaires */}
              <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div className="flex-1">
                  <h4 className="font-medium mb-1">Cookies nécessaires</h4>
                  <p className="text-sm text-gray-600">
                    Ces cookies sont essentiels au fonctionnement du site et ne peuvent pas être désactivés.
                  </p>
                </div>
                <div className="ml-4">
                  <span className="text-sm text-gray-500">Toujours actif</span>
                </div>
              </div>

              {/* Cookies fonctionnels */}
              <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div className="flex-1">
                  <h4 className="font-medium mb-1">Cookies fonctionnels</h4>
                  <p className="text-sm text-gray-600">
                    Ces cookies permettent de mémoriser vos préférences (langue, devise, etc.).
                  </p>
                </div>
                <div className="ml-4">
                  <input
                    type="checkbox"
                    checked={consent.functional}
                    onChange={(e) =>
                      setConsent({ ...consent, functional: e.target.checked })
                    }
                    className="w-5 h-5 text-blue-600 rounded"
                  />
                </div>
              </div>

              {/* Cookies analytiques */}
              <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div className="flex-1">
                  <h4 className="font-medium mb-1">Cookies analytiques</h4>
                  <p className="text-sm text-gray-600">
                    Ces cookies nous aident à comprendre comment vous utilisez notre site.
                  </p>
                </div>
                <div className="ml-4">
                  <input
                    type="checkbox"
                    checked={consent.analytics}
                    onChange={(e) =>
                      setConsent({ ...consent, analytics: e.target.checked })
                    }
                    className="w-5 h-5 text-blue-600 rounded"
                  />
                </div>
              </div>

              {/* Cookies marketing */}
              <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div className="flex-1">
                  <h4 className="font-medium mb-1">Cookies marketing</h4>
                  <p className="text-sm text-gray-600">
                    Ces cookies permettent de vous proposer des publicités personnalisées.
                  </p>
                </div>
                <div className="ml-4">
                  <input
                    type="checkbox"
                    checked={consent.marketing}
                    onChange={(e) =>
                      setConsent({ ...consent, marketing: e.target.checked })
                    }
                    className="w-5 h-5 text-blue-600 rounded"
                  />
                </div>
              </div>
            </div>

            <div className="flex gap-3 justify-end">
              <Button
                onClick={rejectAll}
                variant="secondary"
                size="sm"
              >
                Tout refuser
              </Button>
              <Button
                onClick={saveCustom}
                variant="primary"
                size="sm"
              >
                Enregistrer mes préférences
              </Button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
