"use client";

import dynamic from "next/dynamic";
import { useEffect, useState } from "react";
import { useCheckoutContext } from "../CheckoutContext";
import { useCart } from "@/components/cart/CartContext";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import type { PaymentMethod } from "@/lib/types/checkout-types";
import { ModuleHooks } from "@/components/modules/ModuleHooks";
import { logger } from "@/lib/logger";

// Lazy load Stripe Elements (heavy bundle ~100KB)
const StripePaymentForm = dynamic(
  () => import("../components/StripePaymentForm").then((mod) => ({ default: mod.StripePaymentForm })),
  {
    loading: () => (
      <div className="flex items-center gap-2 text-xs text-[var(--theme-muted-color,#6b7280)]">
        <Loader2 className="w-3 h-3 animate-spin" />
        Chargement du formulaire de paiement sécurisé…
      </div>
    ),
    ssr: false,
  }
);

type PaymentMethodCode = "card" | "paypal" | "applepay" | "test" | null;

const PAYMENT_METHOD_LABELS: Record<string, string> = {
  stripe: "Carte bancaire (Visa, Mastercard)",
  manual_test: "Paiement de test (sans Stripe)",
  paypal: "PayPal",
  applepay: "Apple Pay / Google Pay",
};

const PAYMENT_METHOD_CODE_MAP: Record<string, PaymentMethodCode> = {
  stripe: "card",
  manual_test: "test",
  paypal: "paypal",
  applepay: "applepay",
};

export function PaymentStep() {
  const router = useRouter();
  const {
    paymentMethod,
    setPaymentMethod,
    orderId,
    orderNumber,
    shippingCostBase,
    appliedPromos,
    automaticDiscountTotal,
  } = useCheckoutContext();
  const { subtotal } = useCart();
  const [availableMethods, setAvailableMethods] = useState<PaymentMethod[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [testSubmitting, setTestSubmitting] = useState(false);

  // Calculer les réductions
  const promoDiscount = appliedPromos
    .filter((p) => p.type === "order" || p.type === "product")
    .reduce((sum, p) => sum + (p.discountAmount || 0), 0);

  const shippingDiscountTotal = appliedPromos
    .filter((p) => p.freeShipping || (p.shippingDiscountAmount ?? 0) > 0)
    .reduce((sum, p) => sum + (p.shippingDiscountAmount ?? 0), 0);

  const finalShippingCost = Math.max(0, shippingCostBase - shippingDiscountTotal);
  // Note: subtotal est déjà TTC (taxes comprises), donc on n'ajoute PAS taxTotal
  const total = subtotal - promoDiscount - automaticDiscountTotal + finalShippingCost;

  useEffect(() => {
    let cancelled = false;

    async function loadPaymentMethods() {
      setLoading(true);
      setError(null);

      try {
        const res = await fetch("/api/payment-methods", {
          method: "GET",
          credentials: "include",
          headers: {
            Accept: "application/json",
          },
        });

        const data = await res.json().catch(() => null);

        if (!res.ok || !data || data.ok === false) {
          throw new Error(
            data?.error || data?.message || "Impossible de charger les moyens de paiement."
          );
        }

        if (!cancelled) {
          const methods: PaymentMethod[] = data.data || [];
          setAvailableMethods(methods);

          // Sélectionner automatiquement le premier moyen de paiement disponible
          if (!paymentMethod && methods.length > 0) {
            const firstMethodCode = PAYMENT_METHOD_CODE_MAP[methods[0].code];
            if (firstMethodCode) {
              setPaymentMethod(firstMethodCode);
            }
          }
        }
      } catch (err: unknown) {
        logger.error(err instanceof Error ? err.message : String(err));
        if (!cancelled) {
          const errorMessage = err instanceof Error ? err.message : "Erreur lors du chargement des moyens de paiement.";
          setError(errorMessage);
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    loadPaymentMethods();

    return () => {
      cancelled = true;
    };
  }, [paymentMethod, setPaymentMethod]);

  const handleTestPayment = async () => {
    if (!orderId || !orderNumber) {
      setActionError("La commande n'est pas encore prête. Veuillez réessayer.");
      return;
    }

    setTestSubmitting(true);
    setActionError(null);

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
          provider: "manual_test",
        }),
      });

      const json = await res.json().catch(() => ({}));

      if (!res.ok || !json?.ok) {
        setActionError(
          json?.message || "Impossible de valider le paiement de test."
        );
        return;
      }

      router.push(`/checkout/success/${encodeURIComponent(orderNumber)}`);
    } catch (err: unknown) {
      logger.error("Manual test payment failed", err);
      setActionError("Erreur réseau lors du paiement de test.");
    } finally {
      setTestSubmitting(false);
    }
  };

  return (
    <div className="space-y-3">
      <h2 className="text-sm font-semibold text-[var(--theme-heading-color,#111827)]">
        4. Paiement sécurisé
      </h2>
      <p className="text-xs text-[var(--theme-muted-color,#6b7280)]">
        Sélectionnez votre mode de paiement. Les formulaires se connectent à
        vos providers (Stripe, PayPal, Mollie…).
      </p>

      {loading && (
        <div className="flex items-center justify-center py-8">
          <Loader2 className="w-6 h-6 text-[var(--theme-muted-color,#6b7280)] animate-spin" />
        </div>
      )}

      {error && (
        <p className="text-xs text-red-600">
          {error}
        </p>
      )}

      {actionError && (
        <p className="text-xs text-red-600">
          {actionError}
        </p>
      )}

      {!loading && !error && availableMethods.length === 0 && (
        <p className="text-xs text-[var(--theme-muted-color,#6b7280)]">
          Aucun moyen de paiement disponible pour le moment.
        </p>
      )}

      {!loading && !error && availableMethods.length > 0 && (
        <div className="space-y-2 text-xs">
          {availableMethods.map((method) => {
            const methodCode = PAYMENT_METHOD_CODE_MAP[method.code];
            if (!methodCode) return null;

            return (
              <button
                key={method.id}
                type="button"
                onClick={() => setPaymentMethod(methodCode)}
                className={`w-full px-3 py-2 rounded-xl border text-left transition ${paymentMethod === methodCode
                    ? "border-[var(--theme-border-hover,#111827)] bg-[var(--theme-page-bg,#f6f6f7)]"
                    : "border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-card-bg,#ffffff)] hover:bg-[var(--theme-page-bg,#f6f6f7)]"
                  }`}
              >
                {PAYMENT_METHOD_LABELS[method.code] || method.name}
              </button>
            );
          })}
        </div>
      )}

      {/* Hook: checkout.payment.methods - Permet d'ajouter des méthodes de paiement personnalisées */}
      <ModuleHooks
        hookName="checkout.payment.methods"
        context={{
          paymentMethod,
          orderId,
          orderNumber,
          total,
        }}
      />

      {/* Zone des formulaires de paiement */}
      <div className="mt-3">
        {paymentMethod === "card" && (
          <>
            {!orderId && (
              <p className="text-xs text-[var(--theme-muted-color,#6b7280)] flex gap-2 items-center">
                <Loader2 className="w-3 h-3 animate-spin" />
                Initialisation du paiement en cours…
              </p>
            )}

            {orderId && (
              <StripePaymentForm
                orderId={orderId}
                orderNumber={orderNumber}
                total={total}
              />
            )}
          </>
        )}

        {paymentMethod === "paypal" && (
          <p className="text-xs text-[var(--theme-muted-color,#6b7280)]">
            Intégration PayPal à implémenter ici (module). La commande est déjà
            créée, vous pourrez appeler votre endpoint PayPal avec l&apos;ID de
            commande.
          </p>
        )}

        {paymentMethod === "applepay" && (
          <p className="text-xs text-[var(--theme-muted-color,#6b7280)]">
            Intégration Apple Pay / Google Pay (via Stripe ou autre provider)
            à implémenter ici.
          </p>
        )}

        {paymentMethod === "test" && (
          <>
            {!orderId && (
              <p className="text-xs text-[var(--theme-muted-color,#6b7280)] flex gap-2 items-center">
                <Loader2 className="w-3 h-3 animate-spin" />
                Initialisation de la commande en cours…
              </p>
            )}

            {orderId && (
              <button
                type="button"
                onClick={handleTestPayment}
                disabled={testSubmitting}
                className="w-full rounded-lg bg-[var(--theme-primary,#111827)] px-4 py-2 text-xs font-medium text-[var(--theme-button-primary-text,#ffffff)] hover:opacity-90 disabled:opacity-60"
              >
                {testSubmitting
                  ? "Validation du paiement de test..."
                  : "Valider la commande (paiement test)"}
              </button>
            )}
          </>
        )}
      </div>
    </div>
  );
}
