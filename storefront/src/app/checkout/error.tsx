'use client';

import { useEffect } from 'react';
import { Button } from '@/components/common/Button';
import { logger } from '@/lib/logger';

/**
 * DCA-010: Error boundary pour le checkout
 * Critique: doit rassurer le client et proposer des alternatives
 */
export default function CheckoutError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    logger.error('Checkout error:', error);
  }, [error]);

  return (
    <div className="min-h-[60vh] bg-[var(--theme-page-bg,#f6f6f7)] flex items-center justify-center px-4">
      <div className="max-w-md w-full text-center">
        <div className="mb-6">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4 bg-[var(--theme-error-bg,#fee2e2)]">
            <svg
              className="w-8 h-8 text-[var(--theme-error-color,#ef4444)]"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
              />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-[var(--theme-heading-color,#111827)] mb-2">
            Erreur lors du paiement
          </h2>
          <p className="text-[var(--theme-body-color,#374151)] mb-2">
            Une erreur est survenue. Votre panier est intact et aucun paiement n&apos;a été effectué.
          </p>
          <p className="text-sm text-[var(--theme-muted-color,#6b7280)] mb-4">
            Veuillez réessayer ou retourner à votre panier.
          </p>
          {error.digest && (
            <p className="text-xs text-[var(--theme-muted-color,#6b7280)] font-mono">
              Ref: {error.digest}
            </p>
          )}
        </div>

        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <Button onClick={reset} variant="primary" size="md">
            Réessayer le paiement
          </Button>
          <Button href="/cart" variant="secondary" size="md">
            Retour au panier
          </Button>
        </div>

        <p className="mt-6 text-xs text-[var(--theme-muted-color,#6b7280)]">
          Besoin d&apos;aide ? Contactez-nous pour finaliser votre commande.
        </p>
      </div>
    </div>
  );
}
