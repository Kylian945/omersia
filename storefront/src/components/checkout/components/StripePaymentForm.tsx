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

const stripePromise = loadStripe(
  process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY as string
);

type StripePaymentFormProps = {
  orderId: number;
  orderNumber: string | null;
  total: number;
};

export function StripePaymentForm({ orderId, orderNumber, total }: StripePaymentFormProps) {
  const [clientSecret, setClientSecret] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!orderId) return;

    let cancelled = false;

    (async () => {
      try {
        setError(null);

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
          throw new Error(
            errorData?.message ||
            errorData?.backend ||
            `Impossible d'initialiser le paiement (HTTP ${res.status}).`
          );
        }

        // 👉 On sait que ta réponse ressemble à ça :
        // { ok: true, data: { provider: 'stripe', client_secret: '...' } }
        const cs = json.data?.client_secret;

        if (!cs) {
          throw new Error(
            "client_secret manquant dans la réponse de l'API."
          );
        }

        if (!cancelled) {
          setClientSecret(cs);
        }
      } catch (e: unknown) {
        console.error("StripePaymentForm error", e);
        if (!cancelled) {
          const message = e instanceof Error ? e.message : "Erreur lors de l'initialisation du paiement.";
          setError(message);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [orderId, total]);

  if (error) {
    return (
      <div className="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xxs text-red-700">
        {error}
      </div>
    );
  }

  if (!clientSecret) {
    return;
  }

  return (
    <Elements
      stripe={stripePromise}
      options={{
        clientSecret,
        appearance: {
          theme: "stripe",
        },
      }}
    >
      <StripeCheckoutForm orderNumber={orderNumber} />
    </Elements>
  );
}

function StripeCheckoutForm({ orderNumber }: { orderNumber: string | null }) {
  const stripe = useStripe();
  const elements = useElements();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!stripe || !elements) return;

    setSubmitting(true);
    setError(null);

    localStorage.removeItem("omersia_cart_items");

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
      setError(stripeError.message || "Erreur lors du paiement.");
      setSubmitting(false);
      return;
    }

    // Stripe gère la redirection/3DS si nécessaire.
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-3 mt-2">
      <PaymentElement />
      {error && (
        <p className="text-xxs text-red-600 mt-1">
          {error}
        </p>
      )}
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
