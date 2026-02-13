"use client";

import { Container } from "@/components/common/Container";
import { useCart } from "@/components/cart/CartContext";
import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import type { Address } from "@/lib/types/addresses-types";
import { AuthUser } from "@/lib/types/user-types";
import { Button } from "@/components/common/Button";

import { CheckoutProvider } from "./CheckoutProvider";
import { useCheckoutContext } from "./CheckoutContext";

import { CheckoutStepper } from "./components/CheckoutStepper";
import { CheckoutSummaryWrapper } from "./components/CheckoutSummaryWrapper";
import { CheckoutReassurance } from "./components/CheckoutReassurance";

import { IdentityStep } from "./steps/IdentityStep";
import { AddressStep } from "./steps/AddressStep";
import { ShippingStep } from "./steps/ShippingStep";
import { PaymentStep } from "./steps/PaymentStep";

const steps = [
  { id: 1, label: "Identité" },
  { id: 2, label: "Adresse" },
  { id: 3, label: "Livraison" },
  { id: 4, label: "Paiement" },
];

type CheckoutClientProps = {
  initialUser: AuthUser | null;
  initialAddresses: Address[];
};

export default function CheckoutClient({
  initialUser,
  initialAddresses,
}: CheckoutClientProps) {
  return (
    <CheckoutProvider
      initialUser={initialUser}
      initialAddresses={initialAddresses}
    >
      <CheckoutClientInner />
    </CheckoutProvider>
  );
}

function CheckoutClientInner() {
  const { items, isHydrated } = useCart();
  const {
    currentStep,
    prevStep,
    nextStep,
    effectiveUser,
    identity,
    address,
    addresses,
    shippingMethodId,
    paymentMethod,
    errorModalOpen,
    errorModalMessage,
    closeErrorModal,
    handleSaveFirstAddress,
    isSavingFirstAddress,
  } = useCheckoutContext();

  const totalQty = items.reduce((sum, item) => sum + item.qty, 0);

  const canGoNext = () => {
    if (currentStep === 1) {
      return !!(effectiveUser && identity.email && identity.firstName && identity.lastName);
    }
    if (currentStep === 2) {
      return !!(address.line1 && address.zip && address.city);
    }
    if (currentStep === 3) {
      return shippingMethodId !== null;
    }
    if (currentStep === 4) {
      return !!paymentMethod;
    }
    return true;
  };

  const goPrev = () => {
    if (currentStep > 1) {
      prevStep();
    }
  };

  const goNext = async () => {
    // Étape 2 : Si l'utilisateur n'a pas d'adresse, on doit d'abord sauvegarder
    if (currentStep === 2 && effectiveUser && addresses.length === 0) {
      await handleSaveFirstAddress();
      // Ne pas passer à l'étape suivante, rester sur l'étape 2
      return;
    }

    if (canGoNext() && currentStep < 4) {
      nextStep();
    }
  };

  return (
    <>
      <main className="flex-1 py-8 bg-[var(--theme-page-bg,#f6f6f7)]">
        <Container>
          <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-2 mb-4">
            <div>
              <h1 className="text-2xl font-semibold tracking-tight text-[var(--theme-heading-color,#111827)]">
                Finaliser ma commande
              </h1>
              <p className="text-xs text-[var(--theme-muted-color,#6b7280)]">
                {!isHydrated
                  ? "\u00A0"
                  : totalQty > 0
                    ? `${totalQty} article${totalQty > 1 ? "s" : ""} dans votre panier`
                    : "Votre panier est vide."}
              </p>
              {effectiveUser && (
                <p className="mt-1 text-xxs text-[var(--theme-muted-color,#6b7280)]">
                  Connecté en tant que{" "}
                  <span className="font-medium text-[var(--theme-body-color,#374151)]">
                    {(effectiveUser.firstname || effectiveUser.lastname
                      ? `${effectiveUser.firstname ?? ""} ${effectiveUser.lastname ?? ""}`.trim()
                      : effectiveUser.email) || effectiveUser.email}
                  </span>
                </p>
              )}
            </div>
            {isHydrated && totalQty === 0 && (
              <Link
                href="/products"
                className="inline-flex items-center rounded-full bg-[var(--theme-primary,#111827)] px-4 py-1.5 text-xs text-[var(--theme-button-primary-text,#ffffff)] hover:opacity-90"
              >
                Continuer mes achats
              </Link>
            )}
          </div>

          {isHydrated && items.length === 0 && (
            <div className="theme-panel mt-4 rounded-2xl bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] p-6 text-xs text-[var(--theme-muted-color,#6b7280)]">
              Votre panier est vide. Ajoutez des produits pour accéder au checkout.
            </div>
          )}

          {isHydrated && items.length > 0 && (
            <div className="grid grid-cols-1 lg:grid-cols-[minmax(0,2fr)_300px] gap-6 items-start">
              <div className="space-y-4">
                <CheckoutStepper steps={steps} currentStep={currentStep} />

                <div className="theme-panel rounded-2xl bg-[var(--theme-card-bg,#ffffff)] border border-[var(--theme-border-default,#e5e7eb)] p-4 space-y-4">
                  {currentStep === 1 && <IdentityStep />}
                  {currentStep === 2 && <AddressStep />}
                  {currentStep === 3 && <ShippingStep />}
                  {currentStep === 4 && <PaymentStep />}

                  {/* Boutons de navigation - cachés à l'étape 1 si non connecté (CheckoutAuth gère ses propres boutons) */}
                  {!(currentStep === 1 && !effectiveUser) && (
                    <div className="flex items-center justify-between pt-2">
                      <button
                        type="button"
                        onClick={goPrev}
                        disabled={currentStep === 1}
                        className="text-xs text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-heading-color,#111827)] disabled:opacity-30 flex items-center gap-1"
                      >
                        <ArrowLeft className="w-3 h-3" /> Retour
                      </button>

                      {currentStep < 4 && (
                        <Button
                          type="button"
                          onClick={goNext}
                          disabled={!canGoNext() || isSavingFirstAddress}
                          variant="primary"
                          size="md"
                        >
                          {currentStep === 2 && effectiveUser && addresses.length === 0
                            ? isSavingFirstAddress
                              ? "Enregistrement..."
                              : "Enregistrer cette adresse"
                            : "Continuer"}
                        </Button>
                      )}
                    </div>
                  )}
                </div>
              </div>

              <aside className="space-y-3 sticky top-24">
                <CheckoutSummaryWrapper />
                <CheckoutReassurance />
              </aside>
            </div>
          )}
        </Container>
      </main>

      {/* Modal d'erreur générique */}
      {errorModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="theme-panel w-full max-w-md rounded-2xl bg-[var(--theme-card-bg,#ffffff)] p-6 shadow-xl">
            <div className="mb-4">
              <h3 className="text-lg font-semibold text-[var(--theme-heading-color,#111827)]">
                Attention
              </h3>
            </div>

            <div className="mb-6">
              <p className="text-sm text-[var(--theme-muted-color,#6b7280)]">
                {errorModalMessage}
              </p>
            </div>

            <div className="flex justify-end">
              <Button
                type="button"
                onClick={closeErrorModal}
                variant="primary"
                size="md"
              >
                Compris
              </Button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
