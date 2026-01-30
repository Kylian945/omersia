"use client";

import { useState, useEffect, useCallback } from "react";

interface CookieConsent {
  has_consent: boolean;
  necessary: boolean;
  functional: boolean;
  analytics: boolean;
  marketing: boolean;
  consented_at?: string;
  expires_at?: string;
}

export function useCookieConsent() {
  const [consent, setConsent] = useState<CookieConsent | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const loadConsent = useCallback(async () => {
    try {
      setIsLoading(true);
      const res = await fetch("/api/gdpr/cookie-consent", {
        credentials: "include",
      });
      const data = await res.json();
      setConsent(data);
    } catch (error) {
      console.error("Error loading cookie consent:", error);
      // Par défaut, pas de consentement = refus (RGPD)
      setConsent({
        has_consent: false,
        necessary: true,
        functional: false,
        analytics: false,
        marketing: false,
      });
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadConsent();
  }, [loadConsent]);

  const isAllowed = useCallback(
    (type: "necessary" | "functional" | "analytics" | "marketing"): boolean => {
      if (!consent) return type === "necessary"; // Les cookies nécessaires sont toujours autorisés
      return consent[type] === true;
    },
    [consent]
  );

  const checkConsent = useCallback(
    async (type: "necessary" | "functional" | "analytics" | "marketing"): Promise<boolean> => {
      try {
        const res = await fetch(`/api/gdpr/cookie-consent/check/${type}`, {
          credentials: "include",
        });
        const data = await res.json();
        return data.allowed === true;
      } catch (error) {
        console.error(`Error checking ${type} cookie consent:`, error);
        return type === "necessary"; // Toujours autoriser les cookies nécessaires
      }
    },
    []
  );

  return {
    consent,
    isLoading,
    isAllowed,
    checkConsent,
    reload: loadConsent,
  };
}
