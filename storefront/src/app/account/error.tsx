'use client';

import { useEffect } from 'react';
import { Button } from '@/components/common/Button';
import { logger } from '@/lib/logger';

/**
 * DCA-010: Error boundary pour l'espace compte
 */
export default function AccountError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    logger.error('Account page error:', error);
  }, [error]);

  return (
    <div className="min-h-[60vh] bg-[var(--theme-page-bg,#f6f6f7)] flex items-center justify-center px-4">
      <div className="max-w-md w-full text-center">
        <div className="mb-6">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
            <svg
              className="w-8 h-8 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
              />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-[var(--theme-heading-color,#111827)] mb-2">
            Erreur de chargement
          </h2>
          <p className="text-[var(--theme-muted-color,#6b7280)] mb-4">
            Impossible de charger vos informations. Veuillez réessayer.
          </p>
          {error.digest && (
            <p className="text-xs text-[var(--theme-muted-color,#6b7280)] font-mono">
              Ref: {error.digest}
            </p>
          )}
        </div>

        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <Button onClick={reset} variant="primary" size="md">
            Réessayer
          </Button>
          <Button href="/" variant="secondary" size="md">
            Retour à l&apos;accueil
          </Button>
        </div>
      </div>
    </div>
  );
}
