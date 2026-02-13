"use client";

import { useEffect, useState } from "react";
import { loadStripe } from "@stripe/stripe-js";
import {
  Elements,
  PaymentElement,
  useStripe,
  useElements,
} from "@stripe/react-stripe-js";
import { Button } from "@/components/common/Button";
import { logger } from "@/lib/logger";

const stripePublishableKey = process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY;
const stripePromise = stripePublishableKey ? loadStripe(stripePublishableKey) : null;
const stripeConfigurationError = stripePublishableKey
  ? null
  : "Stripe n'est pas configuré. Veuillez contacter le support.";

type StripePaymentFormProps = {
  orderId: number;
  orderNumber: string | null;
  total: number;
};

export function StripePaymentForm({ orderId, orderNumber, total }: StripePaymentFormProps) {
  const [clientSecret, setClientSecret] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(stripeConfigurationError);
  const [isErrorModalOpen, setIsErrorModalOpen] = useState(Boolean(stripeConfigurationError));

  const openErrorModal = (message: string) => {
    setError(message);
    setIsErrorModalOpen(true);
  };

  const closeErrorModal = () => setIsErrorModalOpen(false);

  useEffect(() => {
    if (!orderId || !stripePublishableKey) return;

    let cancelled = false;

    (async () => {
      try {
        const res = await fetch("/api/payments/intent", {
          method: "POST",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({
            order_id: orderId,
            provider: "stripe",
          }),
        });

        const json = await res.json().catch(() => null);

        if (!res.ok || !json) {
          interface ErrorResponse {
            message?: string;
            backend?: string;
          }
          const errorData = json as ErrorResponse;
          const message =
            errorData?.message ||
            errorData?.backend ||
            `Impossible d'initialiser le paiement (HTTP ${res.status}).`;
          if (!cancelled) {
            openErrorModal(message);
          }
          return;
        }

        // 👉 On sait que ta réponse ressemble à ça :
        // { ok: true, data: { provider: 'stripe', client_secret: '...' } }
        const cs = json.data?.client_secret;

        if (!cs) {
          if (!cancelled) {
            openErrorModal(
              "client_secret manquant dans la réponse de l'API."
            );
          }
          return;
        }

        if (!cancelled) {
          setClientSecret(cs);
          setError(null);
          setIsErrorModalOpen(false);
        }
      } catch (e: unknown) {
        logger.error("StripePaymentForm error", e);
        if (!cancelled) {
          const message = e instanceof Error ? e.message : "Erreur lors de l'initialisation du paiement.";
          openErrorModal(message);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [orderId, total]);

  if (!clientSecret || !stripePromise) {
    return (
      <StripeErrorModal
        open={isErrorModalOpen}
        message={error}
        onClose={closeErrorModal}
      />
    );
  }

  return (
    <>
      <StripeErrorModal
        open={isErrorModalOpen}
        message={error}
        onClose={closeErrorModal}
      />
      <Elements
        stripe={stripePromise}
        options={{
          clientSecret,
          appearance: {
            theme: "stripe",
          },
        }}
      >
        <StripeCheckoutForm orderNumber={orderNumber} onError={openErrorModal} />
      </Elements>
    </>
  );
}

function StripeCheckoutForm({
  orderNumber,
  onError,
}: {
  orderNumber: string | null;
  onError: (message: string) => void;
}) {
  const stripe = useStripe();
  const elements = useElements();
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!stripe || !elements) return;

    setSubmitting(true);

    const returnUrl =
      `${window.location.origin}/checkout/success/` +
      (orderNumber ?? "");

    const { error: stripeError } = await stripe.confirmPayment({
      elements,
      confirmParams: {
        return_url: returnUrl,
      },
    });

    if (stripeError) {
      onError(stripeError.message || "Erreur lors du paiement.");
      setSubmitting(false);
      return;
    }

    // Stripe gère la redirection/3DS si nécessaire.
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-3 mt-2">
      <PaymentElement />
      <Button
        type="submit"
        disabled={!stripe || !elements || submitting}
        variant="primary"
        size="md"
        className="w-full"
      >
        {submitting ? "Traitement en cours…" : "Payer maintenant"}
      </Button>
    </form>
  );
}

function StripeErrorModal({
  open,
  message,
  onClose,
}: {
  open: boolean;
  message: string | null;
  onClose: () => void;
}) {
  useEffect(() => {
    if (!open) return;

    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };

    document.addEventListener("keydown", handleEscape);
    document.body.style.overflow = "hidden";

    return () => {
      document.removeEventListener("keydown", handleEscape);
      document.body.style.overflow = "unset";
    };
  }, [open, onClose]);

  if (!open || !message) return null;

  return (
    <>
      <div
        className="fixed inset-0 bg-black/50 z-50 transition-opacity"
        onClick={onClose}
        aria-hidden="true"
      />
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
          className="bg-[var(--theme-card-bg,#ffffff)] rounded-2xl shadow-2xl max-w-md w-full relative animate-in fade-in zoom-in duration-200"
          onClick={(e) => e.stopPropagation()}
        >
          <div className="p-5 border-b border-[var(--theme-border-default,#e5e7eb)] flex items-center justify-between">
            <h2 className="text-sm font-semibold text-[var(--theme-heading-color,#111827)]">
              Paiement indisponible
            </h2>
            <button
              onClick={onClose}
              className="text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-body-color,#374151)] transition-colors"
              aria-label="Fermer"
            >
              ×
            </button>
          </div>
          <div className="p-5 space-y-3">
            <p className="text-xs text-[var(--theme-body-color,#374151)]">
              {message}
            </p>
            <div className="flex justify-end">
              <button
                onClick={onClose}
                className="rounded-lg bg-[var(--theme-primary,#111827)] px-4 py-2 text-xs font-medium text-[var(--theme-button-primary-text,#ffffff)] hover:opacity-90"
              >
                Compris
              </button>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
